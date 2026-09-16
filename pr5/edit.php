<?php
require __DIR__ . '/functions.php';
require __DIR__ . '/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$catalog = load_catalog();
$categories = unique_categories($catalog);

// Нет такого товара — честный код 404 и понятное сообщение.
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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validate_product($_POST);
    if ($errors === []) {
        $catalog = update_product($catalog, $id, $_POST);
        save_catalog($catalog);
        header('Location: index.php?updated=' . $id);
        exit;
    }
}

// В форме сохраняем то, что пользователь ввёл; при первом открытии — данные товара.
$form = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? $_POST
    : [
        'title'    => $product['title'],
        'category' => $product['category'],
        'price'    => $product['price'],
        'stock'    => $product['stock'],
        'discount' => $product['discount'],
    ];

page_header('Редактирование товара');
?>
    <h1>Редактирование товара #<?= e($id) ?></h1>
    <a href="index.php" style="color:#2563eb;">← Назад в каталог</a>

    <form method="post">
        <label for="title">Название</label>
        <input type="text" name="title" id="title" value="<?= e($form['title'] ?? '') ?>">
        <?php if (isset($errors['title'])): ?><span class="error"><?= e($errors['title']) ?></span><?php endif; ?>

        <label for="category">Категория</label>
        <select name="category" id="category">
            <option value="">— выберите —</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= ($form['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category'])): ?><span class="error"><?= e($errors['category']) ?></span><?php endif; ?>

        <div class="grid">
            <div>
                <label for="price">Цена, ₽</label>
                <input type="text" name="price" id="price" value="<?= e($form['price'] ?? '') ?>">
                <?php if (isset($errors['price'])): ?><span class="error"><?= e($errors['price']) ?></span><?php endif; ?>
            </div>
            <div>
                <label for="stock">Остаток</label>
                <input type="text" name="stock" id="stock" value="<?= e($form['stock'] ?? '') ?>">
                <?php if (isset($errors['stock'])): ?><span class="error"><?= e($errors['stock']) ?></span><?php endif; ?>
            </div>
            <div>
                <label for="discount">Скидка, %</label>
                <input type="text" name="discount" id="discount" value="<?= e($form['discount'] ?? '') ?>">
                <?php if (isset($errors['discount'])): ?><span class="error"><?= e($errors['discount']) ?></span><?php endif; ?>
            </div>
        </div>

        <button type="submit">Сохранить изменения</button>
    </form>
<?php
page_footer();