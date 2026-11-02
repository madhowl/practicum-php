<?php

use App\Html;

require __DIR__ . '/app.php';
require __DIR__ . '/layout.php';

$errors = [];
$values = ['title' => '', 'category' => '', 'price' => '', 'stock' => '0', 'discount' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = $_POST;
    $errors = $validator->validate($_POST);

    if ($errors === []) {
        $product = $repository->create($_POST);
        // PRG: после изменения — редирект, чтобы F5 не отправлял форму повторно.
        header('Location: index.php?added=' . $product->id);
        exit;
    }
}

page_header('Добавить товар', 'add');
?>

<h1>Добавить товар</h1>
<?php if ($errors !== []): ?>
    <div class="banner error">Исправьте ошибки в форме</div>
<?php endif; ?>

<?php product_form($values, $errors, 'add.php', 'Добавить'); ?>

<?php
page_footer();
