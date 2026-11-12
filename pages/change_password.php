
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

$error = "";
$success = $_SESSION['password_success'] ?? "";

unset($_SESSION['password_success']);

$csrfToken = sf_csrf_token();
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = (string)(
        $_POST['csrf_token'] ?? ''
    );

    $currentPassword = (string)(
        $_POST['current_password'] ?? ''
    );

    $newPassword = (string)(
        $_POST['new_password'] ?? ''
    );

    $confirmPassword = (string)(
        $_POST['confirm_password'] ?? ''
    );

    if (!hash_equals($csrfToken, $token)) {

        $error = "Cerere invalidă. Reîncarcă pagina.";

    } elseif ($currentPassword === '') {

        $error = "Introdu parola actuală.";

    } elseif (
        strlen($newPassword) < 12 ||
        strlen($newPassword) > 72
    ) {

        $error =
            "Parola nouă trebuie să aibă între 12 și 72 de octeți.";

    } elseif ($newPassword !== $confirmPassword) {

        $error = "Parolele noi nu coincid.";

    } else {

        try {

            // Citim exclusiv contul autentificat.
            $stmt = $pdo->prepare(
                "SELECT id, password_hash
                 FROM users
                 WHERE id = ?
                   AND is_active = 1
                 LIMIT 1"
            );

            $stmt->execute([$userId]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {

                $error = "Contul nu este disponibil.";

            } elseif (!password_verify(
                $currentPassword,
                $user['password_hash']
            )) {

                $error = "Parola actuală este incorectă.";

            } elseif (password_verify(
                $newPassword,
                $user['password_hash']
            )) {

                $error =
                    "Parola nouă trebuie să fie diferită de cea actuală.";

            } else {

                // Generam hash-ul noii parole.
                $newHash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                // Actualizam numai contul autentificat.
                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET password_hash = ?
                     WHERE id = ?
                       AND password_hash = ?
                       AND is_active = 1"
                );

                $stmt->execute([
                    $newHash,
                    $userId,
                    $user['password_hash']
                ]);

                if ($stmt->rowCount() !== 1) {

                    $error =
                        "Parola a fost modificată între timp. Reîncearcă.";

                } else {

                    session_regenerate_id(true);

                    $_SESSION['csrf_token'] =
                        bin2hex(random_bytes(32));

                    $_SESSION['password_success'] =
                        "Parola a fost schimbată cu succes!";

                    header(
                        "Location: change_password.php"
                    );

                    exit;
                }
            }

        } catch (PDOException $exception) {

            error_log($exception->getMessage());

            $error =
                "Schimbarea parolei a eșuat. Reîncearcă.";
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

    <title>Schimbă parola - StockFlow</title>

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

            <h1 class="fw-bold mb-2">
                Schimbă parola
            </h1>

            <p class="text-secondary mb-4">
                Modifică parola contului tău StockFlow.
            </p>

            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <?php if ($success): ?>

                <div class="alert alert-success">
                    <?= e($success) ?>
                </div>

            <?php endif; ?>

            <div class="card border-0 shadow-sm"
                 style="max-width: 680px;">

                <div class="card-body p-4">

                    <h4 class="mb-3">
                        Securitatea contului
                    </h4>

                    <p class="text-secondary">
                        Cont conectat:
                        <strong>
                            <?= e($_SESSION['user_name']) ?>
                        </strong>
                    </p>

                    <form method="POST" id="passwordForm">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e($csrfToken) ?>">

                        <div class="mb-3">

                            <label class="form-label">
                                Parola actuală
                            </label>

                            <input
                                type="password"
                                name="current_password"
                                id="currentPassword"
                                class="form-control"
                                autocomplete="current-password"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Parola nouă
                            </label>

                            <input
                                type="password"
                                name="new_password"
                                id="newPassword"
                                class="form-control"
                                minlength="12"
                                autocomplete="new-password"
                                required>

                            <div class="form-text">
                                Minimum 12 caractere.
                                Recomandăm o parolă unică
                                și dificil de ghicit.
                            </div>

                        </div>

                        <div class="mb-4">

                            <label class="form-label">
                                Confirmă parola nouă
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                id="confirmPassword"
                                class="form-control"
                                minlength="12"
                                autocomplete="new-password"
                                required>

                        </div>

                        <div class="d-flex flex-wrap gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary">

                                Salvează parola nouă

                            </button>

                            <a
                                href="../index.php"
                                class="btn btn-secondary">

                                Anulează

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </main>

    </div>
</div>

<script>
document.getElementById("passwordForm")
    .addEventListener("submit", function(event) {

        const current = document
            .getElementById("currentPassword")
            .value;

        const password = document
            .getElementById("newPassword")
            .value;

        const confirm = document
            .getElementById("confirmPassword")
            .value;

        const bytes = new TextEncoder()
            .encode(password).length;

        if (
            !current ||
            bytes < 12 ||
            bytes > 72 ||
            password !== confirm
        ) {

            event.preventDefault();

            alert(
                "Verifică parola actuală, parola nouă și confirmarea."
            );
        }
    });
</script>

</body>
</html>
