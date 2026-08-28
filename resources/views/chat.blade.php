<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat</title>
    <style>
        :root {
            --bg: #eef1f6;
            --surface: #ffffff;
            --surface-2: #f4f6fa;
            --border: #e3e8f0;
            --text: #1c2333;
            --muted: #6b7488;
            --primary: #4f46e5;
            --primary-soft: #eef0ff;
            --mine-bg: linear-gradient(135deg, #4f46e5, #6366f1);
            --mine-text: #ffffff;
            --shadow: 0 1px 2px rgba(16, 24, 40, .06), 0 1px 3px rgba(16, 24, 40, .08);
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f1117;
                --surface: #181b23;
                --surface-2: #1f2330;
                --border: #2a2f3d;
                --text: #e6e9f0;
                --muted: #8b93a7;
                --primary: #818cf8;
                --primary-soft: #262a45;
                --mine-bg: linear-gradient(135deg, #4f46e5, #7c3aed);
                --mine-text: #ffffff;
                --shadow: 0 1px 3px rgba(0, 0, 0, .4);
            }
        }
        * { box-sizing: border-box; margin: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100dvh;
            display: grid;
            place-items: center;
        }
        .app {
            width: min(720px, 100vw);
            height: min(100dvh, 900px);
            display: flex;
            flex-direction: column;
            background: var(--surface);
            overflow: hidden;
        }
        @media (min-width: 740px) {
            .app { border-radius: 18px; box-shadow: 0 8px 40px rgba(16, 24, 40, .12); height: min(92dvh, 900px); border: 1px solid var(--border); }
        }

        header {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .8rem 1rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }
        .avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: grid; place-items: center;
            background: var(--mine-bg);
            color: #fff;
            font-weight: 600;
            flex-shrink: 0;
        }
        header .info { line-height: 1.25; }
        header .info strong { font-size: .95rem; }
        header .info .mode { display: block; font-size: .72rem; color: var(--muted); }
        header form { margin-left: auto; }
        header button {
            border: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--muted);
            border-radius: 8px;
            padding: .4rem .7rem;
            font-size: .8rem;
            cursor: pointer;
        }
        header button:hover { color: var(--text); }

        main {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: .35rem;
            background: var(--bg);
        }
        .empty {
            margin: auto;
            text-align: center;
            color: var(--muted);
            font-size: .9rem;
        }
        .empty .big { font-size: 2rem; display: block; margin-bottom: .4rem; }

        .row { display: flex; gap: .5rem; align-items: flex-end; max-width: 78%; }
        .row.mine { align-self: flex-end; flex-direction: row-reverse; }
        .row .avatar { width: 28px; height: 28px; font-size: .75rem; margin-bottom: 2px; }
        .row.mine .avatar { display: none; }

        .msg {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px 16px 16px 4px;
            padding: .55rem .8rem;
            box-shadow: var(--shadow);
            min-width: 0;
        }
        .row.mine .msg {
            background: var(--mine-bg);
            border: 0;
            color: var(--mine-text);
            border-radius: 16px 16px 4px 16px;
        }
        .row.enter { animation: pop .25s ease; }
        @keyframes pop { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

        .msg .who { font-size: .68rem; color: var(--muted); margin-bottom: .15rem; }
        .row.mine .who { display: none; }
        .msg .body { white-space: pre-wrap; word-break: break-word; font-size: .93rem; line-height: 1.45; }
        .msg .time { font-size: .65rem; color: var(--muted); margin-top: .2rem; text-align: right; }
        .row.mine .time { color: rgba(255, 255, 255, .75); }

        .file-card {
            display: flex;
            align-items: center;
            gap: .55rem;
            margin-top: .35rem;
            padding: .5rem .65rem;
            border-radius: 10px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            text-decoration: none;
            color: inherit;
            max-width: 320px;
        }
        .row.mine .file-card { background: rgba(255, 255, 255, .14); border-color: rgba(255, 255, 255, .25); }
        .file-card .icon { font-size: 1.35rem; }
        .file-card .name { font-size: .82rem; font-weight: 500; word-break: break-all; }
        .file-card .hint { font-size: .68rem; color: var(--muted); }
        .row.mine .file-card .hint { color: rgba(255, 255, 255, .75); }

        button.summarize {
            margin-top: .4rem;
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent;
            border-radius: 999px;
            padding: .28rem .8rem;
            font-size: .76rem;
            cursor: pointer;
        }
        .row.mine button.summarize { border-color: rgba(255, 255, 255, .6); color: #fff; }
        button.summarize:disabled { opacity: .55; cursor: wait; }
        button.summarize.done { background: var(--primary-soft); border-color: transparent; }
        .row.mine button.summarize.done { background: rgba(255, 255, 255, .18); }

        .composer-wrap { border-top: 1px solid var(--border); background: var(--surface); padding: .65rem .8rem; }
        #chip {
            display: none;
            align-items: center;
            gap: .4rem;
            margin-bottom: .5rem;
            padding: .3rem .6rem;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 999px;
            font-size: .78rem;
            width: fit-content;
            max-width: 100%;
        }
        #chip.show { display: flex; }
        #chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        #chip button { border: 0; background: none; color: inherit; cursor: pointer; font-size: .9rem; line-height: 1; }
        .app { position: relative; }
        #files-panel {
            position: absolute;
            inset: 0;
            z-index: 10;
            background: var(--surface);
            display: flex;
            flex-direction: column;
        }
        #files-panel[hidden] { display: none; }
        .fp-head { display: flex; align-items: center; justify-content: space-between; padding: .9rem 1rem; border-bottom: 1px solid var(--border); }
        .fp-head button { border: 0; background: none; color: var(--muted); font-size: 1.05rem; cursor: pointer; }
        #fp-list { flex: 1; overflow-y: auto; padding: .5rem; }
        .fp-empty { text-align: center; color: var(--muted); font-size: .85rem; margin-top: 2.5rem; }
        .fp-row { display: flex; align-items: center; gap: .65rem; padding: .6rem; border-radius: 10px; }
        .fp-row:hover { background: var(--surface-2); }
        .fp-row .icon { font-size: 1.3rem; }
        .fp-row .meta { flex: 1; min-width: 0; }
        .fp-row a.name { display: block; font-size: .85rem; font-weight: 500; word-break: break-all; text-decoration: none; color: inherit; }
        .fp-row a.name:hover { color: var(--primary); }
        .fp-row .sub { font-size: .7rem; color: var(--muted); }
        .fp-row .state { font-size: .72rem; color: var(--primary); white-space: nowrap; }
        .fp-row input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }
        .state.view { border: 0; background: none; cursor: pointer; font-weight: 600; color: var(--primary); }
        .state.view:hover { text-decoration: underline; }
        #sum-dialog {
            margin: auto; /* reset * {margin:0} đè mất căn giữa mặc định của dialog */
            border: 0;
            border-radius: 14px;
            padding: 0;
            width: min(480px, calc(100vw - 2rem));
            background: var(--surface);
            color: var(--text);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .35);
        }
        #sum-dialog::backdrop { background: rgba(0, 0, 0, .45); }
        .sd-head { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .9rem 1rem; border-bottom: 1px solid var(--border); }
        .sd-head strong { font-size: .9rem; word-break: break-all; }
        .sd-head button { border: 0; background: none; color: var(--muted); cursor: pointer; font-size: 1rem; }
        .sd-label { padding: .75rem 1rem 0; font-size: .68rem; font-weight: 600; color: var(--primary); letter-spacing: .03em; }
        .sd-body { padding: .5rem 1rem 1rem; font-size: .85rem; line-height: 1.6; white-space: pre-wrap; max-height: 60vh; overflow-y: auto; }
        .fp-foot { padding: .75rem 1rem; border-top: 1px solid var(--border); }
        .fp-foot button { width: 100%; padding: .6rem; border: 0; border-radius: 10px; background: var(--mine-bg); color: #fff; font-weight: 600; font-size: .9rem; cursor: pointer; }
        .fp-foot button:disabled { opacity: .5; cursor: default; }
        form#composer { display: flex; gap: .5rem; align-items: flex-end; }
        #file { display: none; }
        .icon-btn {
            width: 40px; height: 40px;
            flex-shrink: 0;
            display: grid; place-items: center;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--muted);
            cursor: pointer;
            font-size: 1.05rem;
        }
        .icon-btn:hover { color: var(--primary); border-color: var(--primary); }
        #body {
            flex: 1;
            max-height: 120px;
            padding: .55rem .9rem;
            border: 1px solid var(--border);
            border-radius: 20px;
            font: inherit;
            font-size: .93rem;
            resize: none;
            background: var(--surface-2);
            color: var(--text);
            outline: none;
        }
        #body:focus { border-color: var(--primary); }
        #send {
            width: 40px; height: 40px;
            flex-shrink: 0;
            border: 0;
            border-radius: 50%;
            background: var(--mine-bg);
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            display: grid; place-items: center;
        }
        #send:disabled { opacity: .55; cursor: wait; }
    </style>
