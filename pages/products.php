
<?php
require_once __DIR__ . "/../includes/auth.php";
sf_require_login();

require_once __DIR__ . "/../config/db.php";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

$error = $_SESSION['product_error'] ?? '';
unset($_SESSION['product_error']);

$status = $_GET['status'] ?? '';

// FILTRE
$search = mb_substr(
    trim((string)($_GET['search'] ?? '')),
    0,
    100
);

$categoryId = filter_var(
    $_GET['category_id'] ?? '',
    FILTER_VALIDATE_INT
);

$supplierId = filter_var(
    $_GET['supplier_id'] ?? '',
    FILTER_VALIDATE_INT
);

$stockFilter = $_GET['stock_filter'] ?? '';

if (!in_array($stockFilter, ['', 'low', 'ok'], true)) {
    $stockFilter = '';
}

// CATEGORII
$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// FURNIZORI
$suppliers = $pdo->query(
    "SELECT id, name FROM suppliers ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// PRODUSE SI STOCURI
$sql = "
    SELECT
        p.id,
        p.name,
        p.price,
        p.min_stock,
        c.name AS category,
        s.name AS supplier,
        COALESCE(st.current_stock, 0) AS current_stock

    FROM products p

    JOIN categories c ON c.id = p.category_id
    JOIN suppliers s ON s.id = p.supplier_id

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

if ($search !== '') {
    $where[] = "p.name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if ($categoryId !== false && $categoryId > 0) {
    $where[] = "p.category_id = :category";
    $params[':category'] = $categoryId;
}

if ($supplierId !== false && $supplierId > 0) {
    $where[] = "p.supplier_id = :supplier";
    $params[':supplier'] = $supplierId;
}

if ($stockFilter === 'low') {
    $where[] = "
        COALESCE(st.current_stock, 0) < p.min_stock
    ";
}

if ($stockFilter === 'ok') {
    $where[] = "
        COALESCE(st.current_stock, 0) >= p.min_stock
    ";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Produse - StockFlow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <?php
        require __DIR__ . "/../includes/sidebar.php";
        ?>

        <main class="col-md-9 col-lg-10 main-content">

            <div class="d-flex flex-wrap justify-content-between
                        align-items-center gap-3 mb-4">

                <div>
                    <h1 class="fw-bold mb-1">
                        Produse
                    </h1>

                    <p class="text-secondary mb-0">
                        Gestionează inventarul firmei.
                    </p>
                </div>

                <?php if ($isAdmin): ?>
                    <a href="add_product.php"
                       class="btn btn-primary">
                        + Adaugă produs
                    </a>
                <?php endif; ?>

            </div>

            <?php if ($status === 'updated'): ?>
                <div class="alert alert-success">
                    Produsul a fost modificat!
                </div>
            <?php elseif ($status === 'deleted'): ?>
                <div class="alert alert-success">
                    Produsul a fost șters!
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- CAUTARE SI FILTRARE -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Căutare și filtrare
                    </h4>

                    <form method="GET" action="products.php">

                        <div class="row g-3">

                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">
                                    Caută produs
                                </label>

                                <input
                                    type="search"
                                    name="search"
                                    class="form-control"
                                    maxlength="100"
                                    placeholder="Nume produs..."
                                    value="<?= e($search) ?>">
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">
                                    Categorie
                                </label>

                                <select
                                    name="category_id"
                                    class="form-select">

                                    <option value="">
                                        Toate categoriile
                                    </option>

                                    <?php foreach ($categories as $c): ?>
                                        <option
                                            value="<?= (int)$c['id'] ?>"
                                            <?= (int)$categoryId === (int)$c['id'] ? 'selected' : '' ?>>

                                            <?= e($c['name']) ?>

                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">
                                    Furnizor
                                </label>

                                <select
                                    name="supplier_id"
                                    class="form-select">

                                    <option value="">
                                        Toți furnizorii
                                    </option>

                                    <?php foreach ($suppliers as $s): ?>
                                        <option
                                            value="<?= (int)$s['id'] ?>"
                                            <?= (int)$supplierId === (int)$s['id'] ? 'selected' : '' ?>>

                                            <?= e($s['name']) ?>

                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 col-xl-3">
                                <label class="form-label">
                                    Status stoc
                                </label>

                                <select
                                    name="stock_filter"
                                    class="form-select">

                                    <option value="">
                                        Toate produsele
                                    </option>

                                    <option value="low"
                                        <?= $stockFilter === 'low' ? 'selected' : '' ?>>
                                        Stoc scăzut
                                    </option>

                                    <option value="ok"
                                        <?= $stockFilter === 'ok' ? 'selected' : '' ?>>
                                        Stoc OK
                                    </option>
                                </select>
                            </div>

                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">

                            <button type="submit"
                                    class="btn btn-primary">
                                Aplică filtre
                            </button>

                            <a href="products.php"
                               class="btn btn-outline-secondary">
                                Resetează filtrele
                            </a>

                        </div>
                    </form>

                </div>
            </div>

            <!-- TABEL PRODUSE -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between
                                align-items-center mb-3">

                        <h4 class="mb-0">
                            Lista produselor
                        </h4>

                        <span class="badge bg-primary">
                            <?= count($products) ?> rezultate
                        </span>

                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Produs</th>
                                    <th>Categorie</th>
                                    <th>Furnizor</th>
                                    <th>Preț</th>
                                    <th>Stoc</th>
                                    <th>Status</th>

                                    <?php if ($isAdmin): ?>
                                        <th>Acțiuni</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($products as $p): ?>

                                <?php
                                $stock = (int)$p['current_stock'];
                                $minimum = (int)$p['min_stock'];
                                $lowStock = $stock < $minimum;
                                ?>

                                <tr>
                                    <td><?= (int)$p['id'] ?></td>

                                    <td class="fw-semibold">
                                        <?= e($p['name']) ?>
                                    </td>

                                    <td><?= e($p['category']) ?></td>
                                    <td><?= e($p['supplier']) ?></td>

                                    <td>
                                        <?= number_format(
                                            (float)$p['price'],
                                            2
                                        ) ?> lei
                                    </td>

                                    <td class="fw-bold">
                                        <?= $stock ?>
                                    </td>

                                    <td>
                                        <?php if ($lowStock): ?>
                                            <span class="badge bg-danger">
                                                Stoc scăzut
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                Stoc OK
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <?php if ($isAdmin): ?>
                                        <td>
                                            <div class="d-flex gap-2">

                                                <a
                                                    href="edit_product.php?id=<?= (int)$p['id'] ?>"
                                                    class="btn btn-warning btn-sm">
                                                    Editează
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="delete_product.php"
                                                    onsubmit="return confirm('Sigur vrei să ștergi acest produs?');">

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int)$p['id'] ?>">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(sf_csrf_token()) ?>">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger btn-sm">
                                                        Șterge
                                                    </button>

                                                </form>

                                            </div>
                                        </td>
                                    <?php endif; ?>

                                </tr>
                            <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="alert alert-info">
                            Nu există produse pentru filtrele selectate.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>
