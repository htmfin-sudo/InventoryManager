
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

$csrfToken = sf_csrf_token();
$userId = (int)$_SESSION['user_id'];

$error = "";
$status = $_GET['status'] ?? "";

// Verificam utilizatorul autentificat
$stmt = $pdo->prepare(
    "SELECT id, name, role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([$userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$currentUser ||
    !in_array(
        $currentUser['role'],
        ['admin', 'user'],
        true
    )
) {
    http_response_code(403);
    exit("Contul nu mai este valid.");
}

// INREGISTRARE MISCARE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = (string)($_POST['csrf_token'] ?? '');

    $productId = filter_var(
        $_POST['product_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $type = $_POST['type'] ?? '';

    $quantity = filter_var(
        $_POST['quantity'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!hash_equals($csrfToken, $token)) {

        $error = "Cerere invalidă.";

    } elseif (
        !$productId ||
        $productId < 1 ||
        !in_array($type, ['IN', 'OUT'], true) ||
        $quantity === false ||
        $quantity === null ||
        $quantity < 1 ||
        $quantity > 1000000000
    ) {

        $error = "Verifică produsul și cantitatea.";

    } else {

        try {

            $pdo->beginTransaction();

            // Blocam produsul pentru operatii simultane
            $stmt = $pdo->prepare(
                "SELECT id, name
                 FROM products
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$productId]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException(
                    "Produsul nu există."
                );
            }

            // Calculam stocul disponibil
            $stmt = $pdo->prepare(
                "SELECT COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'IN'
                                THEN quantity
                            WHEN type = 'OUT'
                                THEN -quantity
                            ELSE 0
                        END
                    ), 0
                )
                FROM stock_movements
                WHERE product_id = ?"
            );

            $stmt->execute([$productId]);

            $currentStock = (int)$stmt->fetchColumn();

            // Nu permitem stoc negativ
            if (
                $type === 'OUT' &&
                $quantity > $currentStock
            ) {
                throw new RuntimeException(
                    "Stoc insuficient! Disponibil: " .
                    $currentStock . " bucăți."
                );
            }

            // Salvam operatia cu utilizatorul conectat
            $stmt = $pdo->prepare(
                "INSERT INTO stock_movements
                 (product_id, user_id, type, quantity)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $productId,
                $userId,
                $type,
                $quantity
            ]);

            $pdo->commit();

            header(
                "Location: stock_movements.php?status=added"
            );
            exit;

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($exception instanceof RuntimeException) {
                $error = $exception->getMessage();
            } else {
                error_log($exception->getMessage());
                $error = "Operația nu a putut fi salvată.";
            }
        }
    }
}

// STOCURI REALE
$products = $pdo->query(
    "SELECT
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
     GROUP BY p.id, p.name, p.min_stock
     ORDER BY p.name"
)->fetchAll(PDO::FETCH_ASSOC);

// ISTORIC OPERATIUNI
$movements = $pdo->query(
    "SELECT
        sm.id,
        sm.type,
        sm.quantity,
        sm.created_at,
        p.name AS product_name,
        u.name AS user_name
     FROM stock_movements sm
     JOIN products p ON p.id = sm.product_id
     JOIN users u ON u.id = sm.user_id
     ORDER BY sm.id DESC
     LIMIT 100"
)->fetchAll(PDO::FETCH_ASSOC);

// STATISTICI
$totalStock = 0;
$lowStockCount = 0;

