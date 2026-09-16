<?php
require __DIR__ . '/functions.php';
require __DIR__ . '/layout.php';

$catalog = load_catalog();
$categories = unique_categories($catalog);

// Никакие данные ещё не сохраняем: только валидация и подготовка.
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validate_product($_POST);
    if ($errors === []) {
        $catalog = add_product($catalog, $_POST);
        save_catalog($catalog);
        $newId = max(array_column($catalog, 'id'));
        header('Location: index.php?added=' . $newId);
        exit;
    }
}

page_header();
?>
    <h1>Добавить товар</h1>
    <a href="index.php" style="color:#2563eb;">← Назад в каталог</a>

    <form method="post">
        <label for="title">Название</label>
        <input type="text" name="title" id="title" value="<?= e($_POST['title'] ?? '') ?>">
        <?php if (isset($errors['title'])): ?><span class="error"><?= e($errors['title']) ?></span><?php endif; ?>

        <label for="category">Категория</label>
        <select name="category" id="category">
            <option value="">— выберите —</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category'])): ?><span class="error"><?= e($errors['category']) ?></span><?php endif; ?>

        <div class="grid">
            <div>
                <label for="price">Цена, ₽</label>
                <input type="text" name="price" id="price" value="<?= e($_POST['price'] ?? '') ?>">
                <?php if (isset($errors['price'])): ?><span class="error"><?= e($errors['price']) ?></span><?php endif; ?>
            </div>
            <div>
                <label for="stock">Остаток</label>
                <input type="text" name="stock" id="stock" value="<?= e($_POST['stock'] ?? '') ?>">
                <?php if (isset($errors['stock'])): ?><span class="error"><?= e($errors['stock']) ?></span><?php endif; ?>
            </div>
            <div>
                <label for="discount">Скидка, %</label>
                <input type="text" name="discount" id="discount" value="<?= e($_POST['discount'] ?? '') ?>">
                <?php if (isset($errors['discount'])): ?><span class="error"><?= e($errors['discount']) ?></span><?php endif; ?>
            </div>
        </div>

        <button type="submit">Сохранить</button>
    </form>
<?php
page_footer();