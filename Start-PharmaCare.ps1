# Starts the PharmaCare server hidden, waits for it to respond, opens the browser.
# Uses $PSScriptRoot (this script's own folder) so it keeps working even if
# the whole project folder gets moved or copied to a different path/client PC.
$ProjectRoot = $PSScriptRoot
Set-Location $ProjectRoot

$port = 8000
$url = "http://127.0.0.1:$port"

# Apply any pending database migrations before starting the server, so
# updates that add new columns/tables (like the discount feature) never
# leave the app pointing at an out-of-date schema.
Start-Process -FilePath "php" -ArgumentList "artisan","migrate","--force" -WindowStyle Hidden -Wait

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
