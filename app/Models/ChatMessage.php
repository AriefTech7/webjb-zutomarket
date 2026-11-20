<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const MAX_LENGTH = 1000;

    protected $fillable = ['body', 'from_admin', 'admin_id'];

    protected function casts(): array
    {
        return ['from_admin' => 'boolean'];
    }

    /** @return BelongsTo<ChatConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /**
     * Shape sent to the chat widgets; the body is plain text and is inserted with textContent, never as HTML.
     *
     * @return array{id: int, mine: bool, body: string, time: string}
     */
    public function toChatArray(bool $viewerIsAdmin): array
    {
        return [
            'id' => $this->id,
            'mine' => $this->from_admin === $viewerIsAdmin,
            'body' => $this->body,
            'time' => $this->created_at->timezone(GameAccount::LOCAL_TIMEZONE)->format('d/m H:i'),
        ];
    }
}
