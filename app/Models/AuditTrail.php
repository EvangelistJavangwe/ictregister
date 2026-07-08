<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'username', 'action', 'module',
        'description', 'ip_address', 'mac_address', 'computer_name',
        'user_agent', 'badge_status', 'record_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public static function log(
        string $action,
        string $module,
        string $description,
        string $badgeStatus = 'info',
        ?string $recordId = null
    ): void {
        $user = auth()->user();
        $ip   = self::resolveIp();

        self::create([
            'user_id'       => $user?->id,
            'username'      => $user?->username ?? 'Guest',
            'action'        => $action,
            'module'        => $module,
            'description'   => $description,
            'ip_address'    => $ip,
            'mac_address'   => self::resolveMac($ip),
            'computer_name' => self::resolveHostname($ip),
            'user_agent'    => request()->userAgent(),
            'badge_status'  => $badgeStatus,
            'record_id'     => $recordId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Get the real client IP, honouring common proxy/load-balancer headers. */
    private static function resolveIp(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_REAL_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For can be a comma-separated list; take the first
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? (request()->ip() ?? '');

        // ::1 or 127.0.0.1 means the request came from the same machine as the server.
        // Resolve the server's own LAN IP so the audit trail shows the real network address.
        if (in_array($ip, ['::1', '127.0.0.1', ''])) {
            $hostname = gethostname();
            if ($hostname) {
                $lanIp = gethostbyname($hostname);
                if ($lanIp && $lanIp !== $hostname && filter_var($lanIp, FILTER_VALIDATE_IP)) {
                    return $lanIp;
                }
            }
        }

        return $ip ?: '0.0.0.0';
    }

    /**
     * Attempt an ARP lookup to get the MAC address for a LAN IP.
     * Works on both Windows (XAMPP) and Linux/Mac servers on the same subnet.
     * Returns null for localhost or if the lookup fails.
     */
    private static function resolveMac(string $ip): ?string
    {
        // Skip loopback / unresolved — resolveIp() already replaced these
        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) {
            return null;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        try {
            $safeIp = escapeshellarg($ip);
            $cmd    = PHP_OS_FAMILY === 'Windows'
                ? "arp -a {$safeIp}"
                : "arp -n {$safeIp}";

            $lines = [];
            exec($cmd, $lines);

            foreach ($lines as $line) {
                // Windows: 00-1A-2B-3C-4D-5E   Linux: 00:1a:2b:3c:4d:5e
                if (preg_match('/([0-9a-f]{2}[:\-]){5}[0-9a-f]{2}/i', $line, $m)) {
                    // Normalise to XX:XX:XX:XX:XX:XX uppercase
                    return strtoupper(str_replace('-', ':', $m[0]));
                }
            }
        } catch (\Throwable) {
            // Silent — ARP may be unavailable or exec() disabled
        }

        return null;
    }

    /**
     * Reverse-DNS the IP to get the computer's network hostname.
     * For localhost, returns the server's own hostname.
     */
    private static function resolveHostname(string $ip): ?string
    {
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return gethostname() ?: null;
        }

        try {
            $host = gethostbyaddr($ip);
            // gethostbyaddr returns the IP unchanged when no PTR record exists
            return ($host && $host !== $ip) ? $host : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
