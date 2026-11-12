
<?php

require_once __DIR__ . "/auth.php";

/**
 * Controlul accesului in StockFlow.
 *
 * Administrator:
 * - Acces la toate functiile
 *
 * Angajat:
 * - Dashboard
 * - Vizualizare produse
 * - Miscari de stoc
 * - Schimbarea propriei parole
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
