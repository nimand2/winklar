<?php
declare(strict_types=1);
namespace App\Core;

/** A shared, locked file stores rate limits across sessions and API clients. */
final class LoginLimiter
{
    public function __construct(private readonly ?string $directory = null) {}

    public function attempt(string $login, string $ip, ?int $now = null): bool
    {
        $now ??= time();
        $directory = $this->directory ?? (getenv('LOGIN_LIMIT_DIR') ?: sys_get_temp_dir() . '/winklar-login-' . substr(hash('sha256', dirname(__DIR__, 2)), 0, 16));
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new \RuntimeException('Login-Schutz nicht verfügbar.');
        $path = $directory . '/limits.json';
        $stream = fopen($path, 'c+');
        if ($stream === false) throw new \RuntimeException('Login-Schutz nicht verfügbar.');
        try {
            if (!flock($stream, LOCK_EX)) throw new \RuntimeException('Login-Schutz nicht verfügbar.');
            $data = json_decode(stream_get_contents($stream), true) ?: [];
            foreach ($data as $key => $entry) if (($entry['until'] ?? 0) <= $now) unset($data[$key]);
            $keys = [hash('sha256', 'ip|' . $ip) => 50, hash('sha256', 'login|' . $ip . '|' . mb_strtolower($login, 'UTF-8')) => 10];
            $allowed = true;
            foreach ($keys as $key => $limit) if (($data[$key]['count'] ?? 0) >= $limit) $allowed = false;
            if ($allowed) {
                foreach ($keys as $key => $limit) {
                    $data[$key] = ['until' => $data[$key]['until'] ?? ($now + 900), 'count' => ($data[$key]['count'] ?? 0) + 1];
                }
            }
            rewind($stream);
            if (!ftruncate($stream, 0) || fwrite($stream, json_encode($data, JSON_THROW_ON_ERROR)) === false || !fflush($stream)) throw new \RuntimeException('Login-Schutz nicht verfügbar.');
            return $allowed;
        } finally {
            flock($stream, LOCK_UN);
            fclose($stream);
        }
    }
}
