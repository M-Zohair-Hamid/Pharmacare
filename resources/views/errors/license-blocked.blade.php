<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PharmaCare - License Notice</title>
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
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 40px;
            max-width: 440px;
            text-align: center;
        }
        .icon {
            font-size: 40px;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 20px;
            color: #1f2937;
            margin: 0 0 12px;
        }
        p {
            color: #4b5563;
            line-height: 1.5;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔒</div>
        <h1>PharmaCare is not activated on this device</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
