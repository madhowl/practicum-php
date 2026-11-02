<?php

use App\Category;
use App\Html;

// Общие HTML-«обёртки» страниц. Это обычные функции, а не классы:
// они отвечают только за разметку.
function page_header(string $title, string $active = ''): void
{
    $isIndex = $active === 'index' ? ' class="active"' : '';
    $isAdd = $active === 'add' ? ' class="active"' : '';
    ?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Html::e($title) ?> — Каталог товаров</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #f4f5f7; color: #1f2933; }
    header.site { background: #1f2933; color: #fff; padding: 12px 24px; display: flex; gap: 20px; align-items: baseline; }
    header.site strong { font-size: 18px; }
    header.site a { color: #cbd2d9; text-decoration: none; margin-right: 14px; }
    header.site a.active, header.site a:hover { color: #fff; text-decoration: underline; }
    main { max-width: 960px; margin: 24px auto; padding: 0 24px; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { border: 1px solid #d9e2ec; padding: 8px 10px; text-align: left; font-size: 14px; }
    th { background: #e6eef7; }
    .stats { margin: 12px 0; color: #52606d; font-size: 14px; }
    .banner { padding: 10px 14px; border-radius: 6px; margin: 12px 0; }
    .banner.success { background: #d3f9d8; color: #216e39; }
    .banner.error { background: #ffe3e3; color: #b42318; }
    form.inline { display: inline; }
    .filters { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; margin: 16px 0; }
    .filters label { display: flex; flex-direction: column; font-size: 13px; color: #52606d; gap: 4px; }
    input, select { padding: 6px 8px; border: 1px solid #bcccdc; border-radius: 4px; font-size: 14px; }
    button { padding: 8px 14px; border: 0; border-radius: 4px; background: #2b6cb0; color: #fff; font-size: 14px; cursor: pointer; }
    button.danger { background: #b42318; }
    a.action { color: #2b6cb0; }
    .errors { color: #b42318; font-size: 13px; margin-top: 4px; }
    .pager { margin: 16px 0; display: flex; gap: 8px; align-items: center; }
    .pager a, .pager span { padding: 4px 10px; border: 1px solid #bcccdc; border-radius: 4px; text-decoration: none; background: #fff; }
    .pager span.current { background: #2b6cb0; color: #fff; border-color: #2b6cb0; }
    .card { background: #fff; padding: 16px; border-radius: 6px; }
    .field { margin-bottom: 12px; display: flex; flex-direction: column; gap: 4px; max-width: 360px; }
</style>
</head>
<body>
<header class="site">
    <strong>Каталог товаров</strong>
    <nav>
        <a href="index.php"<?= $isIndex ?>>Каталог</a>
        <a href="add.php"<?= $isAdd ?>>Добавить товар</a>
    </nav>
</header>
<main>
<?php
}

function page_footer(): void
{
    ?></main>
</body>
</html><?php
}

// Сообщение об успешной операции (PRG): читаем флаг из адреса.
function flash_message(): ?array
{
    if (isset($_GET['added'])) {
        return ['success', 'Товар #' . (int) $_GET['added'] . ' добавлен'];
    }
    if (isset($_GET['updated'])) {
        return ['success', 'Товар #' . (int) $_GET['updated'] . ' обновлён'];
    }
    if (isset($_GET['deleted'])) {
        return ['success', 'Товар #' . (int) $_GET['deleted'] . ' удалён'];
    }
    return null;
}

function banner(string $type, string $text): void
{
    echo '<div class="banner ' . Html::e($type) . '">' . Html::e($text) . '</div>';
}

// Ссылка с сохранением текущих GET-параметров (поиск, фильтр, сортировка).
function query_url(array $params): string
{
    $all = array_merge($_GET, $params);
    return 'index.php?' . http_build_query($all);
}

// Форма добавления/редактирования товара. $values — что подставить в поля,
// $errors — сообщения под полями.
function product_form(array $values, array $errors, string $action, string $submitLabel): void
{
    $value = fn (string $key, string $default = ''): string => Html::e((string) ($values[$key] ?? $default));
    $error = fn (string $key): string => isset($errors[$key])
        ? '<span class="errors">' . Html::e($errors[$key]) . '</span>'
        : '';

    $selected = (string) ($values['category'] ?? '');
    $options = '';
    foreach (Category::cases() as $category) {
        $checked = $category->value === $selected ? ' selected' : '';
        $options .= '<option value="' . Html::e($category->value) . '"' . $checked . '>'
            . Html::e($category->label()) . '</option>';
    }
    ?>
    <form class="card" method="post" action="<?= Html::e($action) ?>">
        <div class="field">
            <label>Название</label>
            <input type="text" name="title" value="<?= $value('title') ?>">
            <?= $error('title') ?>
        </div>
        <div class="field">
            <label>Категория</label>
            <select name="category"><option value="">— выберите —</option><?= $options ?></select>
            <?= $error('category') ?>
        </div>
        <div class="field">
            <label>Цена, ₽</label>
            <input type="text" name="price" value="<?= $value('price') ?>">
            <?= $error('price') ?>
        </div>
        <div class="field">
            <label>Остаток</label>
            <input type="text" name="stock" value="<?= $value('stock', '0') ?>">
            <?= $error('stock') ?>
        </div>
        <div class="field">
            <label>Скидка, %</label>
            <input type="text" name="discount" value="<?= $value('discount', '0') ?>">
            <?= $error('discount') ?>
        </div>
        <button type="submit"><?= Html::e($submitLabel) ?></button>
    </form>
    <?php
}
