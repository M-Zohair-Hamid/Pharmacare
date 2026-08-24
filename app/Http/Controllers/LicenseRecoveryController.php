<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Dev-only override entry point. Not linked anywhere in the app UI.
 * Visiting /license-recovery lets the developer (and only the developer,
 * since it requires the master key) temporarily bypass the hardware
 * license check for this browser session - e.g. to support the client
 * on an unregistered/replacement machine, or during testing.
 *
 * The master key itself is never stored anywhere. Only its bcrypt hash
 * lives in .env as LICENSE_MASTER_HASH.
 */
class LicenseRecoveryController extends Controller
{
    public function show()
    {
        return view('license.recovery');
    }

    public function attempt(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $rateLimitKey = 'license-recovery:'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning('License recovery rate-limited', ['ip' => $request->ip()]);

            return back()->withErrors(['key' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $storedHash = config('services.license.master_hash');

        if (! $storedHash || ! password_verify($request->input('key'), $storedHash)) {
            RateLimiter::hit($rateLimitKey, 900); // 15 minutes

            Log::warning('License recovery failed attempt', ['ip' => $request->ip()]);

            return back()->withErrors(['key' => 'Invalid key.']);
        }

        RateLimiter::clear($rateLimitKey);

        // Override active for this browser session only, for 3 hours.
        session([
            'license_override' => true,
            'license_override_expires' => now()->addHours(3)->timestamp,
        ]);

        Log::info('License override activated', ['ip' => $request->ip()]);

        return redirect('/')->with('status', 'Override active for this session (3 hours).');
    }
}