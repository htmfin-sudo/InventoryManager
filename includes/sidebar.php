
<?php
// Meniu comun pentru toate paginile
$currentPage = basename(
    $_SERVER['SCRIPT_NAME'] ?? ''
);

$menu = [
    [
        'name' => 'Dashboard',
        'url' => '/InventoryManager/index.php',
        'pages' => ['index.php']
    ],
    [
        'name' => 'Produse',
        'url' => '/InventoryManager/pages/products.php',
        'pages' => [
            'products.php',
            'add_product.php',
            'edit_product.php'
        ]
    ],
    [
        'name' => 'Categorii',
        'url' => '/InventoryManager/pages/categories.php',
        'pages' => ['categories.php']
    ],
    [
        'name' => 'Furnizori',
        'url' => '/InventoryManager/pages/suppliers.php',
        'pages' => ['suppliers.php']
    ],
    [
        'name' => 'Mișcări de stoc',
        'url' => '/InventoryManager/pages/stock_movements.php',
        'pages' => ['stock_movements.php']
    ]
];
?>

<aside class="col-md-3 col-lg-2 sidebar">

    <h3>StockFlow</h3>

    <?php foreach ($menu as $item): ?>

        <?php
        $active = in_array(
            $currentPage,
            $item['pages'],
            true
        );
        ?>

        <a href="<?= htmlspecialchars(
            $item['url'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
           class="<?= $active ? 'active' : '' ?>"
           <?= $active ? 'aria-current="page"' : '' ?>>

            <?= htmlspecialchars(
                $item['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </a>

    <?php endforeach; ?>

</aside>