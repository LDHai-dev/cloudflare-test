<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập</title>
    <style>
        :root {
            --bg: #eef1f6;
            --surface: #ffffff;
            --surface-2: #f4f6fa;
            --border: #e3e8f0;
            --text: #1c2333;
            --muted: #6b7488;
            --primary: #4f46e5;
            --mine-bg: linear-gradient(135deg, #4f46e5, #6366f1);
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
                --mine-bg: linear-gradient(135deg, #4f46e5, #7c3aed);
            }
        }
        * { box-sizing: border-box; margin: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: grid;
            place-items: center;
            min-height: 100dvh;
        }
        form {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 2.25rem 2rem;
            border-radius: 18px;
            box-shadow: 0 8px 40px rgba(16, 24, 40, .12);
            width: min(360px, calc(100vw - 2rem));
            display: grid;
            gap: .8rem;
        }
        .logo {
            width: 52px; height: 52px;
            border-radius: 16px;
            display: grid; place-items: center;
            background: var(--mine-bg);
            font-size: 1.5rem;
            margin: 0 auto .25rem;
        }
        h1 { font-size: 1.2rem; text-align: center; }
        .sub { text-align: center; color: var(--muted); font-size: .82rem; margin-bottom: .5rem; }
        input {
            padding: .65rem .85rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: .95rem;
            background: var(--surface-2);
            color: var(--text);
            outline: none;
        }
        input:focus { border-color: var(--primary); }
        button {
            padding: .65rem;
            border: 0;
            border-radius: 10px;
            background: var(--mine-bg);
            color: #fff;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { filter: brightness(1.08); }
        .error { color: #ef4444; font-size: .82rem; text-align: center; }
        .hint { color: var(--muted); font-size: .75rem; text-align: center; line-height: 1.5; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <div class="logo">💬</div>
        <h1>Đăng nhập chat</h1>
        <div class="sub">Chat 2 người · upload tệp · tóm tắt AI</div>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autofocus>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button>Đăng nhập</button>
        <div class="hint">user1@example.com / user2@example.com<br>mật khẩu: <b>password</b></div>
    </form>
</body>
</html>
