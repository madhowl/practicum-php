<?php

use App\Html;
use App\ProductNotFoundException;

require __DIR__ . '/app.php';
require __DIR__ . '/layout.php';

$id = (int) ($_GET['id'] ?? 0);

// Репозиторий бросает исключение, если товара нет. Ловим и отдаём 404.
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

$errors = [];
$values = $product->toArray();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = $_POST;
    $errors = $validator->validate($_POST);

    if ($errors === []) {
        $repository->update($id, $_POST);
        header('Location: index.php?updated=' . $id);
        exit;
    }
}

page_header('Изменить товар #' . $id);
?>

<h1>Изменить товар #<?= Html::e((string) $id) ?></h1>
<?php if ($errors !== []): ?>
    <div class="banner error">Исправьте ошибки в форме</div>
<?php endif; ?>

<?php product_form($values, $errors, 'edit.php?id=' . $id, 'Сохранить'); ?>

<?php
page_footer();
