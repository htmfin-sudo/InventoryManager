
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
$editSupplier = null;

$formValues = [
    'name' => '',
    'email' => '',
    'phone' => ''
];

// CITIM FURNIZORUL PENTRU EDITARE
if (isset($_GET['edit'])) {

    $editId = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM suppliers WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $editSupplier = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($editSupplier) {
            $formValues = [
                'name' => $editSupplier['name'],
                'email' => $editSupplier['email'] ?? '',
                'phone' => $editSupplier['phone'] ?? ''
            ];
        } else {
            $error = "Furnizorul nu a fost găsit.";
        }
    } else {
        $error = "ID furnizor invalid.";
    }
}

// ADAUGARE, EDITARE SI STERGERE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    $token = (string)(
        $_POST['csrf_token'] ?? ''
    );

    if (!hash_equals(
        $_SESSION['csrf_token'],
        $token
    )) {
        $error = "Cerere invalidă. Reîncarcă pagina.";

    } else {

        try {

            if ($action === 'add' || $action === 'edit') {

                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');

                $formValues = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ];

                if (
                    mb_strlen($name) < 2 ||
                    mb_strlen($name) > 150 ||
                    mb_strlen($email) > 150 ||
                    mb_strlen($phone) > 30 ||
                    (
                        $email !== '' &&
                        !filter_var(
                            $email,
                            FILTER_VALIDATE_EMAIL
                        )
                    )
                ) {
                    $error = "Verifică datele furnizorului.";

                } elseif ($action === 'add') {

                    // CREATE
                    $stmt = $pdo->prepare(
                        "INSERT INTO suppliers
                         (name, email, phone)
                         VALUES (?, ?, ?)"
                    );

                    $stmt->execute([
                        $name,
                        $email ?: null,
                        $phone ?: null
                    ]);

                    header(
                        "Location: suppliers.php?status=added"
                    );
                    exit;

                } else {

                    // UPDATE
                    $id = filter_var(
                        $_POST['id'] ?? '',
                        FILTER_VALIDATE_INT
                    );

                    if (!$id || $id < 1) {

                        $error = "Furnizor invalid.";

                    } else {

                        $stmt = $pdo->prepare(
                            "UPDATE suppliers
                             SET name = ?,
                                 email = ?,
                                 phone = ?
                             WHERE id = ?"
                        );

                        $stmt->execute([
                            $name,
                            $email ?: null,
                            $phone ?: null,
                            $id
                        ]);

                        header(
                            "Location: suppliers.php?status=updated"
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

                    $error = "Furnizor invalid.";

                } else {

                    // Verificam daca are produse
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*)
                         FROM products
                         WHERE supplier_id = ?"
                    );

                    $stmt->execute([$id]);

                    $count = (int)$stmt->fetchColumn();

                    if ($count > 0) {

                        $error =
                            "Furnizorul are produse asociate și nu poate fi șters.";

                    } else {

                        $stmt = $pdo->prepare(
                            "DELETE FROM suppliers
                             WHERE id = ?"
                        );

                        $stmt->execute([$id]);

                        header(
                            "Location: suppliers.php?status=deleted"
                        );
                        exit;
                    }
                }

            } else {

                $error = "Acțiune necunoscută.";
            }

        } catch (PDOException $exception) {

            error_log($exception->getMessage());

            $error =
                "Operația nu a putut fi efectuată.";
        }
    }
}

// CITIM TOTI FURNIZORII
$suppliers = $pdo->query(
    "SELECT
        s.id,
        s.name,
        s.email,
        s.phone,
        COUNT(p.id) AS product_count
     FROM suppliers s
     LEFT JOIN products p
         ON p.supplier_id = s.id
     GROUP BY
        s.id,
        s.name,
        s.email,
        s.phone
     ORDER BY s.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Furnizori - StockFlow</title>

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

        <!-- CONTINUTUL PAGINII -->
        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="fw-bold mb-4">
                Furnizori
            </h1>

            <!-- MESAJE -->
            <?php if ($status === 'added'): ?>

                <div class="alert alert-success">
                    Furnizorul a fost adăugat cu succes!
                </div>

            <?php elseif ($status === 'updated'): ?>

                <div class="alert alert-success">
                    Furnizorul a fost modificat cu succes!
                </div>

            <?php elseif ($status === 'deleted'): ?>

                <div class="alert alert-success">
                    Furnizorul a fost șters cu succes!
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
                        <?= $editSupplier
                            ? 'Editează furnizor'
                            : 'Adaugă furnizor' ?>
                    </h4>

                    <form method="POST" id="supplierForm">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>">

                        <input
                            type="hidden"
                            name="action"
                            value="<?= $editSupplier ? 'edit' : 'add' ?>">

                        <?php if ($editSupplier): ?>

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$editSupplier['id'] ?>">

                        <?php endif; ?>

                        <div class="mb-3">

                            <label class="form-label">
                                Nume furnizor
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="supplierName"
                                class="form-control"
                                minlength="2"
                                maxlength="150"
                                value="<?= e($formValues['name']) ?>"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Adresă email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="supplierEmail"
                                class="form-control"
                                maxlength="150"
                                value="<?= e($formValues['email']) ?>"
                                placeholder="exemplu@firma.ro">

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Număr de telefon
                            </label>

                            <input
                                type="tel"
                                name="phone"
                                id="supplierPhone"
                                class="form-control"
                                maxlength="30"
                                value="<?= e($formValues['phone']) ?>"
                                placeholder="07xxxxxxxx">

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary">

                            <?= $editSupplier
                                ? 'Salvează modificările'
                                : '+ Adaugă furnizor' ?>

                        </button>

                        <?php if ($editSupplier): ?>

                            <a href="suppliers.php"
                               class="btn btn-secondary">
                                Anulează
                            </a>

                        <?php endif; ?>

                    </form>

                </div>
            </div>

            <!-- TABEL FURNIZORI -->
            <div class="card border-0 shadow-sm">

                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Lista furnizorilor
                    </h4>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Furnizor</th>
                                    <th>Email</th>
                                    <th>Telefon</th>
                                    <th>Produse</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($suppliers as $s): ?>

                                <tr>

                                    <td>
                                        <?= (int)$s['id'] ?>
                                    </td>

                                    <td class="fw-semibold">
                                        <?= e($s['name']) ?>
                                    </td>

                                    <td>
                                        <?= e($s['email'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= e($s['phone'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            <?= (int)$s['product_count'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">

                                            <!-- EDITARE -->
                                            <a
                                                href="suppliers.php?edit=<?= (int)$s['id'] ?>"
                                                class="btn btn-warning btn-sm">

                                                Editează

                                            </a>

                                            <!-- STERGERE -->
                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Sigur vrei să ștergi acest furnizor?');">

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
                                                    value="<?= (int)$s['id'] ?>">

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

                    <?php if (empty($suppliers)): ?>

                        <div class="alert alert-info">
                            Nu există furnizori înregistrați.
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.getElementById("supplierForm")
    .addEventListener("submit", function(event) {

        const name = document
            .getElementById("supplierName")
            .value.trim();

        const email = document
            .getElementById("supplierEmail");

        const phone = document
            .getElementById("supplierPhone")
            .value.trim();

        if (
            name.length < 2 ||
            name.length > 150 ||
            !email.checkValidity() ||
            phone.length > 30
        ) {
            event.preventDefault();

            alert(
                "Verifică numele, emailul și telefonul."
            );
        }
    });
</script>

</body>
</html>