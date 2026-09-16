<?php
require __DIR__ . '/functions.php';
require __DIR__ . '/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$catalog = load_catalog();

$product = find_product($catalog, $id);
if ($product === null) {
    http_response_code(404);
    page_header('Товар не найден');
    echo '<h1>Товар не найден</h1>';
    echo '<p>Товара с id = ' . (int) $id . ' нет в каталоге.</p>';
    echo '<p><a href="index.php" style="color:#2563eb;">← Вернуться в каталог</a></p>';
    page_footer();
    exit;
}

// Удаляем только по явно отправленной форме подтверждения (POST),
// а не по простому переходу по ссылке.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catalog = delete_product($catalog, $id);
    save_catalog($catalog);
    header('Location: index.php?deleted=' . $id);
    exit;
}

page_header('Удаление товара');
?>
    <h1>Удаление товара</h1>

    <form method="post" style="max-width:420px">
        <p style="margin:6px 0 4px;"><strong><?= e($product['title']) ?></strong></p>
        <p class="muted" style="margin:0 0 10px;">
            Категория: <?= e($product['category']) ?> ·
            Цена: <?= e(number_format((float) $product['price'], 0, ',', ' ')) ?> ₽ ·
            Остаток: <?= e($product['stock']) ?> шт.
        </p>
        <p style="margin:0 0 4px;">Действие необратимо. Удалить товар из каталога?</p>
        <button type="submit" class="danger">Да, удалить</button>
        <a href="index.php" style="margin-left:12px; color:#2563eb;">Отмена</a>
    </form>
<?php
page_footer();