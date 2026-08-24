<?php

namespace App\Console\Commands;

use App\Services\MachineFingerprint;
use Illuminate\Console\Command;

/**
 * Run once, by installer.ps1, right after a successful install.
 * Writes storage/license.lock, binding this software to the current
 * machine's hardware fingerprint.
 *
 * Usage: php artisan license:generate
 */
class GenerateLicenseLock extends Command
{
    protected $signature = 'license:generate {--force : Overwrite an existing license.lock}';

    protected $description = 'Bind this installation to the current machine (generates storage/license.lock)';

    public function handle(): int
    {
        $lockPath = storage_path('license.lock');

        if (file_exists($lockPath) && ! $this->option('force')) {
            $this->error('license.lock already exists. This machine is already activated.');
            $this->line('Use --force only if you intend to re-bind this install (e.g. re-running the installer on the same PC).');

            return self::FAILURE;
        }

        $fingerprint = MachineFingerprint::current();
        $parts = MachineFingerprint::currentParts();

        // Salted with APP_KEY so the lock file alone (without this specific
        // Laravel install's app key) can't be reused/forged elsewhere.
        $salt = config('app.key');
        $signed = hash('sha256', $fingerprint.'|'.$salt);

        $payload = [
            'fingerprint' => $fingerprint,
            'signed' => $signed,
            'board_hint' => substr($parts['board'], 0, 8),
            'disk_hint' => substr($parts['disk'], 0, 8),
            'activated_at' => now()->toIso8601String(),
        ];

        file_put_contents($lockPath, json_encode($payload, JSON_PRETTY_PRINT));

        $this->info('license.lock created. This installation is now bound to this machine.');

        return self::SUCCESS;
    }
}
