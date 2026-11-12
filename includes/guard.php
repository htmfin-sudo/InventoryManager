
<?php

require_once __DIR__ . '/auth.php';

function sf_guard_request(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $path = str_replace(
        "\\",
        "/",
        $_SERVER['SCRIPT_NAME'] ?? ''
    );

    // Pagina publica de autentificare
    if ($path === '/InventoryManager/login.php') {
        return;
    }

    // Toate celelalte pagini necesita login
    sf_require_login();

    // Pagini pentru Admin si Angajat
    $employeePages = [
        '/InventoryManager/index.php',
        '/InventoryManager/logout.php',
        '/InventoryManager/pages/products.php',
        '/InventoryManager/pages/stock_movements.php',
        '/InventoryManager/pages/change_password.php',
        '/InventoryManager/api/products.php'
    ];

    if (in_array($path, $employeePages, true)) {
        return;
    }

    // Restul paginilor necesita Administrator
    sf_require_admin();
}

sf_guard_request();
