
<?php

// Gestionarea sesiunilor si autentificarii

function sf_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');

    $secure = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/InventoryManager/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function sf_csrf_token(): string
{
    sf_start_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function sf_is_authenticated(): bool
{
    sf_start_session();

    return isset(
        $_SESSION['user_id'],
        $_SESSION['user_role']
    );
}

function sf_require_login(): void
{
    if (!sf_is_authenticated()) {
        header(
            "Location: /InventoryManager/login.php"
        );
        exit;
    }
}

function sf_require_admin(): void
{
    sf_require_login();

    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        exit("Acces interzis.");
    }
}
