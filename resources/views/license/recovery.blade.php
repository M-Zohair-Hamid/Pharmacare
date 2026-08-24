<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recovery</title>
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f4f5f7;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        form {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 32px;
            width: 320px;
        }
        input[type=password] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin: 12px 0;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #1f2937;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .error {
            color: #b91c1c;
            font-size: 13px;
            margin-top: 8px;
        }
        .status {
            color: #065f46;
            font-size: 13px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <form method="POST" action="{{ url('/license-recovery') }}">
        @csrf
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif
        <input type="password" name="key" placeholder="Access key" autofocus>
        <button type="submit">Continue</button>
        @error('key')
            <div class="error">{{ $message }}</div>
        @enderror
    </form>
</body>
</html>