
<?php
session_start();

require_once __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

$token = (string)($_POST['csrf_token'] ?? '');

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    $_SESSION['product_error'] = "Cerere invalidă.";
    header("Location: products.php");
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['product_error'] = "Produs invalid.";
    header("Location: products.php");
    exit;
}

try {

    // Nu stergem produse care au deja istoric de stoc.
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM stock_movements
         WHERE product_id = ?"
    );

    $stmt->execute([$id]);

    if ((int)$stmt->fetchColumn() > 0) {
        $_SESSION['product_error'] =
            "Produsul are mișcări de stoc și nu poate fi șters.";

        header("Location: products.php");
        exit;
    }

    $stmt = $pdo->prepare(
        "DELETE FROM products WHERE id = ?"
    );

    $stmt->execute([$id]);

    header("Location: products.php?status=deleted");
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    $_SESSION['product_error'] =
        "Nu s-a putut șterge produsul.";

    header("Location: products.php");
    exit;
}