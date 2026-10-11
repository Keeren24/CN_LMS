'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('aiTutorChat');
    if (!root) return;

    const sendUrl = root.dataset.sendUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const list = document.getElementById('chatMessages');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    const remainingEl = document.getElementById('remainingCount');
    const limitAlert = document.getElementById('limitAlert');

    let busy = false;

    const scrollDown = () => { list.scrollTop = list.scrollHeight; };

    // Tiny Markdown subset for the tutor's replies: fenced code, inline code, **bold**,
    // headings, bullet and numbered lists. Elements are built with createElement and
    // textContent only, so nothing the model writes is ever parsed as HTML.
    function renderInline(parent, text) {
        text.split(/(`[^`\n]+`|\*\*[^*\n]+\*\*)/g).forEach((token) => {
            if (!token) return;
            if (token.length > 2 && token.startsWith('`') && token.endsWith('`')) {
                const code = document.createElement('code');
                code.textContent = token.slice(1, -1);
                parent.appendChild(code);
            } else if (token.length > 4 && token.startsWith('**') && token.endsWith('**')) {
                const strong = document.createElement('strong');
                strong.textContent = token.slice(2, -2);
                parent.appendChild(strong);
            } else {
                parent.appendChild(document.createTextNode(token));
            }
        });
    }

    function renderBlocks(container, text) {
        let para = [];
        let list = null;

        const flushPara = () => {
            if (!para.length) return;
            const p = document.createElement('p');
            renderInline(p, para.join('\n'));
            container.appendChild(p);
            para = [];
        };

        const addItem = (ordered, number, content) => {
            if (!list || list.ordered !== ordered) {
                flushPara();
                list = { ordered, el: document.createElement(ordered ? 'ol' : 'ul') };
                container.appendChild(list.el);
            }
            const li = document.createElement('li');
            if (ordered && number) li.value = number;
            renderInline(li, content);
            list.el.appendChild(li);
        };

        text.split('\n').forEach((raw) => {
            const line = raw.replace(/\s+$/, '');
            let m;

            if (line.trim() === '') { flushPara(); list = null; return; }

            if ((m = line.match(/^\s*#{1,6}\s+(.*)$/))) {
                flushPara(); list = null;
                const p = document.createElement('p');
                p.className = 'fw-semibold';
                renderInline(p, m[1]);
                container.appendChild(p);
            } else if ((m = line.match(/^\s*[-*•]\s+(.*)$/))) {
                addItem(false, 0, m[1]);
            } else if ((m = line.match(/^\s*(\d+)[.)]\s+(.*)$/))) {
                addItem(true, parseInt(m[1], 10), m[2]);
            } else if (list && /^\s{2,}\S/.test(raw)) {
                list.el.lastElementChild.appendChild(document.createElement('br'));
                renderInline(list.el.lastElementChild, line.trim());
            } else {
                list = null;
                para.push(line);
            }
        });

        flushPara();
    }

    function renderMarkdown(el, text) {
        el.textContent = '';
        el.classList.add('is-rendered');

        // split() with one capture group alternates: prose, code, prose, code, ...
        text.split(/```[^\n]*\n([\s\S]*?)```/g).forEach((part, i) => {
            if (i % 2 === 1) {
                const pre = document.createElement('pre');
                const code = document.createElement('code');
                // Drop the shared leading indent (models indent fences inside list items) and trailing blank lines.
                const lines = part.replace(/\s+$/, '').split('\n');
                const indents = lines.filter((l) => l.trim() !== '').map((l) => l.match(/^\s*/)[0].length);
                const strip = indents.length ? Math.min(...indents) : 0;
                code.textContent = lines.map((l) => l.slice(strip)).join('\n');
                pre.appendChild(code);
                el.appendChild(pre);
            } else if (part.trim() !== '') {
                renderBlocks(el, part);
            }
        });
    }

    // The student's own text and the "Thinking…" placeholder stay plain; replies are rendered.
    function addBubble(role, text, extraClass) {
        document.getElementById('chatEmpty')?.remove();

        const el = document.createElement('div');
        el.className = 'tutor-bubble ' + (role === 'user' ? 'is-user' : 'is-assistant') + (extraClass ? ' ' + extraClass : '');
        if (role !== 'user' && !extraClass) {
            renderMarkdown(el, text);
        } else {
            el.textContent = text;
        }
        list.appendChild(el);
        scrollDown();

        return el;
    }

    function setRemaining(n) {
        remainingEl.textContent = n;

        const out = n <= 0;
        limitAlert.classList.toggle('d-none', !out);
        input.disabled = out;
        sendBtn.disabled = out;
    }

    function fail(title, message) {
        Swal.fire({
            title,
            text: message,
            icon: 'warning',
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-primary' },
            buttonsStyling: false,
        });
    }

    async function send() {
        const text = input.value.trim();
        if (!text || busy) return;

        busy = true;
        sendBtn.disabled = true;
        input.value = '';

        const userBubble = addBubble('user', text);
        const typing = addBubble('assistant', 'Thinking…', 'is-typing');

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ message: text }),
            });

            let data = {};
            try { data = await res.json(); } catch (e) { /* non-JSON error page */ }

            typing.remove();

            if (res.ok && data.success) {
                addBubble('assistant', data.reply);
                setRemaining(data.remaining);
            } else {
                // Nothing was saved server-side, so drop the optimistic bubble and hand the text back.
                userBubble.remove();
                input.value = text;

                if (res.status === 429) {
                    setRemaining(0);
                    fail('Daily limit reached', data.message);
                } else if (res.status === 422) {
                    fail('Check your message', data.errors?.message?.[0] || 'That message could not be sent.');
                } else {
                    fail('Tutor unavailable', data.message || 'Something went wrong. Please try again.');
                }
            }
        } catch (e) {
            typing.remove();
            userBubble.remove();
            input.value = text;
            fail('Connection problem', 'Could not reach the tutor. Check your connection and try again.');
        } finally {
            busy = false;
            if (!input.disabled) {
                sendBtn.disabled = false;
                input.focus();
            }
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        send();
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send();
        }
    });

    // History arrives server-rendered as plain text (works without JS); format the replies.
    list.querySelectorAll('.tutor-bubble.is-assistant').forEach((el) => renderMarkdown(el, el.textContent));

    scrollDown();
});
