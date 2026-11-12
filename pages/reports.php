
<?php
require_once __DIR__ . '/../includes/auth.php';
sf_require_admin();

require_once __DIR__ . '/../config/db.php';

function e($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function validDate($value): string {
    $value = trim((string)$value);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value
    );

    return $date && $date->format('Y-m-d') === $value
        ? $value
        : '';
}

// Protectie impotriva formulelor din CSV
function csvSafe($value): string {
    $value = (string)$value;

    if (preg_match(
        '/^[=+\-@]/u',
        ltrim($value, " \t\r\n")
    )) {
        return "'" . $value;
    }

    return $value;
}

// FILTRE
$search = mb_substr(
    trim((string)($_GET['search'] ?? '')),
    0,
    100
);

$categoryId = filter_var(
    $_GET['category_id'] ?? null,
    FILTER_VALIDATE_INT
);

$supplierId = filter_var(
    $_GET['supplier_id'] ?? null,
    FILTER_VALIDATE_INT
);

$stockStatus = (string)(
    $_GET['stock_status'] ?? ''
);

$movementType = (string)(
    $_GET['movement_type'] ?? ''
);

$dateFrom = validDate(
    $_GET['date_from'] ?? ''
);

$dateTo = validDate(
    $_GET['date_to'] ?? ''
);

$export = (string)($_GET['export'] ?? '');

if (!in_array(
    $stockStatus,
    ['', 'low', 'ok'],
    true
)) {
    $stockStatus = '';
}

if (!in_array(
    $movementType,
    ['', 'IN', 'OUT'],
    true
)) {
    $movementType = '';
}

if (
    $categoryId === false ||
    $categoryId === null ||
    $categoryId < 1
) {
    $categoryId = null;
}

if (
    $supplierId === false ||
    $supplierId === null ||
    $supplierId < 1
) {
    $supplierId = null;
}

// CATEGORII SI FURNIZORI
$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query(
    "SELECT id, name FROM suppliers ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// RAPORT PRODUSE CU STOC REAL
$productSql = "
    SELECT
        p.id,
        p.name,
        c.name AS category,
        s.name AS supplier,
        p.price,
        p.min_stock,
        COALESCE(
            st.current_stock, 0
        ) AS current_stock

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
                    WHEN type = 'IN'
                        THEN quantity
                    ELSE -quantity
                END
            ) AS current_stock
        FROM stock_movements
        GROUP BY product_id
    ) st ON st.product_id = p.id
";

$productWhere = [];
$productParams = [];

if ($search !== '') {
    $productWhere[] = "p.name LIKE :search";
    $productParams[':search'] = '%' . $search . '%';
}

if ($categoryId !== null) {
    $productWhere[] = "p.category_id = :category_id";
    $productParams[':category_id'] = $categoryId;
}

if ($supplierId !== null) {
    $productWhere[] = "p.supplier_id = :supplier_id";
    $productParams[':supplier_id'] = $supplierId;
}

if ($stockStatus === 'low') {
    $productWhere[] =
        "COALESCE(st.current_stock, 0) < p.min_stock";
}

if ($stockStatus === 'ok') {
    $productWhere[] =
        "COALESCE(st.current_stock, 0) >= p.min_stock";
}

if (!empty($productWhere)) {
    $productSql .=
        " WHERE " . implode(" AND ", $productWhere);
}

$productSql .= " ORDER BY p.name";

$stmt = $pdo->prepare($productSql);
$stmt->execute($productParams);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// RAPORT MISCARI DE STOC
$movementSql = "
    SELECT
        sm.id,
        sm.created_at,
        p.name AS product_name,
        sm.type,
        sm.quantity,
        u.name AS operator_name

    FROM stock_movements sm

    JOIN products p
        ON p.id = sm.product_id

    JOIN users u
        ON u.id = sm.user_id
";

$movementWhere = [];
$movementParams = [];

if ($movementType !== '') {
    $movementWhere[] = "sm.type = :movement_type";
    $movementParams[':movement_type'] = $movementType;
}

if ($dateFrom !== '') {
    $movementWhere[] =
        "sm.created_at >= :date_from";

    $movementParams[':date_from'] =
        $dateFrom . " 00:00:00";
}

if ($dateTo !== '') {
    $movementWhere[] =
        "sm.created_at < :date_to";

    $movementParams[':date_to'] =
        (new DateTimeImmutable($dateTo))
            ->modify('+1 day')
            ->format('Y-m-d 00:00:00');
}

if (!empty($movementWhere)) {
    $movementSql .=
        " WHERE " . implode(" AND ", $movementWhere);
}

$movementSql .= " ORDER BY sm.id DESC";

$stmt = $pdo->prepare($movementSql);
$stmt->execute($movementParams);

