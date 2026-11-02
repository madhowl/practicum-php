<?php

use App\Html;
use App\ProductNotFoundException;

require __DIR__ . '/app.php';
require __DIR__ . '/layout.php';

$id = (int) ($_GET['id'] ?? 0);

try {
    $product = $repository->find($id);
} catch (ProductNotFoundException) {
    http_response_code(404);
    page_header('Товар не найден');
    echo '<div class="banner error">Товар #' . Html::e((string) $id) . ' не найден</div>';
    echo '<p><a class="action" href="index.php">Вернуться в каталог</a></p>';
    page_footer();
    exit;
}

// Удаляем только на POST — чтобы нельзя было удалить товар случайной ссылкой.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repository->delete($id);
    header('Location: index.php?deleted=' . $id);
    exit;
}

page_header('Удалить товар');
?>

<h1>Удалить товар?</h1>
<div class="card">
    <p><strong><?= Html::e($product->title) ?></strong></p>
    <p>Категория: <?= Html::e($product->category->label()) ?>,
        цена: <?= Html::e(number_format($product->price, 0, ',', ' ')) ?> ₽</p>
    <form class="inline" method="post" action="delete.php?id=<?= Html::e((string) $id) ?>">
        <button class="danger" type="submit">Да, удалить</button>
    </form>
    <a class="action" href="index.php">Отмена</a>
</div>

<?php
page_footer();
