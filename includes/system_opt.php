<?php
declare(strict_types=1);

function opt_cache_dir(): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function opt_perf_log_path(): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir . DIRECTORY_SEPARATOR . 'perf.log';
}

function opt_perf_start(): float
{
    return microtime(true);
}

function opt_perf_log(string $scope, float $start, array $meta = []): void
{
    $line = json_encode([
        'ts' => date('c'),
        'scope' => $scope,
        'elapsed_ms' => round((microtime(true) - $start) * 1000, 2),
        'meta' => $meta,
    ], JSON_UNESCAPED_SLASHES);
    if ($line !== false) {
        @file_put_contents(opt_perf_log_path(), $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function opt_cache_file(string $namespace, string $key): string
{
    return opt_cache_dir() . DIRECTORY_SEPARATOR . preg_replace('/[^a-zA-Z0-9._-]/', '_', $namespace . '_' . $key) . '.json';
}

function opt_cache_get(string $namespace, string $key, int $ttlSeconds): ?array
{
    $file = opt_cache_file($namespace, $key);
    if (!is_file($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false) {
        return null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !isset($decoded['ts']) || !array_key_exists('payload', $decoded)) {
        return null;
    }
    if ((time() - (int) $decoded['ts']) > $ttlSeconds) {
        return null;
    }
    return $decoded['payload'];
}

function opt_cache_set(string $namespace, string $key, array $payload): void
{
    $file = opt_cache_file($namespace, $key);
    $body = json_encode(['ts' => time(), 'payload' => $payload], JSON_UNESCAPED_SLASHES);
    if ($body !== false) {
        @file_put_contents($file, $body, LOCK_EX);
    }
}

function opt_feature_enabled(mysqli $conn, string $settingKey, bool $default = false): bool
{
    $stmt = $conn->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
    if (!$stmt) {
        return $default;
    }
    $stmt->bind_param('s', $settingKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return $default;
    }
    return in_array(strtolower(trim((string) $row['setting_value'])), ['1', 'true', 'yes', 'on'], true);
}

