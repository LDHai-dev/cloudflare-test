<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { font-family: system-ui, sans-serif; background: #f1f5f9; display: grid; place-items: center; min-height: 100vh; }
        form { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.08); width: 320px; display: grid; gap: .75rem; }
        h1 { font-size: 1.25rem; margin-bottom: .5rem; }
        input { padding: .6rem .75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; }
        button { padding: .6rem; border: 0; border-radius: 8px; background: #2563eb; color: #fff; font-size: 1rem; cursor: pointer; }
        .error { color: #dc2626; font-size: .875rem; }
        .hint { color: #64748b; font-size: .8rem; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <h1>Đăng nhập chat</h1>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autofocus>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button>Đăng nhập</button>
        <div class="hint">user1@example.com / user2@example.com — mật khẩu: password</div>
    </form>
</body>
</html>
