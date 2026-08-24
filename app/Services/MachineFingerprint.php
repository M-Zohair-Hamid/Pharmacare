<?php

namespace App\Services;

/**
 * Computes a stable identifier for the physical machine this app is running
 * on. Used to bind the software license to a single device (see the
 * Software License Agreement, Clause 3.4 / 8).
 *
 * Deliberately combines TWO hardware sources so that replacing a single
 * component (e.g. a disk swap) doesn't silently invalidate the license -
 * see machineChanged() below for the tolerant comparison logic.
 */
class MachineFingerprint
{
    /**
     * Returns a stable hash representing this machine.
     * Windows only (matches the installer's target OS).
     */
    public static function current(): string
    {
        $boardUuid = self::motherboardUuid();
        $diskSerial = self::systemDiskSerial();

        $raw = trim($boardUuid).'|'.trim($diskSerial);

        return hash('sha256', $raw);
    }

    /**
     * Returns the two raw hardware values separately, so an "override"/
     * transfer decision can tell WHICH one changed rather than just
     * failing on any change at all.
     */
    public static function currentParts(): array
    {
        return [
            'board' => trim(self::motherboardUuid()),
            'disk' => trim(self::systemDiskSerial()),
        ];
    }

    protected static function motherboardUuid(): string
    {
        return self::runPowerShell(
            "(Get-CimInstance -ClassName Win32_ComputerSystemProduct).UUID"
        ) ?: 'unknown-board';
    }

    protected static function systemDiskSerial(): string
    {
        // Serial of the disk holding the Windows system drive (index 0
        // in the vast majority of single-disk pharmacy PCs).
        return self::runPowerShell(
            "(Get-CimInstance -ClassName Win32_PhysicalMedia | Select-Object -First 1).SerialNumber"
        ) ?: 'unknown-disk';
    }

    protected static function runPowerShell(string $command): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        $escaped = str_replace('"', '`"', $command);
        $output = @shell_exec("powershell -NoProfile -Command \"{$escaped}\" 2>NUL");

        if ($output === null) {
            return null;
        }

        $output = trim($output);

        return $output !== '' ? $output : null;
    }
}