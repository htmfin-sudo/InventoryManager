
<?php

/**
 * StockFlow - Exemplu configurare baza de date
 *
 * Copiaza acest fisier ca db.php.
 * Completeaza datele de conectare locale.
 */

// Protectia paginilor
require_once __DIR__ . "/../includes/guard.php";

// Date de conectare - exemplu XAMPP local
$host = "localhost";
$dbname = "inventory_manager";
$username = "root";
$password = "";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false
        ]
    );

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    exit(
        "Eroare la conectarea bazei de date."
    );
}
