<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A signed-in buyer's chat with the store (one per buyer). *_read_id = last message id that side has seen. */
class ChatConversation extends Model
{
    protected $fillable = ['user_id'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime', 'buyer_read_id' => 'integer', 'admin_read_id' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ChatMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    /**
     * Conversations with buyer messages the admin has not seen yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUnreadByAdmin(Builder $query): void
    {
        $query->whereHas('messages', fn (Builder $messages) => $messages->where('from_admin', false)->whereColumn('chat_messages.id', '>', 'chat_conversations.admin_read_id'));
    }

    public function unreadForBuyer(): int
    {
        return $this->messages()->where('from_admin', true)->where('id', '>', $this->buyer_read_id)->count();
    }

    public function unreadForAdmin(): int
    {
        return $this->messages()->where('from_admin', false)->where('id', '>', $this->admin_read_id)->count();
    }
}
