
<?php
require_once __DIR__ . "/../includes/auth.php";
sf_require_admin();

require_once __DIR__ . "/../config/db.php";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$error = "";
$status = $_GET['status'] ?? "";
$name = "";
$email = "";
$csrfToken = sf_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $token)) {
        $error = "Cerere invalidă. Reîncarcă pagina.";

    } elseif ($action === 'add') {

        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(
            trim((string)($_POST['email'] ?? ''))
        );

        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)(
            $_POST['confirm_password'] ?? ''
        );

        if (
            mb_strlen($name) < 2 ||
            mb_strlen($name) > 100 ||
            strlen($email) > 150 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $error = "Numele sau emailul este invalid.";

        } elseif (
            strlen($password) < 12 ||
            strlen($password) > 72
        ) {
            $error = "Parola trebuie să aibă între 12 și 72 de octeți.";

        } elseif ($password !== $confirm) {
            $error = "Parolele nu coincid.";

        } else {

            try {
                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (name, email, password_hash, role, is_active)
                    VALUES (?, ?, ?, 'user', 1)"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $hash
                ]);

                header(
                    "Location: users.php?status=created"
                );
                exit;

            } catch (PDOException $exception) {

                error_log($exception->getMessage());

                $error = $exception->getCode() === '23000'
                    ? "Emailul este deja înregistrat."
                    : "Contul nu a putut fi creat.";
            }
        }

    } elseif (
        $action === 'delete' ||
        $action === 'reactivate'
    ) {

        $targetId = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$targetId || $targetId < 1) {
            $error = "Utilizator invalid.";

        } elseif (
            $targetId === (int)$_SESSION['user_id']
        ) {
            $error = "Nu îți poți elimina propriul cont.";

        } else {

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "SELECT id, role, is_active
                     FROM users
                     WHERE id = ?
                     FOR UPDATE"
                );

                $stmt->execute([$targetId]);

                $target = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$target || $target['role'] !== 'user') {

                    $pdo->rollBack();
                    $error = "Operație nepermisă.";

                } elseif ($action === 'reactivate') {

                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET is_active = 1
                         WHERE id = ? AND role = 'user'"
                    );

                    $stmt->execute([$targetId]);

                    $pdo->commit();

                    header(
                        "Location: users.php?status=reactivated"
                    );
                    exit;

                } elseif ((int)$target['is_active'] === 0) {

                    $pdo->rollBack();
                    $error = "Contul este deja dezactivat.";

                } else {

                    // Verificam istoricul utilizatorului
                    $stmt = $pdo->prepare(
                        "SELECT COUNT(*)
                         FROM stock_movements
                         WHERE user_id = ?"
                    );

                    $stmt->execute([$targetId]);

                    $movementCount =
                        (int)$stmt->fetchColumn();

                    if ($movementCount > 0) {

                        // Pastram istoricul si blocam contul
                        $stmt = $pdo->prepare(
                            "UPDATE users
                             SET is_active = 0
                             WHERE id = ? AND role = 'user'"
                        );

                        $stmt->execute([$targetId]);

                        $result = "deactivated";

                    } else {

                        // Stergem definitiv contul fara istoric
                        $stmt = $pdo->prepare(
                            "DELETE FROM users
                             WHERE id = ? AND role = 'user'"
                        );

                        $stmt->execute([$targetId]);

                        $result = "deleted";
                    }

                    $pdo->commit();

                    header(
                        "Location: users.php?status=" . $result
                    );
                    exit;
                }

            } catch (Throwable $exception) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($exception->getMessage());

                $error = "Operația nu a putut fi efectuată.";
            }
        }

    } else {
        $error = "Acțiune necunoscută.";
    }
}

