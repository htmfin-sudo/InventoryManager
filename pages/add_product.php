
<?php
session_start();
require_once __DIR__ . "/../config/db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query(
    "SELECT id, name FROM suppliers ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$error = "";

$data = [
    'name' => '',
    'category_id' => '',
    'supplier_id' => '',
    'price' => '',
    'min_stock' => '5'
];

// ADAUGARE PRODUS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($data as $key => $value) {
        $data[$key] = trim(
            (string)($_POST[$key] ?? '')
        );
    }

    $category = filter_var(
        $data['category_id'],
        FILTER_VALIDATE_INT
    );

    $supplier = filter_var(
        $data['supplier_id'],
        FILTER_VALIDATE_INT
    );

    $stock = filter_var(
        $data['min_stock'],
        FILTER_VALIDATE_INT
    );

    if (!hash_equals(
        $_SESSION['csrf_token'],
        (string)($_POST['csrf_token'] ?? '')
    )) {
        $error = "Cerere invalidă.";

    } elseif (
        mb_strlen($data['name']) < 2 ||
        mb_strlen($data['name']) > 150 ||
        $category === false || $category < 1 ||
        $supplier === false || $supplier < 1 ||
        !preg_match(
            '/^\d{1,8}(\.\d{1,2})?$/',
            $data['price']
        ) ||
        $stock === false ||
        $stock < 0 ||
        $stock > 1000000000
    ) {
        $error = "Verifică datele introduse.";

    } else {

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO products
                 (name, category_id, supplier_id,
                  price, min_stock)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $data['name'],
                $category,
                $supplier,
                $data['price'],
                $stock
            ]);

            header("Location: products.php");
            exit;

        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = "Produsul nu a putut fi salvat.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Adaugă produs - StockFlow</title>

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

        <!-- PAGINA -->
        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="fw-bold mb-4">
                Adaugă produs
            </h1>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="productForm">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e($_SESSION['csrf_token']) ?>">

                        <!-- NUME -->
                        <div class="mb-3">
                            <label class="form-label">
                                Nume produs
                            </label>

                            <input type="text"
                                   name="name"
                                   id="name"
                                   class="form-control"
                                   minlength="2"
                                   maxlength="150"
                                   value="<?= e($data['name']) ?>"
                                   required>
                        </div>

                        <!-- CATEGORIE -->
                        <div class="mb-3">
                            <label class="form-label">
                                Categorie
                            </label>

                            <select name="category_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Alege categoria
                                </option>

                                <?php foreach ($categories as $c): ?>
                                    <option
                                        value="<?= (int)$c['id'] ?>"
                                        <?= (string)$data['category_id'] === (string)$c['id'] ? 'selected' : '' ?>>

                                        <?= e($c['name']) ?>

                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <!-- FURNIZOR -->
                        <div class="mb-3">
                            <label class="form-label">
                                Furnizor
                            </label>

                            <select name="supplier_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Alege furnizorul
                                </option>

                                <?php foreach ($suppliers as $s): ?>
                                    <option
                                        value="<?= (int)$s['id'] ?>"
                                        <?= (string)$data['supplier_id'] === (string)$s['id'] ? 'selected' : '' ?>>

                                        <?= e($s['name']) ?>

                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <!-- PRET -->
                        <div class="mb-3">
                            <label class="form-label">
                                Preț (lei)
                            </label>

                            <input type="number"
                                   name="price"
                                   id="price"
                                   class="form-control"
                                   min="0"
                                   max="99999999.99"
                                   step="0.01"
                                   value="<?= e($data['price']) ?>"
                                   required>
                        </div>

                        <!-- STOC MINIM -->
                        <div class="mb-3">
                            <label class="form-label">
                                Stoc minim
                            </label>

                            <input type="number"
                                   name="min_stock"
                                   id="min_stock"
                                   class="form-control"
                                   min="0"
                                   max="1000000000"
                                   step="1"
                                   value="<?= e($data['min_stock']) ?>"
                                   required>
                        </div>

                        <!-- BUTOANE -->
                        <button type="submit"
                                class="btn btn-primary">
                            Salvează produsul
                        </button>

                        <a href="products.php"
                           class="btn btn-secondary">
                            Anulează
                        </a>

                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.getElementById("productForm")
    .addEventListener("submit", function(event) {

        const name = document.getElementById("name")
            .value.trim();

        const price = Number(
            document.getElementById("price").value
        );

        const stock = Number(
            document.getElementById("min_stock").value
        );

        if (
            name.length < 2 ||
            name.length > 150 ||
            !Number.isFinite(price) ||
            price < 0 ||
            price > 99999999.99 ||
            !Number.isInteger(stock) ||
            stock < 0 ||
            stock > 1000000000
        ) {
            event.preventDefault();
            alert("Verifică numele, prețul și stocul minim!");
        }
    });
</script>

</body>
</html>