foreach ($products as $p) {

    $stock = (int)$p['current_stock'];
    $minimum = (int)$p['min_stock'];

    $totalStock += $stock;

    if ($stock < $minimum) {
        $lowStockCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Mișcări de stoc - StockFlow</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- MENIU COMUN -->
        <?php
        require __DIR__ . "/../includes/sidebar.php";
        ?>

        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="fw-bold mb-2">
                Mișcări de stoc
            </h1>

            <p class="text-secondary mb-4">
                Operațiuni efectuate de:
                <strong>
                    <?= e($currentUser['name']) ?>
                </strong>
            </p>

            <?php if ($status === 'added'): ?>
                <div class="alert alert-success">
                    Mișcarea a fost înregistrată cu succes!
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- CARDURI STATISTICI -->
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="stat-card">
                        <p>Total produse</p>
                        <h2><?= count($products) ?></h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">
                        <p>Unități disponibile</p>
                        <h2 class="text-success">
                            <?= $totalStock ?>
                        </h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">
                        <p>Produse cu stoc scăzut</p>
                        <h2 class="text-danger">
                            <?= $lowStockCount ?>
                        </h2>
                    </div>
                </div>

            </div>

            <!-- FORMULAR -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Înregistrează mișcare
                    </h4>

                    <form method="POST" id="movementForm">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken) ?>">

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label">
                                    Produs
                                </label>

                                <select
                                    name="product_id"
                                    id="productId"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        Selectează produsul
                                    </option>

                                    <?php foreach ($products as $p): ?>

                                        <option
                                            value="<?= (int)$p['id'] ?>"
                                            data-stock="<?= (int)$p['current_stock'] ?>">

                                            <?= e($p['name']) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Tip mișcare
                                </label>

                                <select
                                    name="type"
                                    id="movementType"
                                    class="form-select"
                                    required>

                                    <option value="IN">
                                        Intrare în stoc
                                    </option>

                                    <option value="OUT">
                                        Ieșire din stoc
                                    </option>

                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Cantitate
                                </label>

                                <input
                                    type="number"
                                    name="quantity"
                                    id="quantity"
                                    class="form-control"
                                    min="1"
                                    max="1000000000"
                                    step="1"
                                    required>
                            </div>

                        </div>

                        <p id="stockInfo"
                           class="text-secondary mt-3">
                            Selectează un produs.
                        </p>

                        <button type="submit"
                                class="btn btn-primary">
                            Salvează mișcarea
                        </button>

                    </form>
                </div>
            </div>

            <!-- STOC DISPONIBIL -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Stoc disponibil
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
                                $low = $stock < $minimum;
                                ?>

                                <tr>
                                    <td><?= e($p['name']) ?></td>
                                    <td class="fw-bold"><?= $stock ?></td>
                                    <td><?= $minimum ?></td>

                                    <td>
                                        <?php if ($low): ?>
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

                </div>
            </div>

            <!-- ISTORIC -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Istoric mișcări
                    </h4>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Produs</th>
                                    <th>Tip</th>
                                    <th>Cantitate</th>
                                    <th>Operator</th>
                                    <th>Data</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($movements as $m): ?>

                                <tr>
                                    <td><?= (int)$m['id'] ?></td>

                                    <td>
                                        <?= e($m['product_name']) ?>
                                    </td>

                                    <td>
                                        <?php if ($m['type'] === 'IN'): ?>
                                            <span class="badge bg-success">
                                                Intrare
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                Ieșire
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="fw-bold">
                                        <?= $m['type'] === 'IN' ? '+' : '-' ?>
                                        <?= (int)$m['quantity'] ?>
                                    </td>

                                    <td>
                                        <?= e($m['user_name']) ?>
                                    </td>

                                    <td>
                                        <?= e($m['created_at']) ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                    <?php if (empty($movements)): ?>
                        <div class="alert alert-info">
                            Nu există mișcări înregistrate.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </main>
    </div>
</div>

<script>
const productSelect =
    document.getElementById("productId");

const typeSelect =
    document.getElementById("movementType");

const quantityInput =
    document.getElementById("quantity");

const stockInfo =
    document.getElementById("stockInfo");

const form =
    document.getElementById("movementForm");

function validateMovement() {

    const option = productSelect.selectedOptions[0];

    const currentStock = Number(
        option?.dataset.stock ?? 0
    );

    const quantity = Number(quantityInput.value);

    if (!productSelect.value) {
        stockInfo.textContent = "Selectează un produs.";
    } else {
        stockInfo.textContent =
            "Stoc disponibil: " +
            currentStock + " bucăți.";
    }

    let message = "";

    if (quantityInput.value !== "") {

        if (
            !Number.isInteger(quantity) ||
            quantity < 1 ||
            quantity > 1000000000
        ) {
            message = "Introdu o cantitate validă.";
        } else if (
            typeSelect.value === "OUT" &&
            quantity > currentStock
        ) {
            message =
                "Stoc insuficient! Disponibil: " +
                currentStock + " bucăți.";
        }
    }

    quantityInput.setCustomValidity(message);
}

productSelect.addEventListener(
    "change", validateMovement
);

typeSelect.addEventListener(
    "change", validateMovement
);

quantityInput.addEventListener(
    "input", validateMovement
);

form.addEventListener("submit", function(event) {

    validateMovement();

    if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
    }
});

validateMovement();
</script>

</body>
</html>