$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EXPORT CSV
if (in_array(
    $export,
    ['products', 'movements'],
    true
)) {

    header(
        'Content-Type: text/csv; charset=UTF-8'
    );

    header(
        'Content-Disposition: attachment; filename="stockflow_' .
        $export . '_' . date('Ymd_His') . '.csv"'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate'
    );

    $out = fopen('php://output', 'w');

    // UTF-8 BOM pentru Excel
    fwrite($out, "\xEF\xBB\xBF");

    if ($export === 'products') {

        fputcsv($out, [
            'ID',
            'Produs',
            'Categorie',
            'Furnizor',
            'Pret (lei)',
            'Stoc actual',
            'Stoc minim',
            'Valoare (lei)',
            'Status'
        ], ';', '"', '');

        foreach ($products as $p) {

            $stock = (int)$p['current_stock'];

            fputcsv($out, [
                (int)$p['id'],
                csvSafe($p['name']),
                csvSafe($p['category']),
                csvSafe($p['supplier']),
                number_format(
                    (float)$p['price'],
                    2, '.', ''
                ),
                $stock,
                (int)$p['min_stock'],
                number_format(
                    $stock * (float)$p['price'],
                    2, '.', ''
                ),
                $stock < (int)$p['min_stock']
                    ? 'Stoc scazut'
                    : 'Stoc OK'
            ], ';', '"', '');
        }

    } else {

        fputcsv($out, [
            'ID',
            'Data',
            'Produs',
            'Tip',
            'Cantitate',
            'Operator'
        ], ';', '"', '');

        foreach ($movements as $m) {

            fputcsv($out, [
                (int)$m['id'],
                $m['created_at'],
                csvSafe($m['product_name']),
                $m['type'] === 'IN'
                    ? 'Intrare'
                    : 'Iesire',
                (int)$m['quantity'],
                csvSafe($m['operator_name'])
            ], ';', '"', '');
        }
    }

    fclose($out);
    exit;
}

// STATISTICI RAPORT
$totalUnits = 0;
$lowStock = 0;
$totalValue = 0.0;

foreach ($products as $p) {

    $stock = (int)$p['current_stock'];

    $totalUnits += $stock;

    $totalValue +=
        $stock * (float)$p['price'];

    if ($stock < (int)$p['min_stock']) {
        $lowStock++;
    }
}

$inQty = 0;
$outQty = 0;

foreach ($movements as $m) {
    if ($m['type'] === 'IN') {
        $inQty += (int)$m['quantity'];
    } else {
        $outQty += (int)$m['quantity'];
    }
}

// PASTRAM FILTRELE LA EXPORT
$filterParams = [];

if ($search !== '') {
    $filterParams['search'] = $search;
}

if ($categoryId !== null) {
    $filterParams['category_id'] = $categoryId;
}

if ($supplierId !== null) {
    $filterParams['supplier_id'] = $supplierId;
}

if ($stockStatus !== '') {
    $filterParams['stock_status'] = $stockStatus;
}

if ($movementType !== '') {
    $filterParams['movement_type'] = $movementType;
}

