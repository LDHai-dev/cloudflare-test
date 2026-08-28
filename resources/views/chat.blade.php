<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { font-family: system-ui, sans-serif; background: #f1f5f9; height: 100vh; display: flex; flex-direction: column; }
        header { background: #fff; padding: .75rem 1rem; display: flex; align-items: center; gap: 1rem; border-bottom: 1px solid #e2e8f0; }
        header .mode { color: #64748b; font-size: .8rem; margin-left: auto; }
        header button { border: 0; background: #e2e8f0; border-radius: 6px; padding: .4rem .75rem; cursor: pointer; }
        main { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .5rem; }
        .msg { max-width: 70%; background: #fff; border-radius: 12px; padding: .5rem .75rem; box-shadow: 0 1px 2px rgba(0,0,0,.06); align-self: flex-start; }
        .msg.mine { align-self: flex-end; background: #dbeafe; }
        .msg .who { font-size: .7rem; color: #64748b; margin-bottom: .15rem; }
        .msg .body { white-space: pre-wrap; word-break: break-word; }
        .msg a.file { display: inline-block; margin-top: .25rem; color: #2563eb; text-decoration: none; font-size: .9rem; }
        .msg .summary { margin-top: .5rem; padding: .5rem; background: #f8fafc; border-left: 3px solid #2563eb; font-size: .85rem; white-space: pre-wrap; border-radius: 0 6px 6px 0; }
        .msg button.summarize { margin-top: .35rem; border: 1px solid #2563eb; color: #2563eb; background: none; border-radius: 6px; padding: .25rem .6rem; font-size: .8rem; cursor: pointer; }
        .msg button.summarize:disabled { opacity: .5; cursor: wait; }
        form#composer { display: flex; gap: .5rem; padding: .75rem 1rem; background: #fff; border-top: 1px solid #e2e8f0; }
        #body { flex: 1; padding: .55rem .75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; font-family: inherit; resize: none; }
        #composer button { border: 0; background: #2563eb; color: #fff; border-radius: 8px; padding: 0 1.25rem; font-size: 1rem; cursor: pointer; }
        #file-label { display: grid; place-items: center; width: 42px; border: 1px dashed #cbd5e1; border-radius: 8px; cursor: pointer; font-size: 1.1rem; }
        #file-label.has-file { border-color: #2563eb; background: #dbeafe; }
        #file { display: none; }
    </style>
</head>
<body>
    <header>
        <strong>{{ auth()->user()->name }}</strong>
        <span class="mode">Chế độ tóm tắt: {{ config('services.ai.summarize_on_upload') ? 'tự động khi upload' : 'bấm nút' }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>Đăng xuất</button></form>
    </header>
    <main id="messages"></main>
    <form id="composer">
        <label id="file-label" for="file" title="Đính kèm tệp">📎</label>
        <input type="file" id="file">
        <textarea id="body" rows="1" placeholder="Nhắn tin..."></textarea>
        <button>Gửi</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2/dist/echo.iife.js"></script>
    <script>
        const ME = {{ auth()->id() }};
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const HEADERS = { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF };
        const list = document.getElementById('messages');
        const fileInput = document.getElementById('file');
        const bodyInput = document.getElementById('body');
        let lastPayload = '';

        function render(messages) {
            list.innerHTML = '';
            for (const m of messages) {
                const div = document.createElement('div');
                div.className = 'msg' + (m.user_id === ME ? ' mine' : '');

                const who = document.createElement('div');
                who.className = 'who';
                who.textContent = m.user.name + ' · ' + new Date(m.created_at).toLocaleTimeString('vi-VN');
                div.appendChild(who);

                if (m.body) {
                    const body = document.createElement('div');
                    body.className = 'body';
                    body.textContent = m.body;
                    div.appendChild(body);
                }

                if (m.file_path) {
                    const link = document.createElement('a');
                    link.className = 'file';
                    link.href = `/messages/${m.id}/download`;
                    link.target = '_blank';
                    link.textContent = '📎 ' + m.file_name;
                    div.appendChild(link);

                    if (m.summary) {
                        const summary = document.createElement('div');
                        summary.className = 'summary';
                        summary.textContent = m.summary;
                        div.appendChild(summary);
                    } else {
                        const btn = document.createElement('button');
                        btn.className = 'summarize';
                        btn.textContent = 'Tóm tắt';
                        btn.onclick = () => summarize(m.id, btn);
                        div.appendChild(btn);
                    }
                }

                list.appendChild(div);
            }
        }

        async function load() {
            const res = await fetch('{{ route('messages.fetch') }}', { headers: HEADERS });
            if (!res.ok) return;
            const text = await res.text();
            if (text === lastPayload) return;
            lastPayload = text;
            const atBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 60;
            render(JSON.parse(text));
            if (atBottom) list.scrollTop = list.scrollHeight;
        }

        async function summarize(id, btn) {
            btn.disabled = true;
            btn.textContent = 'Đang tóm tắt...';
            const res = await fetch(`/messages/${id}/summarize`, { method: 'POST', headers: HEADERS });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                alert(err.message || 'Tóm tắt thất bại.');
                btn.disabled = false;
                btn.textContent = 'Tóm tắt';
                return;
            }
            if (res.status !== 202) { // đã có sẵn summary
                await reload();
            }
            // 202 = job đã vào queue, đợi ChatUpdated broadcast render lại
        }

        async function reload() {
            lastPayload = '';
            await load();
        }

        document.getElementById('composer').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!bodyInput.value.trim() && !fileInput.files.length) return;
            const form = new FormData();
            if (bodyInput.value.trim()) form.append('body', bodyInput.value.trim());
            if (fileInput.files.length) form.append('file', fileInput.files[0]);
            const res = await fetch('{{ route('messages.store') }}', { method: 'POST', headers: HEADERS, body: form });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                alert(err.message || 'Gửi thất bại.');
                return;
            }
            bodyInput.value = '';
            fileInput.value = '';
            fileInput.dispatchEvent(new Event('change'));
            await reload();
            list.scrollTop = list.scrollHeight;
        });

        fileInput.addEventListener('change', () => {
            document.getElementById('file-label').classList.toggle('has-file', fileInput.files.length > 0);
        });

        bodyInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('composer').requestSubmit();
            }
        });

        load().then(() => { list.scrollTop = list.scrollHeight; });
        setInterval(load, 15000); // poll dự phòng khi websocket rớt

        if (typeof Echo !== 'undefined' && typeof Pusher !== 'undefined') {
            window.Pusher = Pusher;
            const echo = new Echo({
                broadcaster: 'reverb',
                key: '{{ config('broadcasting.connections.reverb.key') }}',
                wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
                wsPort: {{ (int) config('broadcasting.connections.reverb.options.port') }},
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: { headers: { 'X-CSRF-TOKEN': CSRF } },
            });
            echo.private('chat').listen('ChatUpdated', reload);
        }
    </script>
</body>
</html>
