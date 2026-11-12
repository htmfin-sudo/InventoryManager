
<?php

require_once __DIR__ . "/includes/auth.php";
sf_require_login();

require_once __DIR__ . "/config/db.php";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

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

// PRODUSE SI STOCURI
$sql = "
    SELECT
        p.id,
        p.name,
        p.price,
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
        p.id, p.name, p.price, p.min_stock

    ORDER BY p.name
";

$products = $pdo->query($sql)
    ->fetchAll(PDO::FETCH_ASSOC);

// CALCUL STATISTICI
$totalStock = 0;
$lowStockCount = 0;
$inventoryValue = 0;

$stockLabels = [];
$stockValues = [];

foreach ($products as $product) {

    $stock = (int)$product['current_stock'];
    $minimum = (int)$product['min_stock'];
    $price = (float)$product['price'];

    $totalStock += $stock;
    $inventoryValue += $stock * $price;

    if ($stock < $minimum) {
        $lowStockCount++;
    }

    // Pentru lizibilitate afisam
    // maximum 12 produse in grafic.
    if (count($stockLabels) < 12) {
        $stockLabels[] = $product['name'];
        $stockValues[] = $stock;
    }
}

// DISTRIBUTIA PE CATEGORII
$categoryRows = $pdo->query(
    "SELECT
        c.name,
        COUNT(p.id) AS total

     FROM categories c

     LEFT JOIN products p
        ON p.category_id = c.id

     GROUP BY c.id, c.name
     ORDER BY c.name"
)->fetchAll(PDO::FETCH_ASSOC);

$categoryLabels = [];
$categoryValues = [];

foreach ($categoryRows as $row) {
    $categoryLabels[] = $row['name'];
    $categoryValues[] = (int)$row['total'];
}

// INTRARI SI IESIRI
$movementRows = $pdo->query(
    "SELECT type, SUM(quantity) AS total
     FROM stock_movements
     GROUP BY type"
)->fetchAll(PDO::FETCH_ASSOC);

$flowTotals = [
    'IN' => 0,
    'OUT' => 0
];

foreach ($movementRows as $row) {
    if (isset($flowTotals[$row['type']])) {
        $flowTotals[$row['type']] =
            (int)$row['total'];
    }
}

// DATE PENTRU GRAFICE
// Codificare sigura pentru includerea in JavaScript
$chartData = [
    'stockLabels' => $stockLabels,
    'stockValues' => $stockValues,
    'categoryLabels' => $categoryLabels,
    'categoryValues' => $categoryValues,
    'flowValues' => [
        $flowTotals['IN'],
        $flowTotals['OUT']
    ]
];

$chartJson = json_encode(
    $chartData,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_INVALID_UTF8_SUBSTITUTE
);

