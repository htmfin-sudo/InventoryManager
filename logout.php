
<?php

require_once __DIR__ . "/includes/auth.php";

sf_start_session();

if (!sf_is_authenticated()) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = (string)(
        $_POST['csrf_token'] ?? ''
    );

    if (!hash_equals(sf_csrf_token(), $token)) {
        http_response_code(403);
        exit("Cerere invalidă.");
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax'
            ]
        );
    }

    session_destroy();

    header("Location: login.php?logged_out=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Deconectare - StockFlow</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm mx-auto"
         style="max-width: 450px;">

        <div class="card-body p-4 text-center">

            <h2>Deconectare</h2>

            <p class="text-secondary">
                Sigur dorești să ieși din StockFlow?
            </p>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        sf_csrf_token(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                <button
                    type="submit"
                    class="btn btn-danger">

                    Deconectare

                </button>

                <a href="index.php"
                   class="btn btn-secondary">

                    Anulează

                </a>

            </form>

        </div>

    </div>

</div>

</body>
</html>
