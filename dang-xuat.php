<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $thamSoCookie = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $thamSoCookie['path'],
            'domain' => $thamSoCookie['domain'],
            'secure' => $thamSoCookie['secure'],
            'httponly' => $thamSoCookie['httponly'],
            'samesite' => $thamSoCookie['samesite'] ?? 'Lax',
        ]
    );
}

session_destroy();
header('Location: index.php', true, 303);
exit;
