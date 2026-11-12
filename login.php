
<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

sf_start_session();

if (sf_is_authenticated()) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = isset($_GET['logged_out']);
$email = "";

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(
        trim((string)($_POST['email'] ?? ''))
    );

    $password = (string)(
        $_POST['password'] ?? ''
    );

    $token = (string)(
        $_POST['csrf_token'] ?? ''
    );

    $now = time();

    $_SESSION['login_attempts'] = array_values(
        array_filter(
            $_SESSION['login_attempts'],
            fn($t) => is_int($t) && $t > $now - 900
        )
    );

    if (!hash_equals(sf_csrf_token(), $token)) {

        $error = "Cerere invalidă.";

    } elseif (count($_SESSION['login_attempts']) >= 5) {

        $error = "Prea multe încercări. Încearcă mai târziu.";

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $password === ''
    ) {

        $error = "Introdu un email și o parolă valide.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT
                id, name, email, password_hash,
                role, is_active
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $user &&
            (int)$user['is_active'] === 1 &&
            password_verify(
                $password,
                $user['password_hash']
            ) &&
            in_array(
                $user['role'],
                ['admin', 'user'],
                true
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            unset($_SESSION['login_attempts']);

            header("Location: index.php");
            exit;

        } else {

            $_SESSION['login_attempts'][] = $now;

            $error =
                "Date incorecte sau cont dezactivat.";
        }
    }
}

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

    <title>Autentificare - StockFlow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 10px 35px rgba(0,0,0,.08);
        }

        .logo {
            color: #2563eb;
            font-size: 30px;
            font-weight: 700;
        }

        .subtitle {
            color: #64748b;
        }

        .btn-primary {
            background: #2563eb;
            padding: 12px;
            font-weight: 600;
        }

        .form-control {
            padding: 12px;
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="text-center mb-4">
        <div class="logo">StockFlow</div>

        <h4 class="mt-3">
            Autentificare
        </h4>

        <p class="subtitle">
            Intră în cont pentru a gestiona inventarul.
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Te-ai deconectat cu succes!
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">

        <input type="hidden"
               name="csrf_token"
               value="<?= e(sf_csrf_token()) ?>">

        <div class="mb-3">
            <label class="form-label">
                Adresă email
            </label>

            <input type="email"
                   name="email"
                   class="form-control"
                   maxlength="150"
                   autocomplete="username"
                   value="<?= e($email) ?>"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label">
                Parolă
            </label>

            <input type="password"
                   name="password"
                   class="form-control"
                   autocomplete="current-password"
                   required>
        </div>

        <button type="submit"
                class="btn btn-primary w-100">
            Autentificare
        </button>

    </form>

    <p class="text-center text-secondary mt-4 mb-0">
        InventoryManager © StockFlow
    </p>

</div>

<script>
document.getElementById("loginForm")
    .addEventListener("submit", function(event) {

        const email = this.querySelector(
            'input[name="email"]'
        ).value.trim();

        const password = this.querySelector(
            'input[name="password"]'
        ).value;

        if (!email || !password) {
            event.preventDefault();
            alert("Completează emailul și parola!");
        }
    });
</script>

</body>
</html>
