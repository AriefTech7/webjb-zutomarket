// Live chat: the buyer's floating widget and the admin's conversation page share this code. Each [data-chat] box polls
// its endpoint with ?after=<last id> and appends new messages; message text is always set with textContent.

/** Highest message id seen so far, so the next poll only asks for newer ones. */
export function lastMessageId(messages, current = 0) {
    return messages.reduce((max, message) => Math.max(max, Number(message.id) || 0), current);
}

/** Drops messages already on screen (a reply can arrive both from send() and from the next poll). */
export function onlyNew(messages, shownIds) {
    return messages.filter((message) => !shownIds.has(message.id));
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function createChat(box, { isOpen, onUnread }) {
    const list = box.querySelector('[data-chat-list]');
    const form = box.querySelector('[data-chat-form]');
    const input = form.elements.body;
    const status = box.querySelector('[data-chat-status]');
    const empty = box.querySelector('[data-chat-empty]');
    const shown = new Set([...list.querySelectorAll('[data-message-id]')].map((item) => Number(item.dataset.messageId)));
    let after = lastMessageId([...shown].map((id) => ({ id })));
    let busy = false;

    const append = (messages) => {
        const fresh = onlyNew(messages, shown);
        if (!fresh.length) return;
        const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
        fresh.forEach((message) => {
            shown.add(message.id);
            const item = document.createElement('li');
            item.className = `chat-msg ${message.mine ? 'is-mine' : ''}`;
            item.dataset.messageId = message.id;
            const bubble = document.createElement('p');
            bubble.className = 'chat-bubble';
            bubble.textContent = message.body;
            const time = document.createElement('span');
            time.className = 'chat-time';
            time.textContent = message.time;
            item.append(bubble, time);
            list.append(item);
        });
        after = lastMessageId(fresh, after);
        if (empty) empty.hidden = true;
        if (nearBottom || fresh.some((message) => message.mine)) list.scrollTop = list.scrollHeight;
    };

    const poll = async () => {
        if (busy) return;
        busy = true;
        try {
            const url = new URL(box.dataset.chatEndpoint, window.location.href);
            url.searchParams.set('after', String(after));
            if (isOpen()) url.searchParams.set('seen', '1');
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            if (data.cleared) {
                // The admin deleted this chat: start again from an empty conversation.
                list.replaceChildren();
                shown.clear();
                after = 0;
                if (empty) empty.hidden = false;
            }
            append(data.messages ?? []);
            onUnread?.(isOpen() ? 0 : Number(data.unread) || 0);
        } catch {
            // Offline for a moment: the next poll tries again.
        } finally {
            busy = false;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        status.textContent = '';
        try {
            const response = await fetch(box.dataset.chatSend, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ body }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                status.textContent = data.errors?.body?.[0] ?? (response.status === 429 ? 'Terlalu cepat, tunggu sebentar.' : 'Pesan belum terkirim. Coba lagi.');
                return;
            }
            input.value = '';
            append([data.message]);
        } catch {
            status.textContent = 'Pesan belum terkirim. Periksa koneksi lalu coba lagi.';
        } finally {
            button.disabled = false;
            input.focus();
        }
    });
    // Enter sends, Shift+Enter makes a new line.
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    list.scrollTop = list.scrollHeight;
    return { poll };
}

function every(ms, task) {
    let timer;
    const tick = async () => {
        if (document.visibilityState === 'visible') await task();
        timer = setTimeout(tick, typeof ms === 'function' ? ms() : ms);
    };
    timer = setTimeout(tick, typeof ms === 'function' ? ms() : ms);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') task(); });
    return () => clearTimeout(timer);
}

export function initialiseChat() {
    // Storefront widget. Guests get the panel with a sign-in button only (no [data-chat] box, nothing to poll).
    const widget = document.querySelector('[data-chat-widget]');
    if (widget) {
        const panel = widget.querySelector('[data-chat-panel]');
        const badge = widget.querySelector('[data-chat-badge]');
        const box = widget.querySelector('[data-chat]');
        const expand = widget.querySelector('[data-chat-expand]');
        const isOpen = () => !panel.hidden;
        const showUnread = (count) => {
            badge.textContent = count > 9 ? '9+' : String(count);
            badge.hidden = count === 0;
        };
        const chat = box ? createChat(box, { isOpen, onUnread: showUnread }) : null;
        const toggle = () => {
            panel.hidden = !panel.hidden;
            widget.querySelector('[data-chat-launcher]').setAttribute('aria-expanded', String(isOpen()));
            if (!isOpen()) return;
            showUnread(0);
            if (chat) {
                chat.poll();
                box.querySelector('textarea').focus();
                const list = box.querySelector('[data-chat-list]');
                list.scrollTop = list.scrollHeight;
            } else {
                panel.querySelector('a[href]')?.focus();
            }
        };
        widget.querySelectorAll('[data-chat-toggle]').forEach((button) => button.addEventListener('click', toggle));
        // "Tanya Stok" on an account page: open the chat with the question already typed (unless the buyer typed something).
        document.addEventListener('click', (event) => {
            const ask = event.target.closest('[data-chat-ask]');
            if (!ask) return;
            if (!isOpen()) toggle();
            const textarea = box?.querySelector('textarea');
            if (textarea && !textarea.value.trim()) {
                textarea.value = ask.dataset.chatAsk;
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
        });
        expand?.addEventListener('click', () => {
            const expanded = panel.classList.toggle('is-expanded');
            expand.setAttribute('aria-pressed', String(expanded));
            expand.setAttribute('aria-label', expanded ? 'Perkecil chat' : 'Perbesar chat');
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen()) toggle();
        });
        if (chat) {
            chat.poll();
            every(() => (isOpen() ? 4000 : 30000), chat.poll);
        }
    }

    // Admin conversation page: always open.
    const adminBox = document.querySelector('[data-chat-admin] [data-chat]');
    if (adminBox) {
        const chat = createChat(adminBox, { isOpen: () => true });
        every(4000, chat.poll);
    }

    // Admin menu badge: conversations waiting for a reply.
    // The admin menu is rendered twice (sidebar and phone menu), so every copy of the badge is updated.
    const counters = [...document.querySelectorAll('[data-chat-unread-endpoint]')];
    if (counters.length) {
        every(30000, async () => {
            try {
                const response = await fetch(counters[0].dataset.chatUnreadEndpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                if (!response.ok) return;
                const { count } = await response.json();
                counters.forEach((counter) => {
                    counter.textContent = String(count);
                    counter.hidden = !count;
                });
            } catch {
                // Try again on the next tick.
            }
        });
    }
}
