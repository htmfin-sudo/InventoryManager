
<?php
session_start();

require_once __DIR__ . "/../config/db.php";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$status = $_GET['status'] ?? "";
$editCategory = null;

// EDITAREA UNEI CATEGORII
$editId = filter_input(
    INPUT_GET,
    'edit',
    FILTER_VALIDATE_INT
);

if ($editId && $editId > 0) {
    $stmt = $pdo->prepare(
        "SELECT * FROM categories WHERE id = ?"
    );
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editCategory) {
        $error = "Categoria nu a fost găsită.";
    }
}

// PROCESAREA FORMULARELOR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = (string)($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = "Cerere invalidă.";
    } else {

        try {

            if ($action === 'add' || $action === 'edit') {

                $name = trim($_POST['name'] ?? '');
                $description = trim(
                    $_POST['description'] ?? ''
                );

                if (
                    mb_strlen($name) < 2 ||
                    mb_strlen($name) > 100 ||
                    mb_strlen($description) > 500
                ) {
                    $error = "Verifică numele și descrierea.";
                } elseif ($action === 'add') {

                    // CREATE
                    $stmt = $pdo->prepare(
                        "INSERT INTO categories
                        (name, description)
                        VALUES (?, ?)"
                    );

                    $stmt->execute([$name, $description]);

                    header(
                        "Location: categories.php?status=added"
                    );
                    exit;

                } else {

                    // UPDATE
                    $id = filter_var(
                        $_POST['id'] ?? '',
                        FILTER_VALIDATE_INT
                    );

                    if (!$id || $id < 1) {
                        $error = "ID invalid.";
                    } else {

                        $stmt = $pdo->prepare(
                            "UPDATE categories
                            SET name = ?, description = ?
                            WHERE id = ?"
                        );

                        $stmt->execute([
                            $name,
                            $description,
                            $id
                        ]);

                        header(
                            "Location: categories.php?status=updated"
                        );
                        exit;
                    }
                }

            } elseif ($action === 'delete') {

                // DELETE
                $id = filter_var(
                    $_POST['id'] ?? '',
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    $error = "ID invalid.";
                } else {

                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*)
                        FROM products
                        WHERE category_id = ?"
                    );

                    $stmt->execute([$id]);

                    if ((int)$stmt->fetchColumn() > 0) {

                        $error =
                            "Categoria conține produse și nu poate fi ștearsă.";

                    } else {

                        $stmt = $pdo->prepare(
                            "DELETE FROM categories WHERE id = ?"
                        );

                        $stmt->execute([$id]);

                        header(
                            "Location: categories.php?status=deleted"
                        );
                        exit;
                    }
                }
            } else {
                $error = "Acțiune necunoscută.";
            }

        } catch (PDOException $exception) {

            error_log($exception->getMessage());

            if ($exception->getCode() === '23000') {
                $error = "Categoria există deja sau este utilizată.";
            } else {
                $error = "Operația nu a putut fi efectuată.";
            }
        }
    }
}

// PASTRAM DATELE INTRODUSE DACA APARE O EROARE
if (
    $error !== '' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    in_array($_POST['action'] ?? '', ['add', 'edit'], true)
) {
    $editCategory = [
        'id' => $_POST['id'] ?? '',
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? '')
    ];
}

// LISTA CATEGORIILOR
$categories = $pdo->query(
    "SELECT
        c.id,
        c.name,
        c.description,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.description
    ORDER BY c.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Categorii - StockFlow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- MENIUL COMUN -->
        <?php
        require __DIR__ . "/../includes/sidebar.php";
        ?>

        <!-- CONTINUT -->
        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="fw-bold mb-4">
                Categorii
            </h1>

            <!-- MESAJE -->
            <?php if ($status === 'added'): ?>

                <div class="alert alert-success">
                    Categoria a fost adăugată!
                </div>

            <?php elseif ($status === 'updated'): ?>

                <div class="alert alert-success">
                    Categoria a fost modificată!
                </div>

            <?php elseif ($status === 'deleted'): ?>

                <div class="alert alert-success">
                    Categoria a fost ștearsă!
                </div>

            <?php endif; ?>

            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <!-- FORMULAR -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        <?= $editCategory
                            ? 'Editează categoria'
                            : 'Adaugă categorie' ?>
                    </h4>

                    <form method="POST" id="categoryForm">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>">

                        <input
                            type="hidden"
                            name="action"
                            value="<?= $editCategory ? 'edit' : 'add' ?>">

                        <?php if ($editCategory): ?>

                            <input
                                type="hidden"
                                name="id"
                                value="<?= e($editCategory['id']) ?>">

                        <?php endif; ?>

                        <div class="mb-3">

                            <label class="form-label">
                                Nume categorie
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="categoryName"
                                class="form-control"
                                minlength="2"
                                maxlength="100"
                                value="<?= e($editCategory['name'] ?? '') ?>"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Descriere
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="3"
                                maxlength="500"><?= e(
                                    $editCategory['description'] ?? ''
                                ) ?></textarea>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary">

                            <?= $editCategory
                                ? 'Salvează modificările'
                                : '+ Adaugă categorie' ?>

                        </button>

                        <?php if ($editCategory): ?>

                            <a href="categories.php"
                               class="btn btn-secondary">
                                Anulează
                            </a>

                        <?php endif; ?>

                    </form>

                </div>
            </div>

            <!-- TABEL CATEGORII -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Lista categoriilor
                    </h4>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Categorie</th>
                                    <th>Descriere</th>
                                    <th>Produse</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($categories as $c): ?>

                                <tr>

                                    <td>
                                        <?= (int)$c['id'] ?>
                                    </td>

                                    <td class="fw-semibold">
                                        <?= e($c['name']) ?>
                                    </td>

                                    <td>
                                        <?= e($c['description']) ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?= (int)$c['product_count'] ?>
                                        </span>
                                    </td>

                                    <td>

                                        <div class="d-flex gap-2">

                                            <a
                                                href="categories.php?edit=<?= (int)$c['id'] ?>"
                                                class="btn btn-warning btn-sm">

                                                Editează

                                            </a>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Sigur vrei să ștergi categoria?');">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$c['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm">

                                                    Șterge

                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>
                        </table>

                    </div>

                    <?php if (empty($categories)): ?>

                        <div class="alert alert-info">
                            Nu există categorii.
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.getElementById("categoryForm")
    .addEventListener("submit", function(event) {

        const name = document
            .getElementById("categoryName")
            .value.trim();

        if (name.length < 2 || name.length > 100) {

            event.preventDefault();

            alert(
                "Numele categoriei trebuie să conțină între 2 și 100 de caractere."
            );
        }
    });
</script>

</body>
</html>