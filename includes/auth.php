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

/**
 * Checks if the user has been inactive for too long and logs them out.
 */
function check_session_timeout(bool $is_api = false): void 
{
    $timeout_duration = 1800; // 1800 seconds = 30 minutes of inactivity

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        session_unset();     // unset $_SESSION variable for the run-time 
        session_destroy();   // destroy session data in storage
        
        if ($is_api) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Session expired. Please log in again.']);
            exit;
        } else {
            header('Location: ' . app_url('login.php?session_expired=1'));
            exit;
        }
    }
    // Update last activity time stamp
    $_SESSION['LAST_ACTIVITY'] = time(); 
}

function require_login(): void
{
    if (empty($_SESSION['loggedin'])) {
        header('Location: ' . app_url('login.php'));
        exit;
    }
    check_session_timeout(); // Enforce timeout
}

function require_admin(): void
{
    if (empty($_SESSION['loggedin']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . app_url('login.php'));
        exit;
    }
    check_session_timeout(); // Enforce timeout
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
    check_session_timeout(true); // Enforce timeout for API
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
    check_session_timeout(); // Enforce timeout
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
    check_session_timeout(true); // Enforce timeout for API
}