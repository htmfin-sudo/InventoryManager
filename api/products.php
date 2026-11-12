
<?php

require_once __DIR__ . '/../includes/auth.php';
sf_require_login();

require_once __DIR__ . '/../config/db.php';

// API REST - StockFlow
// Versiunea 1: citire produse prin GET

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function jsonResponse(int $status, array $data): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

// Permitem numai GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');

    jsonResponse(405, [
        'success' => false,
        'message' => 'Metoda HTTP nu este permisa.'
    ]);
}

// Citim si validam parametrii
foreach (['id', 'q', 'category_id', 'stock', 'limit'] as $key) {
    if (isset($_GET[$key]) && !is_string($_GET[$key])) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Parametru invalid: ' . $key
        ]);
    }
}

$id = null;
$categoryId = null;

if (isset($_GET['id'])) {
    $id = filter_var(
        $_GET['id'],
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id < 1) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'ID invalid.'
        ]);
    }
}

if (isset($_GET['category_id'])) {
    $categoryId = filter_var(
        $_GET['category_id'],
        FILTER_VALIDATE_INT
    );

    if ($categoryId === false || $categoryId < 1) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Categorie invalida.'
        ]);
    }
}

$search = trim($_GET['q'] ?? '');

if (mb_strlen($search) > 100) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Cautarea este prea lunga.'
    ]);
}

$stockFilter = $_GET['stock'] ?? '';

if (!in_array($stockFilter, ['', 'low', 'ok'], true)) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Filtru de stoc invalid.'
    ]);
}

$limitText = $_GET['limit'] ?? '50';

if (
    !ctype_digit($limitText) ||
    (int)$limitText < 1 ||
    (int)$limitText > 100
) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Limita trebuie sa fie intre 1 si 100.'
    ]);
}

$limit = (int)$limitText;

// Interogare produse si stoc real
$sql = "
    SELECT
        p.id,
        p.name,
        p.category_id,
        p.supplier_id,
        p.price,
        p.min_stock,
        c.name AS category,
        s.name AS supplier,
        COALESCE(st.current_stock, 0) AS current_stock

    FROM products p

    JOIN categories c
        ON c.id = p.category_id

    JOIN suppliers s
        ON s.id = p.supplier_id

    LEFT JOIN (
        SELECT
            product_id,
            SUM(
                CASE
                    WHEN type = 'IN' THEN quantity
                    WHEN type = 'OUT' THEN -quantity
                    ELSE 0
                END
            ) AS current_stock
        FROM stock_movements
        GROUP BY product_id
    ) st ON st.product_id = p.id
";

$where = [];
$params = [];

if ($id !== null) {
    $where[] = "p.id = :id";
    $params[':id'] = $id;
}

if ($search !== '') {
    $where[] = "p.name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if ($categoryId !== null) {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $categoryId;
}

if ($stockFilter === 'low') {
    $where[] =
        "COALESCE(st.current_stock, 0) < p.min_stock";
}

if ($stockFilter === 'ok') {
    $where[] =
        "COALESCE(st.current_stock, 0) >= p.min_stock";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.id DESC LIMIT :limit";

try {
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(
            $key,
            $value,
            is_int($value)
                ? PDO::PARAM_INT
                : PDO::PARAM_STR
        );
    }

    $stmt->bindValue(
        ':limit',
        $id !== null ? 1 : $limit,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {
    error_log($exception->getMessage());

    jsonResponse(500, [
        'success' => false,
        'message' => 'Eroare interna API.'
    ]);
}

// Pregatim raspunsul JSON
$products = [];

foreach ($rows as $row) {
    $stock = (int)$row['current_stock'];
    $minimum = (int)$row['min_stock'];

    $products[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'category_id' => (int)$row['category_id'],
        'category' => $row['category'],
        'supplier_id' => (int)$row['supplier_id'],
        'supplier' => $row['supplier'],
        'price' => (string)$row['price'],
        'currency' => 'RON',
        'current_stock' => $stock,
        'min_stock' => $minimum,
        'stock_status' => $stock < $minimum
            ? 'low'
            : 'ok'
    ];
}

// Cautarea dupa un singur ID
if ($id !== null) {
    if (empty($products)) {
        jsonResponse(404, [
            'success' => false,
            'message' => 'Produsul nu a fost gasit.'
        ]);
    }

    jsonResponse(200, [
        'success' => true,
        'data' => $products[0]
    ]);
}

// Lista produselor
jsonResponse(200, [
    'success' => true,
    'count' => count($products),
    'data' => $products
]);
