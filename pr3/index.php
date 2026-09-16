<?php
require __DIR__ . '/functions.php';
$catalog = require __DIR__ . '/data.php';

// Полный каталог (для списка категорий), рабочая копия — для вывода.
$fullCatalog = $catalog;

// Параметры из адресной строки (?search=...&category=...).
$search   = trim((string) ($_GET['search'] ?? ''));
$category = (string) ($_GET['category'] ?? 'all');

// Обработка отправки формы добавления товара (POST).
$errors = [];
$added  = null;    // сюда попадёт валидный товар
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validate_product($_POST);
    if ($errors === []) {
        $added = sanitize_product($_POST);
    }
}

// Формируем список для показа: поиск + фильтр по категории.
$items = search_by_name($catalog, $search);
$items = filter_by_category($items, $category);

$categories = unique_categories($fullCatalog);

// Только что добавленный товар показываем первой строкой.
if ($added !== null) {
    array_unshift($items, $added);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Каталог товаров — работа 3</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 960px; margin: 32px auto; padding: 0 16px; color: #222; }
        h1 { font-size: 1.4rem; }
        form { background: #f6f6f6; border: 1px solid #ddd; border-radius: 8px; padding: 12px 14px; }
        form + form { margin-top: 20px; }
        label { display: block; margin: 8px 0 3px; font-size: .85rem; color: #555; }
        input, select { padding: 6px 8px; border: 1px solid #bbb; border-radius: 5px; font-size: .95rem; }
        button { margin-top: 12px; padding: 7px 16px; border: 0; border-radius: 5px; background: #2563eb; color: #fff; cursor: pointer; }
        .grid { display: flex; gap: 10px; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e5e5; font-size: .95rem; }
        th { background: #fafafa; }
        .error { display: block; margin-top: 4px; color: #c0392b; font-size: .8rem; }
        .notice { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; padding: 10px 14px; border-radius: 8px; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: .78rem; }
        .badge.ok { background: #e8f5e9; color: #1b5e20; }
        .badge.low { background: #fff3e0; color: #e65100; }
        .badge.no { background: #fdecea; color: #b71c1c; }
        .muted { color: #888; font-size: .85rem; }
    </style>
</head>
<body>
    <h1>Каталог товаров</h1>
    <p class="muted">Работа 3: строки и формы. Поиск и фильтры — через GET, добавление — через POST (без сохранения, это работа 4).</p>

    <?php if ($added !== null): ?>
        <p class="notice">Товар «<?= e($added['title']) ?>» прошёл валидацию. Сохранение появится в работе 4.</p>
    <?php endif; ?>

    <!-- Форма поиска и фильтра (GET) -->
    <form method="get">
        <div class="grid">
            <div>
                <label for="search">Поиск по названию</label>
                <input type="text" name="search" id="search" value="<?= e($search) ?>" placeholder="Например: наушники">
            </div>
            <div>
                <label for="category">Категория</label>
                <select name="category" id="category">
                    <option value="all">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="align-self:flex-end">
                <button type="submit">Показать</button>
                <a href="index.php" style="margin-left:8px; color:#2563eb;">Сбросить</a>
            </div>
        </div>
    </form>

    <!-- Таблица каталога -->
    <?php if ($items === []): ?>
        <p style="margin-top:24px">Ничего не найдено.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Название</th>
                <th>Категория</th>
                <th>Цена</th>
                <th>Скидка</th>
                <th>Итог</th>
                <th>Наличие</th>
            </tr>
            <?php foreach ($items as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['category']) ?></td>
                    <td><?= e(number_format((float) $p['price'], 0, ',', ' ')) ?> ₽</td>
                    <td><?= e($p['discount']) ?> %</td>
                    <td><?= e(number_format(price_with_discount((float) $p['price'], (int) $p['discount']), 2, ',', ' ')) ?> ₽</td>
                    <td>
                        <?php
                        $badge = match (true) {
                            (int) $p['stock'] === 0 => 'no',
                            (int) $p['stock'] <= 5  => 'low',
                            default                 => 'ok',
                        };
                        ?>
                        <span class="badge <?= $badge ?>"><?= e(stock_label((int) $p['stock'])) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="muted">Показано товаров: <?= count($items) ?></p>
    <?php endif; ?>

    <!-- Форма добавления товара (POST) -->
    <form method="post">
        <h2 style="font-size:1.05rem; margin:0 0 4px;">Добавить товар</h2>

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

        <button type="submit">Добавить в каталог</button>
    </form>
</body>
</html>