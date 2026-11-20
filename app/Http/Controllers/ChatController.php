<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Live chat. Signed-in buyers chat from the floating widget (layouts/chat-widget); the super admin answers in
 * Admin > Live chat. Both sides poll the JSON endpoints with ?after=<last message id> to pick up new messages.
 */
class ChatController extends Controller
{
    private const HISTORY = 100;

    public function messages(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->is_admin, 403);
        $after = max(0, (int) $request->query('after', 0));
        $conversation = ChatConversation::firstWhere('user_id', $user->id);
        if (! $conversation) {
            // after > 0 means the widget still shows messages: the admin deleted the chat, so it should clear them.
            return $this->json(collect(), 0, false)->setData(['messages' => [], 'unread' => 0] + ($after > 0 ? ['cleared' => true] : []));
        }
        $messages = $this->messagesAfter($conversation, $after);
        // The widget says seen=1 while it is open: everything the buyer can see is read.
        if ($request->boolean('seen') && ($last = $conversation->messages()->max('id'))) {
            $conversation->forceFill(['buyer_read_id' => $last])->save();
        }

        return $this->json($messages, $conversation->unreadForBuyer(), false);
    }

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->is_admin, 403);
        $data = $this->validateBody($request);
        $conversation = ChatConversation::firstOrCreate(['user_id' => $user->id]);
        $message = $conversation->messages()->create(['body' => $data['body'], 'from_admin' => false]);
        $conversation->forceFill(['last_message_at' => $message->created_at, 'buyer_read_id' => $message->id])->save();

        return response()->json(['message' => $message->toChatArray(false)], 201);
    }

    public function adminIndex(): View
    {
        return view('admin.chat.index', [
            'conversations' => ChatConversation::with(['user:id,name,email,whatsapp', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
                ->whereNotNull('last_message_at')->latest('last_message_at')->paginate(30),
        ]);
    }

    public function adminShow(ChatConversation $conversation): View
    {
        $messages = $conversation->messages()->latest('id')->limit(self::HISTORY)->get()->reverse()->values();
        $this->markAdminRead($conversation);

        return view('admin.chat.show', ['conversation' => $conversation->load('user'), 'messages' => $messages]);
    }

    public function adminMessages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $messages = $this->messagesAfter($conversation, max(0, (int) $request->query('after', 0)));
        $this->markAdminRead($conversation);

        return $this->json($messages, 0, true);
    }

    public function adminReply(Request $request, ChatConversation $conversation): JsonResponse
    {
        $data = $this->validateBody($request);
        $message = $conversation->messages()->create(['body' => $data['body'], 'from_admin' => true, 'admin_id' => $request->user()->id]);
        $conversation->forceFill(['last_message_at' => $message->created_at, 'admin_read_id' => $message->id])->save();

        return response()->json(['message' => $message->toChatArray(true)], 201);
    }

    /** Delete a conversation with all its messages. If the buyer writes again, a fresh conversation starts. */
    public function adminDestroy(ChatConversation $conversation): RedirectResponse
    {
        $name = $conversation->user?->name ?? 'pembeli';
        $conversation->delete();

        return redirect()->route('admin.chat')->with('success', 'Chat dengan '.$name.' dihapus.');
    }

    /** Number of conversations waiting for the admin, for the menu badge. */
    public function adminUnread(): JsonResponse
    {
        return response()->json(['count' => ChatConversation::unreadByAdmin()->count()])->header('Cache-Control', 'no-store');
    }

    /** @return array{body: string} */
    private function validateBody(Request $request): array
    {
        $request->merge(['body' => trim((string) $request->input('body'))]);

        return $request->validate(['body' => ['required', 'string', 'max:'.ChatMessage::MAX_LENGTH]], [
            'body.required' => 'Tulis pesan dulu.',
            'body.max' => 'Pesan maksimal '.ChatMessage::MAX_LENGTH.' karakter.',
        ]);
    }

    /** First load (after=0): the latest HISTORY messages; afterwards only newer ones. */
    /** @return Collection<int, ChatMessage> */
    private function messagesAfter(ChatConversation $conversation, int $after): Collection
    {
        $query = $conversation->messages()->where('id', '>', $after);

        return $after === 0
            ? $query->latest('id')->limit(self::HISTORY)->get()->reverse()->values()
            : $query->oldest('id')->limit(self::HISTORY)->get();
    }

    private function markAdminRead(ChatConversation $conversation): void
    {
        if ($last = $conversation->messages()->max('id')) {
            $conversation->forceFill(['admin_read_id' => $last])->save();
        }
    }

    /** @param  Collection<int, ChatMessage>  $messages */
    private function json(Collection $messages, int $unread, bool $viewerIsAdmin): JsonResponse
    {
        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $message): array => $message->toChatArray($viewerIsAdmin))->values(),
            'unread' => $unread,
        ])->header('Cache-Control', 'no-store');
    }
}
