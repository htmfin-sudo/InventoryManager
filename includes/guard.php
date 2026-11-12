
<?php

require_once __DIR__ . "/auth.php";

/**
 * Control acces StockFlow
 *
 * Administrator: toate paginile
 * Angajat: Dashboard, Produse,
 * Miscari de stoc, Schimbare parola, Logout
 */

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

    if ($path === '/InventoryManager/login.php') {
        return;
    }

    sf_require_login();

    $employeePages = [
        '/InventoryManager/index.php',
        '/InventoryManager/logout.php',
        '/InventoryManager/pages/products.php',
        '/InventoryManager/pages/stock_movements.php',
        '/InventoryManager/pages/change_password.php'
    ];

    if (in_array($path, $employeePages, true)) {
        return;
    }

    sf_require_admin();
}

sf_guard_request();