?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Dashboard Analytics - StockFlow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        .chart-container {
            position: relative;
            height: 330px;
            width: 100%;
        }

        .analytics-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .analytics-card .card-body {
            padding: 24px;
        }

        @media (max-width: 767px) {
            .chart-container {
                height: 290px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- MENIUL COMUN -->
        <?php
        require __DIR__ . "/includes/sidebar.php";
        ?>

        <main class="col-md-9 col-lg-10 main-content">

            <div class="mb-4">

                <h1 class="fw-bold">
                    Dashboard Analytics
                </h1>

                <p class="text-secondary mb-0">
                    Bine ai venit,
                    <?= e($_SESSION['user_name']) ?>!
                    Monitorizează performanța inventarului.
                </p>

            </div>

            <!-- STATISTICI GENERALE -->
            <div class="row g-4">

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <p>Total produse</p>
                        <h2><?= $totalProducts ?></h2>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <p>Categorii</p>
                        <h2><?= $totalCategories ?></h2>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <p>Furnizori</p>
                        <h2><?= $totalSuppliers ?></h2>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card">
                        <p>Mișcări de stoc</p>
                        <h2><?= $totalMovements ?></h2>
                    </div>
                </div>

            </div>

            <!-- STATISTICI AVANSATE -->
            <div class="row g-4 mt-1">

                <div class="col-md-4">
                    <div class="stat-card">

                        <p>Unități disponibile</p>

                        <h2 class="text-success">
                            <?= $totalStock ?>
                        </h2>

                        <small class="text-secondary">
                            Total bucăți în depozit
                        </small>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">

                        <p>Produse cu stoc scăzut</p>

                        <h2 class="text-danger">
                            <?= $lowStockCount ?>
                        </h2>

                        <small class="text-secondary">
                            Produse sub stocul minim
                        </small>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">

                        <p>Valoarea inventarului</p>

                        <h2 class="text-primary">
                            <?= number_format(
                                $inventoryValue,
                                2,
                                ',',
                                '.'
                            ) ?> lei
                        </h2>

                        <small class="text-secondary">
                            Preț produs × stoc disponibil
                        </small>

                    </div>
                </div>

            </div>

            <!-- GRAFICE -->
            <h3 class="fw-bold mt-5 mb-4">
                Analiza inventarului
            </h3>

            <div id="chartsWarning"
                 class="alert alert-warning d-none">
                Graficele nu s-au încărcat.
                Verifică conexiunea la internet.
            </div>

            <div class="row g-4">

                <!-- GRAFIC STOC -->
                <div class="col-xl-7">

                    <div class="card analytics-card h-100">

                        <div class="card-body">

                            <h5 class="fw-bold mb-2">
                                Stocul produselor
                            </h5>

                            <p class="text-secondary small">
                                Cantități disponibile pentru
                                primele 12 produse
                            </p>

                            <?php if (!empty($products)): ?>

                                <div class="chart-container">
                                    <canvas
                                        id="stockChart">
                                    </canvas>
                                </div>

                            <?php else: ?>

                                <p class="text-secondary">
                                    Nu există produse.
                                </p>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <!-- GRAFIC CATEGORII -->
                <div class="col-xl-5">

                    <div class="card analytics-card h-100">

                        <div class="card-body">

                            <h5 class="fw-bold mb-2">
                                Distribuția pe categorii
                            </h5>

                            <p class="text-secondary small">
                                Numărul de produse din
                                fiecare categorie
                            </p>

                            <?php if ($totalProducts > 0): ?>

                                <div class="chart-container">
                                    <canvas
                                        id="categoryChart">
                                    </canvas>
                                </div>

                            <?php else: ?>

                                <p class="text-secondary">
                                    Nu există produse.
                                </p>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <!-- GRAFIC MISCARI -->
                <div class="col-12">

                    <div class="card analytics-card">

                        <div class="card-body">

                            <h5 class="fw-bold mb-2">
                                Intrări vs. ieșiri
                            </h5>

                            <p class="text-secondary small">
                                Totalul cantităților
                                intrate și ieșite din depozit
                            </p>

                            <div class="chart-container">
                                <canvas
                                    id="movementChart">
                                </canvas>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- SITUATIA STOCURILOR -->
            <div class="card analytics-card mt-4 mb-4">

                <div class="card-body">

                    <h4 class="mb-3">
                        Situația stocurilor
                    </h4>

                    <div class="table-responsive">

                        <table
                            class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Produs</th>
                                    <th>Preț</th>
                                    <th>Stoc actual</th>
                                    <th>Stoc minim</th>
                                    <th>Valoare</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($products as $p): ?>

                                <?php
                                $stock =
                                    (int)$p['current_stock'];

                                $minimum =
                                    (int)$p['min_stock'];

                                $price =
                                    (float)$p['price'];

                                $value = $stock * $price;

                                $lowStock =
                                    $stock < $minimum;
                                ?>

                                <tr>

                                    <td>
                                        <?= e($p['name']) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $price, 2, ',', '.'
                                        ) ?> lei
                                    </td>

                                    <td class="fw-bold">
                                        <?= $stock ?>
                                    </td>

                                    <td>
                                        <?= $minimum ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $value, 2, ',', '.'
                                        ) ?> lei
                                    </td>

                                    <td>

                                        <?php if ($lowStock): ?>

                                            <span
                                                class="badge bg-danger">
                                                Stoc scăzut
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="badge bg-success">
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

<!-- BIBLIOTECA CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>

<script>

// Datele vin din MySQL prin PHP.
const dashboardData = <?= $chartJson ?>;

if (typeof Chart === "undefined") {

    document.getElementById("chartsWarning")
        .classList.remove("d-none");

} else {

    Chart.defaults.font.family = "Arial";

    // GRAFIC STOCURI
    const stockCanvas =
        document.getElementById("stockChart");

    if (stockCanvas) {

        new Chart(stockCanvas, {
            type: "bar",

            data: {
                labels: dashboardData.stockLabels,

                datasets: [{
                    label: "Stoc disponibil",
                    data: dashboardData.stockValues,
                    backgroundColor: "#2563eb",
                    borderRadius: 6
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: "y",

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // GRAFIC CATEGORII
    const categoryCanvas =
        document.getElementById("categoryChart");

    if (categoryCanvas) {

        const colors = [
            "#2563eb",
            "#16a34a",
            "#f59e0b",
            "#9333ea",
            "#ef4444",
            "#06b6d4",
            "#64748b"
        ];

        new Chart(categoryCanvas, {
            type: "doughnut",

            data: {
                labels: dashboardData.categoryLabels,

                datasets: [{
                    data: dashboardData.categoryValues,
                    backgroundColor:
                        dashboardData.categoryLabels.map(
                            (_, i) => colors[i % colors.length]
                        ),
                    borderWidth: 2
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "65%",

                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    }

    // GRAFIC INTRARI SI IESIRI
    const movementCanvas =
        document.getElementById("movementChart");

    if (movementCanvas) {

        new Chart(movementCanvas, {
            type: "bar",

            data: {
                labels: [
                    "Intrări în stoc",
                    "Ieșiri din stoc"
                ],

                datasets: [{
                    label: "Unități",
                    data: dashboardData.flowValues,
                    backgroundColor: [
                        "#16a34a",
                        "#ef4444"
                    ],
                    borderRadius: 8,
                    maxBarThickness: 100
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
}

</script>

</body>
</html>
