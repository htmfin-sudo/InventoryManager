
<?php

$currentPage = basename(
    $_SERVER['SCRIPT_NAME'] ?? ''
);

$isAdmin = (
    ($_SESSION['user_role'] ?? '') === 'admin'
);

$menu = [
    [
        'name' => 'Dashboard',
        'url' => '/InventoryManager/index.php',
        'pages' => ['index.php'],
        'admin' => false
    ],
    [
        'name' => 'Produse',
        'url' => '/InventoryManager/pages/products.php',
        'pages' => [
            'products.php',
            'add_product.php',
            'edit_product.php'
        ],
        'admin' => false
    ],
    [
        'name' => 'Categorii',
        'url' => '/InventoryManager/pages/categories.php',
        'pages' => ['categories.php'],
        'admin' => true
    ],
    [
        'name' => 'Furnizori',
        'url' => '/InventoryManager/pages/suppliers.php',
        'pages' => ['suppliers.php'],
        'admin' => true
    ],
    [
        'name' => 'Mișcări de stoc',
        'url' => '/InventoryManager/pages/stock_movements.php',
        'pages' => ['stock_movements.php'],
        'admin' => false
    ],
    [
        'name' => 'Utilizatori',
        'url' => '/InventoryManager/pages/users.php',
        'pages' => ['users.php'],
        'admin' => true
    ]
];

function sidebarEscape($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$userName = $_SESSION['user_name'] ?? 'Utilizator';

$roleLabel = $isAdmin
    ? 'Administrator'
    : 'Angajat';
?>

<aside class="col-md-3 col-lg-2 sidebar">

    <h3>StockFlow</h3>

    <nav aria-label="Meniu principal">

        <?php foreach ($menu as $item): ?>

            <?php
            if ($item['admin'] && !$isAdmin) {
                continue;
            }

            $active = in_array(
                $currentPage,
                $item['pages'],
                true
            );
            ?>

            <a
                href="<?= sidebarEscape($item['url']) ?>"
                class="<?= $active ? 'active' : '' ?>"
                <?= $active ? 'aria-current="page"' : '' ?>>

                <?= sidebarEscape($item['name']) ?>

            </a>

        <?php endforeach; ?>

    </nav>

    <div
        class="mt-5 p-3 rounded"
        style="background-color: #1f2937;">

        <p class="text-light small mb-1">
            Cont conectat
        </p>

        <p class="text-white fw-bold mb-1">
            <?= sidebarEscape($userName) ?>
        </p>

        <span class="badge bg-primary mb-3">
            <?= sidebarEscape($roleLabel) ?>
        </span>

        <a
            href="/InventoryManager/pages/change_password.php"
            class="btn btn-outline-light btn-sm w-100 mb-2">

            Schimbă parola

        </a>

        <a
            href="/InventoryManager/logout.php"
            class="btn btn-outline-light btn-sm w-100">

            Deconectare

        </a>

    </div>

</aside>
