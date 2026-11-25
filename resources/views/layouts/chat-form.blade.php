{{-- Message box shared by the buyer widget and the admin conversation page (submitted by chat.js). --}}
<form class="border-t border-white/10 p-2" data-chat-form>
    <div class="flex items-end gap-2">
        <label class="sr-only" for="chat-body-{{ $chatFormId ?? 'buyer' }}">Pesan</label>
        <textarea id="chat-body-{{ $chatFormId ?? 'buyer' }}" name="body" rows="1" maxlength="{{ \App\Models\ChatMessage::MAX_LENGTH }}" required
            class="field max-h-32 min-h-11 flex-1 resize-none py-2.5" placeholder="Tulis pesan...">{{ $chatDraft ?? '' }}</textarea>
        <button type="submit" class="btn size-11 shrink-0 rounded-full p-0" aria-label="Kirim pesan">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" /></svg>
        </button>
    </div>
    <p class="px-1 pt-1 text-xs text-rose-300" data-chat-status role="status" aria-live="polite"></p>
</form>
