<?php

namespace App\Http\Middleware;

use App\Services\MachineFingerprint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the application unless it's running on the machine it was
 * activated for (storage/license.lock) - OR a dev override session is
 * active (see LicenseRecoveryController).
 */
class CheckHardwareLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never block the recovery route itself - that would lock out
        // the only way back in.
        if ($request->is('license-recovery') || $request->is('license-recovery/*')) {
            return $next($request);
        }

        // Active dev override session bypasses the hardware check entirely.
        if (session('license_override') === true) {
            return $next($request);
        }

        $lockPath = storage_path('license.lock');

        if (! file_exists($lockPath)) {
            return $this->blocked('This installation has not been activated. Please contact PharmaCare support.');
        }

        $data = json_decode(file_get_contents($lockPath), true);

        if (! is_array($data) || empty($data['fingerprint']) || empty($data['signed'])) {
            return $this->blocked('License file is invalid or corrupted. Please contact PharmaCare support.');
        }

        $salt = config('app.key');
        $expectedSigned = hash('sha256', $data['fingerprint'].'|'.$salt);

        if (! hash_equals($expectedSigned, $data['signed'])) {
            // Lock file was tampered with / copied from another install.
            return $this->blocked('License verification failed. Please contact PharmaCare support.');
        }

        $currentFingerprint = MachineFingerprint::current();

        if (! hash_equals($data['fingerprint'], $currentFingerprint)) {
            return $this->blocked('This software is licensed for a different device. Please contact PharmaCare support to transfer your license.');
        }

        return $next($request);
    }

    protected function blocked(string $message): Response
    {
        return response()->view('errors.license-blocked', ['message' => $message], 403);
    }
}
