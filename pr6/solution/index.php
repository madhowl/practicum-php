<?php

use App\Category;
use App\Html;

require __DIR__ . '/app.php';
require __DIR__ . '/layout.php';

// 1. Загружаем весь каталог через репозиторий.
$catalog = $repository->load();

// 2. Читаем параметры из адреса и «зажимаем» их в безопасные значения.
$search = (string) ($_GET['search'] ?? '');
$sort = (string) ($_GET['sort'] ?? 'price_asc');
$sort = in_array($sort, ['price_asc', 'price_desc', 'title', 'stock'], true) ? $sort : 'price_asc';
$page = max(1, (int) ($_GET['page'] ?? 1));

$categoryParam = (string) ($_GET['category'] ?? 'all');
$category = $categoryParam === 'all' ? null : Category::tryFrom($categoryParam);

// 3. Поиск -> фильтр -> сортировка -> пагинация. Каждый шаг возвращает
//    новый каталог, поэтому вызовы можно выстроить в цепочку.
$filtered = $catalog->search($search);
if ($category !== null) {
    $filtered = $filtered->filterByCategory($category);
}

$filtered = match ($sort) {
    'price_desc' => $filtered->sortByPrice('desc'),
    'title' => $filtered->sortByTitle(),
    'stock' => $filtered->sortByStock('desc'),
    default => $filtered->sortByPrice('asc'),
};

$pagination = $filtered->paginate($page, PER_PAGE);
$stats = $catalog->stats();

page_header('Каталог', 'index');

$flash = flash_message();
if ($flash !== null) {
    banner($flash[0], $flash[1]);
}
?>

<h1>Каталог товаров</h1>
<p class="stats">
    Всего товаров: <?= Html::e((string) $stats['products']) ?>,
    на складе: <?= Html::e((string) $stats['stock']) ?> шт.,
    стоимость склада: <?= Html::e(number_format((float) $stats['warehouse'], 2, ',', ' ')) ?> ₽,
    со скидкой: <?= Html::e((string) $stats['discounted']) ?>
</p>

<form class="filters" method="get" action="index.php">
    <label>Поиск
        <input type="text" name="search" value="<?= Html::e($search) ?>" placeholder="например, игр">
    </label>
    <label>Категория
        <select name="category">
            <option value="all"<?= $category === null ? ' selected' : '' ?>>Все категории</option>
            <?php foreach (Category::cases() as $item): ?>
                <option value="<?= Html::e($item->value) ?>"<?= $category === $item ? ' selected' : '' ?>>
                    <?= Html::e($item->label()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Сортировка
        <select name="sort">
            <option value="price_asc"<?= $sort === 'price_asc' ? ' selected' : '' ?>>Цена ↑</option>
            <option value="price_desc"<?= $sort === 'price_desc' ? ' selected' : '' ?>>Цена ↓</option>
            <option value="title"<?= $sort === 'title' ? ' selected' : '' ?>>По алфавиту</option>
            <option value="stock"<?= $sort === 'stock' ? ' selected' : '' ?>>По остатку</option>
        </select>
    </label>
    <button type="submit">Показать</button>
</form>

<?php if ($pagination['items'] === []): ?>
    <div class="banner error">Ничего не найдено</div>
<?php else: ?>
    <table>
        <tr>
            <th>#</th><th>Название</th><th>Категория</th><th>Цена</th>
            <th>Скидка</th><th>Цена со скидкой</th><th>Остаток</th><th></th>
        </tr>
        <?php foreach ($pagination['items'] as $product): ?>
            <tr>
                <td><?= Html::e((string) $product->id) ?></td>
                <td><?= Html::e($product->title) ?></td>
                <td><?= Html::e($product->category->label()) ?></td>
                <td><?= Html::e(number_format($product->price, 0, ',', ' ')) ?> ₽</td>
                <td><?= Html::e((string) $product->discount) ?> %</td>
                <td><?= Html::e(number_format($product->priceWithDiscount(), 2, ',', ' ')) ?> ₽</td>
                <td><?= Html::e($product->stockLabel()) ?></td>
                <td>
                    <a class="action" href="edit.php?id=<?= Html::e((string) $product->id) ?>">Изменить</a>
                    <a class="action" href="delete.php?id=<?= Html::e((string) $product->id) ?>">Удалить</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if ($pagination['totalPages'] > 1): ?>
    <div class="pager">
        <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
            <?php if ($i === $pagination['page']): ?>
                <span class="current"><?= Html::e((string) $i) ?></span>
            <?php else: ?>
                <a href="<?= Html::e(query_url(['page' => $i])) ?>"><?= Html::e((string) $i) ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php
page_footer();
