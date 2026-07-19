# Starts the PharmaCare server hidden, waits for it to respond, opens the browser.
$ProjectRoot = "C:\Users\WDAGUtilityAccount\Desktop\pharmacare-laravel"
Set-Location $ProjectRoot

$port = 8000
$url = "http://127.0.0.1:$port"

# Start php artisan serve completely hidden, no console window.
Start-Process -FilePath "php" -ArgumentList "artisan","serve","--port=$port" -WindowStyle Hidden

# Wait for the server to actually come up before opening the browser.
$maxTries = 20
$ready = $false
for ($i = 0; $i -lt $maxTries; $i++) {
    Start-Sleep -Milliseconds 500
    try {
        $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 1
        if ($resp.StatusCode -eq 200) { $ready = $true; break }
    } catch {
        # Not up yet, keep waiting.
    }
}

Start-Process $url