</head>
<body>
    <div class="app">
        <header>
            <div class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
            <div class="info">
                <strong>{{ auth()->user()->name }}</strong>
                <span class="mode">Tóm tắt: {{ config('services.ai.summarize_on_upload') ? 'tự động khi upload' : 'bấm nút' }}</span>
            </div>
            <button type="button" id="fp-open" style="margin-left:auto">📁 Tệp</button>
            <form method="POST" action="{{ route('logout') }}" style="margin-left:0">@csrf<button>Đăng xuất</button></form>
        </header>
        <main id="messages"></main>
        <div id="files-panel" hidden>
            <div class="fp-head"><strong>📁 Tệp đã gửi</strong><button type="button" id="fp-close" title="Đóng">✕</button></div>
            <div id="fp-list"></div>
            <div class="fp-foot"><button type="button" id="fp-run" disabled>✨ Tóm tắt đã chọn (0)</button></div>
        </div>
        <dialog id="sum-dialog">
            <div class="sd-head"><strong id="sd-title"></strong><button type="button" id="sd-close" title="Đóng">✕</button></div>
            <div class="sd-label">✨ TÓM TẮT AI</div>
            <div class="sd-body" id="sd-body"></div>
        </dialog>
        <div class="composer-wrap">
            <div id="chip"><span id="chip-name"></span><button type="button" id="chip-clear" title="Bỏ tệp">✕</button></div>
            <form id="composer">
                <label class="icon-btn" for="file" title="Đính kèm tệp">📎</label>
                <input type="file" id="file" multiple>
                <textarea id="body" rows="1" placeholder="Nhắn tin..."></textarea>
                <button id="send" title="Gửi">➤</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2/dist/echo.iife.js"></script>
    <script>
        const ME = {{ auth()->id() }};
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const HEADERS = { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF };
        const list = document.getElementById('messages');
        const fileInput = document.getElementById('file');
        const bodyInput = document.getElementById('body');
        const sendBtn = document.getElementById('send');
        const chip = document.getElementById('chip');
        let lastPayload = '';
        const seen = new Set();
        const selected = new Set(); // file cũ đang tick chọn để tóm tắt hàng loạt
        const pending = new Set();  // file đang chờ job tóm tắt chạy xong

        function markPending(id) {
            pending.add(id);
            setTimeout(() => { if (pending.delete(id)) reload(); }, 180000); // ponytail: job im lặng quá 3 phút thì reset nút
        }

        const ICONS = { pdf: '📕', docx: '📘', doc: '📘', txt: '📄', md: '📄', log: '📄', csv: '📊', xlsx: '📊', json: '🧾', xml: '🧾', html: '🌐', png: '🖼️', jpg: '🖼️', jpeg: '🖼️', gif: '🖼️', webp: '🖼️', zip: '🗜️' };
        const fileIcon = (name) => ICONS[(name.split('.').pop() || '').toLowerCase()] || '📎';
        const time = (iso) => new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

        let allMessages = [];

        function render(messages) {
            allMessages = messages;
            renderFiles();
            list.innerHTML = '';

            if (!messages.length) {
                const empty = document.createElement('div');
                empty.className = 'empty';
                empty.innerHTML = '<span class="big">💬</span>Chưa có tin nhắn nào.<br>Gửi lời chào hoặc đính kèm tệp để bắt đầu!';
                list.appendChild(empty);
                return;
            }

            for (const m of messages) {
                const row = document.createElement('div');
                row.className = 'row' + (m.user_id === ME ? ' mine' : '') + (seen.has(m.id) ? '' : ' enter');
                seen.add(m.id);

                const avatar = document.createElement('div');
                avatar.className = 'avatar';
                avatar.textContent = (m.user.name || '?').charAt(0);
                row.appendChild(avatar);

                const msg = document.createElement('div');
                msg.className = 'msg';

                const who = document.createElement('div');
                who.className = 'who';
                who.textContent = m.user.name;
                msg.appendChild(who);

                if (m.body) {
                    const body = document.createElement('div');
                    body.className = 'body';
                    body.textContent = m.body;
                    msg.appendChild(body);
                }

                if (m.file_path) {
                    const card = document.createElement('a');
                    card.className = 'file-card';
                    card.href = `/messages/${m.id}/download`;
                    card.target = '_blank';
                    const icon = document.createElement('div');
                    icon.className = 'icon';
                    icon.textContent = fileIcon(m.file_name || '');
                    const meta = document.createElement('div');
                    const name = document.createElement('div');
                    name.className = 'name';
                    name.textContent = m.file_name;
                    const hint = document.createElement('div');
                    hint.className = 'hint';
                    hint.textContent = 'Bấm để tải về';
                    meta.append(name, hint);
                    card.append(icon, meta);
                    msg.appendChild(card);

                    if (m.summary) {
                        pending.delete(m.id);
                        selected.delete(m.id);
                        const btn = document.createElement('button');
                        btn.className = 'summarize done';
                        btn.textContent = '✓ Xem tóm tắt';
                        btn.onclick = (e) => { e.preventDefault(); openSummary(m); };
                        msg.appendChild(btn);
                    } else if (pending.has(m.id)) {
                        const btn = document.createElement('button');
                        btn.className = 'summarize';
                        btn.disabled = true;
                        btn.textContent = 'Đang tóm tắt...';
                        msg.appendChild(btn);
                    } else {
                        const btn = document.createElement('button');
                        btn.className = 'summarize';
                        btn.textContent = '✨ Tóm tắt';
                        btn.onclick = (e) => { e.preventDefault(); summarize(m.id, btn); };
                        msg.appendChild(btn);
                    }
                }

                const timeEl = document.createElement('div');
                timeEl.className = 'time';
                timeEl.textContent = time(m.created_at);
                msg.appendChild(timeEl);

                row.appendChild(msg);
                list.appendChild(row);
            }
        }

        async function load() {
            const res = await fetch('{{ route('messages.fetch') }}', { headers: HEADERS });
            if (!res.ok) return;
            const text = await res.text();
            if (text === lastPayload) return;
            lastPayload = text;
            const atBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
            render(JSON.parse(text));
            if (atBottom) list.scrollTop = list.scrollHeight;
        }

        async function reload() {
            lastPayload = '';
            await load();
        }

        async function summarize(id, btn) {
            btn.disabled = true;
            btn.textContent = 'Đang tóm tắt...';
            const res = await fetch(`/messages/${id}/summarize`, { method: 'POST', headers: HEADERS });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                alert(err.message || 'Tóm tắt thất bại.');
                btn.disabled = false;
                btn.textContent = '✨ Tóm tắt';
                return;
            }
            if (res.status === 202) {
                markPending(id); // job đã vào queue, đợi ChatUpdated broadcast render lại
            } else { // đã có sẵn summary
                await reload();
            }
        }

        // ----- Panel "Tệp đã gửi": chọn nhiều file cũ rồi tóm tắt hàng loạt -----
        const fpPanel = document.getElementById('files-panel');
        const fpList = document.getElementById('fp-list');
        const fpRun = document.getElementById('fp-run');

        function renderFiles() {
            const rows = allMessages.filter((m) => m.file_path).reverse(); // mới nhất lên đầu
            fpList.innerHTML = '';

            if (!rows.length) {
                fpList.innerHTML = '<div class="fp-empty">Chưa có tệp nào được gửi.</div>';
            }

            for (const m of rows) {
                const row = document.createElement('div');
                row.className = 'fp-row';

                const cb = document.createElement('input');
                cb.type = 'checkbox';
                if (m.summary || pending.has(m.id)) {
                    cb.disabled = true;
                } else {
                    cb.checked = selected.has(m.id);
                    cb.onchange = () => { cb.checked ? selected.add(m.id) : selected.delete(m.id); updateFpRun(); };
                }
                row.appendChild(cb);

                const icon = document.createElement('div');
                icon.className = 'icon';
                icon.textContent = fileIcon(m.file_name || '');
                row.appendChild(icon);

                const meta = document.createElement('div');
                meta.className = 'meta';
                const name = document.createElement('a');
                name.className = 'name';
                name.href = `/messages/${m.id}/download`;
                name.target = '_blank';
                name.textContent = m.file_name;
                const sub = document.createElement('div');
                sub.className = 'sub';
                sub.textContent = m.user.name + ' · ' + time(m.created_at);
                meta.append(name, sub);
                row.appendChild(meta);

                if (m.summary) {
                    const view = document.createElement('button');
                    view.type = 'button';
                    view.className = 'state view';
                    view.textContent = '✓ Xem tóm tắt';
                    view.onclick = () => openSummary(m);
                    row.appendChild(view);
                } else {
                    const state = document.createElement('div');
                    state.className = 'state';
                    state.textContent = pending.has(m.id) ? 'Đang tóm tắt...' : '';
                    row.appendChild(state);
                }

                fpList.appendChild(row);
            }

            updateFpRun();
        }

        function updateFpRun() {
            fpRun.disabled = selected.size === 0;
            fpRun.textContent = `✨ Tóm tắt đã chọn (${selected.size})`;
        }

        fpRun.onclick = async () => {
            const ids = [...selected];
            if (!ids.length) return;
            ids.forEach(markPending);
            selected.clear();
            await reload();
            const res = await fetch('{{ route('messages.summarizeBatch') }}', {
                method: 'POST',
                headers: { ...HEADERS, 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids }),
            });
            if (!res.ok) {
                alert('Không gửi được yêu cầu tóm tắt.');
                ids.forEach((id) => pending.delete(id));
                await reload();
            }
        };

        document.getElementById('fp-open').onclick = () => { fpPanel.hidden = false; renderFiles(); };
        document.getElementById('fp-close').onclick = () => { fpPanel.hidden = true; };

        const sumDialog = document.getElementById('sum-dialog');

        function openSummary(m) {
            document.getElementById('sd-title').textContent = fileIcon(m.file_name || '') + ' ' + m.file_name;
            document.getElementById('sd-body').textContent = m.summary;
            sumDialog.showModal();
        }

        document.getElementById('sd-close').onclick = () => sumDialog.close();
        sumDialog.onclick = (e) => { if (e.target === sumDialog) sumDialog.close(); }; // bấm nền tối để đóng

        document.getElementById('composer').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!bodyInput.value.trim() && !fileInput.files.length) return;
            sendBtn.disabled = true;
            const form = new FormData();
            if (bodyInput.value.trim()) form.append('body', bodyInput.value.trim());
            for (const f of fileInput.files) form.append('files[]', f);
            const res = await fetch('{{ route('messages.store') }}', { method: 'POST', headers: HEADERS, body: form });
            sendBtn.disabled = false;
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                alert(err.message || 'Gửi thất bại.');
                return;
            }
            bodyInput.value = '';
            bodyInput.style.height = 'auto';
            fileInput.value = '';
            fileInput.dispatchEvent(new Event('change'));
            await reload();
            list.scrollTop = list.scrollHeight;
        });

        fileInput.addEventListener('change', () => {
            chip.classList.toggle('show', fileInput.files.length > 0);
            document.getElementById('chip-name').textContent = fileInput.files.length > 1
                ? fileInput.files.length + ' tệp đã chọn'
                : (fileInput.files[0]?.name || '');
        });
        document.getElementById('chip-clear').onclick = () => {
            fileInput.value = '';
            fileInput.dispatchEvent(new Event('change'));
        };

        bodyInput.addEventListener('input', () => {
            bodyInput.style.height = 'auto';
            bodyInput.style.height = Math.min(bodyInput.scrollHeight, 120) + 'px';
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
            const EchoClass = Echo.default ?? Echo; // bản IIFE expose class ở Echo.default
            const echo = new EchoClass({
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