if ($dateFrom !== '') {
    $filterParams['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $filterParams['date_to'] = $dateTo;
}

$csvProductsUrl = 'reports.php?' . http_build_query(
    $filterParams + ['export' => 'products']
);

$csvMovementsUrl = 'reports.php?' . http_build_query(
    $filterParams + ['export' => 'movements']
);
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Rapoarte - StockFlow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="../css/style.css">

    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            body {
                background: white !important;
                font-size: 10pt;
            }

            .sidebar,
            .no-print {
                display: none !important;
            }

            main.main-content {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                margin-bottom: 12px !important;
                break-inside: auto;
            }

            .card-body {
                padding: 12px !important;
            }

            table {
                font-size: 9pt;
            }

            th, td {
                padding: 4px !important;
            }

            tr {
                break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            .table-responsive {
                overflow: visible !important;
            }

            .badge {
                color: black !important;
                background: transparent !important;
                border: 1px solid #999;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- MENIU COMUN -->
        <?php
        require __DIR__ . '/../includes/sidebar.php';
        ?>

        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="fw-bold mb-1">
                Rapoarte și exporturi
            </h1>

            <p class="text-secondary mb-4">
                Raport generat la
                <?= e(date('d.m.Y H:i')) ?>.
                Acces: Administrator.
            </p>

            <!-- FORMULAR FILTRE -->
            <div class="card border-0 shadow-sm mb-4 no-print">
                <div class="card-body p-4">

                    <h4 class="mb-3">Filtre</h4>

                    <form method="GET" action="reports.php">

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label">
                                    Caută produs
                                </label>

                                <input
                                    type="search"
                                    name="search"
                                    maxlength="100"
                                    class="form-control"
                                    value="<?= e($search) ?>"
                                    placeholder="Nume produs">
                            </div>

                            <div class="col-md-4">
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

                            <div class="col-md-4">
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

                            <div class="col-md-4">
                                <label class="form-label">
                                    Status stoc
                                </label>

                                <select
                                    name="stock_status"
                                    class="form-select">

                                    <option value="">
                                        Toate produsele
                                    </option>

                                    <option
                                        value="low"
                                        <?= $stockStatus === 'low' ? 'selected' : '' ?>>
                                        Stoc scăzut
                                    </option>

                                    <option
                                        value="ok"
                                        <?= $stockStatus === 'ok' ? 'selected' : '' ?>>
                                        Stoc OK
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Tip mișcare
                                </label>

                                <select
                                    name="movement_type"
                                    class="form-select">

                                    <option value="">
                                        Intrări și ieșiri
                                    </option>

                                    <option
                                        value="IN"
                                        <?= $movementType === 'IN' ? 'selected' : '' ?>>
                                        Doar intrări
                                    </option>

                                    <option
                                        value="OUT"
                                        <?= $movementType === 'OUT' ? 'selected' : '' ?>>
                                        Doar ieșiri
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    De la data
                                </label>

                                <input
                                    type="date"
                                    name="date_from"
                                    class="form-control"
                                    value="<?= e($dateFrom) ?>">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Până la data
                                </label>

                                <input
                                    type="date"
                                    name="date_to"
                                    class="form-control"
                                    value="<?= e($dateTo) ?>">
                            </div>

                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">

                            <button
                                type="submit"
                                class="btn btn-primary">
                                Aplică filtre
                            </button>

                            <a
                                href="reports.php"
                                class="btn btn-outline-secondary">
                                Resetează
                            </a>

                        </div>

                    </form>

                    <hr>

                    <!-- EXPORTURI -->
                    <div class="d-flex flex-wrap gap-2">

                        <a
                            href="<?= e($csvProductsUrl) ?>"
                            class="btn btn-success">
                            Descarcă produse CSV
                        </a>

                        <a
                            href="<?= e($csvMovementsUrl) ?>"
                            class="btn btn-success">
                            Descarcă mișcări CSV
                        </a>

                        <button
                            type="button"
                            onclick="window.print()"
                            class="btn btn-dark">
                            Printează / Salvează PDF
                        </button>

                    </div>

                    <small class="text-secondary d-block mt-2">
                        Pentru PDF, selectează „Save as PDF”
                        din fereastra de imprimare.
                        Filtrele pentru produse și mișcări
                        se aplică separat.
                    </small>

                </div>
            </div>

            <!-- STATISTICI -->
            <div class="row g-3 mb-4">

                <div class="col-md-3">
                    <div class="stat-card">
                        <p>Produse găsite</p>
                        <h2><?= count($products) ?></h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <p>Unități disponibile</p>
                        <h2><?= $totalUnits ?></h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <p>Stoc scăzut</p>
                        <h2 class="text-danger">
                            <?= $lowStock ?>
                        </h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <p>Valoarea inventarului</p>
                        <h2 class="fs-4">
                            <?= number_format(
                                $totalValue, 2, ',', '.'
                            ) ?> lei
                        </h2>
                    </div>
                </div>

            </div>

            <!-- RAPORT PRODUSE -->
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Raport produse
                    </h4>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Produs</th>
                                    <th>Categorie</th>
                                    <th>Furnizor</th>
                                    <th>Preț</th>
                                    <th>Stoc</th>
                                    <th>Stoc minim</th>
                                    <th>Valoare</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($products as $p): ?>

                                <?php
                                $stock = (int)$p['current_stock'];
                                $min = (int)$p['min_stock'];
                                ?>

                                <tr>
                                    <td><?= e($p['name']) ?></td>
                                    <td><?= e($p['category']) ?></td>
                                    <td><?= e($p['supplier']) ?></td>

                                    <td>
                                        <?= number_format(
                                            (float)$p['price'],
                                            2, ',', '.'
                                        ) ?> lei
                                    </td>

                                    <td><?= $stock ?></td>
                                    <td><?= $min ?></td>

                                    <td>
                                        <?= number_format(
                                            $stock * (float)$p['price'],
                                            2, ',', '.'
                                        ) ?> lei
                                    </td>

                                    <td>
                                        <span class="badge <?= $stock < $min ? 'bg-danger' : 'bg-success' ?>">
                                            <?= $stock < $min ? 'Stoc scăzut' : 'Stoc OK' ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="alert alert-info">
                            Nu există produse pentru filtrele alese.
                        </div>
                    <?php endif; ?>

                </div>
            </section>

            <!-- RAPORT MISCARI -->
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

                        <h4 class="mb-0">
                            Raport mișcări de stoc
                        </h4>

                        <span class="text-secondary">
                            <?= count($movements) ?> operațiuni
                            · Intrări: <?= $inQty ?>
                            · Ieșiri: <?= $outQty ?>
                        </span>

                    </div>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Produs</th>
                                    <th>Tip</th>
                                    <th>Cantitate</th>
                                    <th>Operator</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($movements as $m): ?>

                                <tr>
                                    <td>
                                        <?= e($m['created_at']) ?>
                                    </td>

                                    <td>
                                        <?= e($m['product_name']) ?>
                                    </td>

                                    <td>
                                        <?= $m['type'] === 'IN'
                                            ? 'Intrare'
                                            : 'Ieșire' ?>
                                    </td>

                                    <td>
                                        <?= (int)$m['quantity'] ?>
                                    </td>

                                    <td>
                                        <?= e($m['operator_name']) ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>
                    </div>

                    <?php if (empty($movements)): ?>
                        <div class="alert alert-info">
                            Nu există mișcări în perioada selectată.
                        </div>
                    <?php endif; ?>

                </div>
            </section>

        </main>

    </div>
</div>

</body>
</html>