// Lista utilizatorilor si istoricul asociat
$users = $pdo->query(
    "SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.is_active,
        u.created_at,
        COUNT(sm.id) AS movement_count
     FROM users u
     LEFT JOIN stock_movements sm
        ON sm.user_id = u.id
     GROUP BY
        u.id, u.name, u.email,
        u.role, u.is_active, u.created_at
     ORDER BY u.is_active DESC, u.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Utilizatori - StockFlow</title>

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

            <h1 class="fw-bold">Utilizatori</h1>

            <p class="text-secondary mb-4">
                Administrarea conturilor și drepturilor
                de acces în StockFlow.
            </p>

            <?php if ($status === 'created'): ?>
                <div class="alert alert-success">
                    Angajatul a fost creat cu succes!
                </div>
            <?php elseif ($status === 'deleted'): ?>
                <div class="alert alert-success">
                    Angajatul a fost șters definitiv!
                </div>
            <?php elseif ($status === 'deactivated'): ?>
                <div class="alert alert-warning">
                    Angajatul a fost dezactivat.
                    Istoricul operațiunilor a fost păstrat.
                </div>
            <?php elseif ($status === 'reactivated'): ?>
                <div class="alert alert-success">
                    Contul angajatului a fost reactivat!
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- CREARE ANGAJAT -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Creează cont de angajat
                    </h4>

                    <form method="POST" id="userForm">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e($csrfToken) ?>">

                        <input type="hidden"
                               name="action"
                               value="add">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    Nume complet
                                </label>

                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       minlength="2"
                                       maxlength="100"
                                       value="<?= e($name) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Email
                                </label>

                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       maxlength="150"
                                       value="<?= e($email) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Parolă
                                </label>

                                <input type="password"
                                       name="password"
                                       id="password"
                                       class="form-control"
                                       minlength="12"
                                       autocomplete="new-password"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Confirmă parola
                                </label>

                                <input type="password"
                                       name="confirm_password"
                                       id="confirmPassword"
                                       class="form-control"
                                       minlength="12"
                                       autocomplete="new-password"
                                       required>
                            </div>
                        </div>

                        <button type="submit"
                                class="btn btn-primary mt-3">
                            + Creează angajat
                        </button>

                    </form>
                </div>
            </div>

            <!-- LISTA UTILIZATORI -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Lista utilizatorilor
                    </h4>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nume</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Status</th>
                                    <th>Operațiuni</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($users as $user): ?>

                                <?php
                                $active =
                                    (int)$user['is_active'] === 1;

                                $admin =
                                    $user['role'] === 'admin';

                                $history =
                                    (int)$user['movement_count'];
                                ?>

                                <tr>
                                    <td>
                                        <?= (int)$user['id'] ?>
                                    </td>

                                    <td class="fw-semibold">
                                        <?= e($user['name']) ?>
                                    </td>

                                    <td>
                                        <?= e($user['email']) ?>
                                    </td>

                                    <td>
                                        <span class="badge
                                            <?= $admin
                                                ? 'bg-primary'
                                                : 'bg-secondary' ?>">
                                            <?= $admin
                                                ? 'Administrator'
                                                : 'Angajat' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ($active): ?>
                                            <span class="badge bg-success">
                                                Activ
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                Dezactivat
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= $history ?>
                                    </td>

                                    <td>
                                        <?php if ($admin): ?>

                                            <span class="text-secondary">
                                                Protejat
                                            </span>

                                        <?php else: ?>

                                            <form method="POST"
                                                  onsubmit="return confirm('Confirmi această operațiune asupra contului?');">

                                                <input type="hidden"
                                                       name="csrf_token"
                                                       value="<?= e($csrfToken) ?>">

                                                <input type="hidden"
                                                       name="id"
                                                       value="<?= (int)$user['id'] ?>">

                                                <?php if ($active): ?>

                                                    <input type="hidden"
                                                           name="action"
                                                           value="delete">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm
                                                        <?= $history > 0
                                                            ? 'btn-warning'
                                                            : 'btn-danger' ?>">

                                                        <?= $history > 0
                                                            ? 'Dezactivează'
                                                            : 'Șterge' ?>

                                                    </button>

                                                <?php else: ?>

                                                    <input type="hidden"
                                                           name="action"
                                                           value="reactivate">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-success btn-sm">

                                                        Reactivează

                                                    </button>

                                                <?php endif; ?>

                                            </form>

                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.getElementById("userForm")
    .addEventListener("submit", function(event) {

        const password =
            document.getElementById("password").value;

        const confirm =
            document.getElementById("confirmPassword").value;

        const bytes =
            new TextEncoder().encode(password).length;

        if (
            bytes < 12 ||
            bytes > 72 ||
            password !== confirm
        ) {
            event.preventDefault();

            alert("Verifică parola și confirmarea.");
        }
    });
</script>

</body>
</html>
