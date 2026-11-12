
<?php
// AUTENTIFICARE
require_once __DIR__ . "/includes/auth.php";

// Blocheaza vizitatorii neautentificati
sf_require_login();

// BAZA DE DATE
require_once __DIR__ . "/config/db.php";

// STATISTICI GENERALE
$totalProducts = (int)$pdo
    ->query("SELECT COUNT(*) FROM products")
    ->fetchColumn();

$totalCategories = (int)$pdo
    ->query("SELECT COUNT(*) FROM categories")
    ->fetchColumn();

$totalSuppliers = (int)$pdo
    ->query("SELECT COUNT(*) FROM suppliers")
    ->fetchColumn();

$totalMovements = (int)$pdo
    ->query("SELECT COUNT(*) FROM stock_movements")
    ->fetchColumn();

// STOCURI REALE
$sql = "
    SELECT
        p.id,
        p.name,
        p.min_stock,
        COALESCE(
            SUM(
                CASE
                    WHEN sm.type = 'IN' THEN sm.quantity
                    WHEN sm.type = 'OUT' THEN -sm.quantity
                    ELSE 0
                END
            ), 0
        ) AS current_stock

    FROM products p

    LEFT JOIN stock_movements sm
        ON sm.product_id = p.id

    GROUP BY
        p.id,
        p.name,
        p.min_stock

    ORDER BY p.name
";

$products = $pdo->query($sql)
    ->fetchAll(PDO::FETCH_ASSOC);

// CALCUL TOTAL STOC
$totalStock = 0;
$lowStockCount = 0;

foreach ($products as $product) {

    $stock = (int)$product['current_stock'];
    $minimum = (int)$product['min_stock'];

    $totalStock += $stock;

    if ($stock < $minimum) {
        $lowStockCount++;
    }
}

// SECURIZARE AFISARE TEXT
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Dashboard - StockFlow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container-fluid">

    <div class="row">

        <!-- MENIU COMUN -->
        <?php
        require __DIR__ . "/includes/sidebar.php";
        ?>

        <!-- DASHBOARD -->
        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="fw-bold">
                Dashboard
            </h1>

            <p class="text-secondary mb-4">

                Bine ai venit,
                <?= e($_SESSION['user_name']) ?>!

                Monitorizează inventarul firmei.

            </p>

            <!-- STATISTICI GENERALE -->
            <div class="row g-4">

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">

                        <p>Total produse</p>

                        <h2>
                            <?= $totalProducts ?>
                        </h2>

                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">

                        <p>Categorii</p>

                        <h2>
                            <?= $totalCategories ?>
                        </h2>

                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">

                        <p>Furnizori</p>

                        <h2>
                            <?= $totalSuppliers ?>
                        </h2>

                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">

                        <p>Mișcări de stoc</p>

                        <h2>
                            <?= $totalMovements ?>
                        </h2>

                    </div>
                </div>

            </div>

            <!-- STATISTICI STOC -->
            <div class="row g-4 mt-1">

                <div class="col-md-6">

                    <div class="stat-card">

                        <p>Unități disponibile</p>

                        <h2 class="text-success">
                            <?= $totalStock ?>
                        </h2>

                        <span class="text-secondary">
                            Total bucăți în depozit
                        </span>

                    </div>

                </div>

                <div class="col-md-6">

                    <div class="stat-card">

                        <p>Produse cu stoc scăzut</p>

                        <h2 class="text-danger">
                            <?= $lowStockCount ?>
                        </h2>

                        <span class="text-secondary">
                            Produse sub limita minimă
                        </span>

                    </div>

                </div>

            </div>

            <!-- TABEL STOCURI -->
            <div class="card border-0 shadow-sm mt-4">

                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Situația stocurilor
                    </h4>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Produs</th>
                                    <th>Stoc actual</th>
                                    <th>Stoc minim</th>
                                    <th>Status</th>
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

                                    <td>
                                        <?= e($p['name']) ?>
                                    </td>

                                    <td class="fw-bold">
                                        <?= $stock ?>
                                    </td>

                                    <td>
                                        <?= $minimum ?>
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

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                    <?php if (empty($products)): ?>

                        <div class="alert alert-info">
                            Nu există produse înregistrate.
                        </div>

                    <?php endif; ?>

                    <a
                        href="pages/stock_movements.php"
                        class="btn btn-primary mt-2">

                        Gestionează stocul

                    </a>

                </div>

            </div>

        </main>

    </div>

</div>

</body>
</html>
