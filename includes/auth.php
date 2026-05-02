<?php
/**
 * Central auth helpers: application URL base, login redirects, role gates.
 * Include after session_start() where needed.
 */

declare(strict_types=1);

function app_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $projectRoot = realpath(dirname(__DIR__));
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    if ($projectRoot && $docRoot) {
        $rootFs = str_replace('\\', '/', $projectRoot);
        $docFs = str_replace('\\', '/', $docRoot);
        if (str_starts_with($rootFs, $docFs)) {
            $base = '/' . trim(substr($rootFs, strlen($docFs)), '/');
            return $base;
        }
    }
    $base = '/plp-alumni-tracer';
    return $base;
}

function app_url(string $relativePath): string
{
    $rel = ltrim(str_replace('\\', '/', $relativePath), '/');
    return app_base_path() . '/' . $rel;
}

function require_login(): void
{
    if (empty($_SESSION['loggedin'])) {
        header('Location: ' . app_url('login.php'));
        exit;
    }
}

function require_admin(): void
{
    if (empty($_SESSION['loggedin']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . app_url('login.php'));
        exit;
    }
}

/**
 * JSON endpoints: no HTML redirect (safer for fetch/XHR).
 */
function require_admin_api(): void
{
    if (empty($_SESSION['loggedin']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }
}

/**
 * Alumni-only UI (admins use the admin panel).
 */
function require_alumni(): void
{
    if (empty($_SESSION['loggedin'])) {
        header('Location: ' . app_url('login.php'));
        exit;
    }
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: ' . app_url('admin/pages/dashboard.php'));
        exit;
    }
}

function require_alumni_api(): void
{
    if (empty($_SESSION['loggedin'])) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Login required.']);
        exit;
    }
    if (($_SESSION['role'] ?? '') === 'admin') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Use the admin panel for this account.']);
        exit;
    }
}
