<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_sign_in_and_buyers_get_the_chat_widget(): void
    {
        $this->get('/')->assertOk()->assertSee('data-chat-widget', false)->assertSee('Login untuk Mulai Chat')->assertSee(route('account.login'), false)
            ->assertDontSee('data-chat-endpoint', false);
        $this->getJson('/chat/pesan')->assertUnauthorized();
        $this->postJson('/chat/pesan', ['body' => 'Halo'])->assertUnauthorized();

        $this->actingAs(User::factory()->buyer()->create(['name' => 'Budi']));
        $this->get('/')->assertOk()->assertSee('data-chat-endpoint', false)->assertSee('Halo Budi!')->assertDontSee('Login untuk Mulai Chat');
        $this->getJson('/chat/pesan')->assertOk()->assertExactJson(['messages' => [], 'unread' => 0]);
    }

    public function test_a_buyer_and_the_super_admin_chat_both_ways(): void
    {
        $buyer = User::factory()->buyer()->create(['name' => 'Budi']);
        $super = User::factory()->create();

        $sent = $this->actingAs($buyer)->postJson('/chat/pesan', ['body' => "  Akun ML masih ada?\n<b>tes</b>  "])->assertCreated()->json('message');
        $this->assertSame([true, "Akun ML masih ada?\n<b>tes</b>"], [$sent['mine'], $sent['body']]);
        $this->postJson('/chat/pesan', ['body' => '   '])->assertUnprocessable()->assertJsonValidationErrors(['body' => 'Tulis pesan dulu.']);
        $this->postJson('/chat/pesan', ['body' => str_repeat('a', 1001)])->assertUnprocessable();
        $conversation = ChatConversation::sole();

        // The super admin sees a waiting chat, opens it (escaped) and replies.
        $this->actingAs($super)->get('/admin')->assertOk()->assertSee('data-chat-unread-endpoint', false);
        $this->getJson('/admin/chat-unread')->assertExactJson(['count' => 1]);
        $this->get('/admin/chat')->assertOk()->assertSee('Budi')->assertSee('1 baru');
        $this->get('/admin/chat/'.$conversation->id)->assertOk()->assertSee('&lt;b&gt;tes&lt;/b&gt;', false)->assertDontSee('<b>tes</b>', false);
        $this->getJson('/admin/chat-unread')->assertExactJson(['count' => 0]);
        $reply = $this->postJson('/admin/chat/'.$conversation->id.'/pesan', ['body' => 'Masih, kak'])->assertCreated()->json('message');
        $this->assertTrue($reply['mine']);

        // The buyer gets the reply as unread until the widget is open.
        $this->actingAs($buyer);
        $poll = $this->getJson('/chat/pesan?after='.$sent['id'])->assertOk();
        $this->assertSame(['Masih, kak'], array_column($poll->json('messages'), 'body'));
        $this->assertFalse($poll->json('messages.0.mine'));
        $this->assertSame(1, $poll->json('unread'));
        $this->getJson('/chat/pesan?after='.$reply['id'].'&seen=1')->assertJson(['messages' => [], 'unread' => 0]);
        $this->assertCount(2, $this->getJson('/chat/pesan')->json('messages'));
    }

    public function test_the_super_admin_deletes_a_chat_and_the_buyer_starts_fresh(): void
    {
        $buyer = User::factory()->buyer()->create(['name' => 'Budi']);
        $last = $this->actingAs($buyer)->postJson('/chat/pesan', ['body' => 'Halo'])->json('message.id');
        $conversation = ChatConversation::sole();

        $this->actingAs(User::factory()->regularAdmin()->create())->delete('/admin/chat/'.$conversation->id)->assertForbidden();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/chat')->assertSee('action="'.route('admin.chat.destroy', $conversation).'"', false);
        $this->delete('/admin/chat/'.$conversation->id)->assertRedirect(route('admin.chat'))->assertSessionHas('success', 'Chat dengan Budi dihapus.');
        $this->assertModelMissing($conversation);
        $this->assertDatabaseCount('chat_messages', 0);
        $this->getJson('/admin/chat-unread')->assertExactJson(['count' => 0]);

        // The open widget is told to clear; a new message starts a new conversation.
        $this->actingAs($buyer);
        $this->getJson('/chat/pesan?after='.$last)->assertExactJson(['messages' => [], 'unread' => 0, 'cleared' => true]);
        $this->getJson('/chat/pesan')->assertExactJson(['messages' => [], 'unread' => 0]);
        $this->postJson('/chat/pesan', ['body' => 'Halo lagi'])->assertCreated();
        $this->assertSame(1, ChatConversation::count());
    }

    public function test_the_widget_shows_chat_admins_seen_in_the_last_five_minutes_as_online(): void
    {
        $this->get('/')->assertSee('Admin sedang offline')->assertDontSee('Admin Online');

        $razan = User::factory()->create(['name' => 'Razan']);
        $staff = User::factory()->regularAdmin()->create();
        $this->travelTo(now()->startOfMinute());
        $this->actingAs($razan)->get('/admin')->assertOk();
        $this->actingAs($staff)->get('/admin')->assertOk();
        $this->assertNotNull($razan->refresh()->last_seen_at);
        auth()->logout();

        // Only super admins answer chats, so only they count.
        $this->get('/')->assertSee('1 Admin Online')->assertSee('title="Razan"', false);
        $this->travel(6)->minutes();
        $this->get('/')->assertSee('Admin sedang offline');
    }

    public function test_only_the_super_admin_reads_chats_and_admins_cannot_use_the_buyer_widget(): void
    {
        $buyer = User::factory()->buyer()->create();
        $this->actingAs($buyer)->postJson('/chat/pesan', ['body' => 'Halo']);
        $conversation = ChatConversation::sole();
        $this->get('/admin/chat')->assertForbidden();

        $regular = User::factory()->regularAdmin()->create();
        $this->actingAs($regular);
        foreach (['/admin/chat', '/admin/chat/'.$conversation->id, '/admin/chat-unread'] as $page) {
            $this->get($page)->assertForbidden();
        }
        $this->postJson('/admin/chat/'.$conversation->id.'/pesan', ['body' => 'x'])->assertForbidden();
        $this->get('/admin')->assertDontSee('data-chat-unread-endpoint', false);
        $this->getJson('/chat/pesan')->assertForbidden();
        $this->get('/')->assertDontSee('data-chat-widget', false)->assertDontSee('Live chat admin');

        // The super admin gets a launcher into Admin > Live chat with the waiting count.
        $this->actingAs(User::factory()->create());
        $this->get('/')->assertOk()->assertDontSee('data-chat-widget', false)->assertSee('href="'.route('admin.chat').'"', false)->assertSee('1 belum dibalas');

        // Another buyer never sees someone else's conversation.
        $this->actingAs(User::factory()->buyer()->create());
        $this->getJson('/chat/pesan')->assertExactJson(['messages' => [], 'unread' => 0]);
    }
}
