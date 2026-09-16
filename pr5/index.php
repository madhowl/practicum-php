<?php
require __DIR__ . '/functions.php';
require __DIR__ . '/layout.php';

$catalog = load_catalog();
$fullCatalog = $catalog;

// Параметры из адресной строки.
$search   = trim((string) ($_GET['search'] ?? ''));
$category = (string) ($_GET['category'] ?? 'all');
$sort     = (string) ($_GET['sort'] ?? 'price_asc');
$sort     = in_array($sort, ['price_asc', 'price_desc', 'title', 'stock'], true) ? $sort : 'price_asc';
$page     = max(1, (int) ($_GET['page'] ?? 1));

// Выборка: поиск -> фильтр -> сортировка -> страница.
$items = search_by_name($catalog, $search);
$items = filter_by_category($items, $category);
$items = match ($sort) {
    'price_desc' => sort_by_price($items, 'desc'),
    'title'      => sort_by_title($items),
    'stock'      => sort_by_stock($items, 'desc'),
    default      => sort_by_price($items, 'asc'),
};
$paged = paginate($items, $page);

$categories = unique_categories($fullCatalog);
$stats = catalog_stats($fullCatalog);

$message = null;
if (isset($_GET['added']) && (int) $_GET['added'] > 0) {
    $message = 'Товар #' . (int) $_GET['added'] . ' добавлен.';
} elseif (isset($_GET['updated']) && (int) $_GET['updated'] > 0) {
    $message = 'Товар #' . (int) $_GET['updated'] . ' обновлён.';
} elseif (isset($_GET['deleted']) && (int) $_GET['deleted'] > 0) {
    $message = 'Товар #' . (int) $_GET['deleted'] . ' удалён.';
}

page_header();

banner(null, $message);
?>
    <h1>Каталог товаров</h1>
    <div class="stats">
        <div><b><?= $stats['products'] ?></b> товаров</div>
        <div><b><?= $stats['stock'] ?></b> единиц на складе</div>
        <div><b><?= e(number_format($stats['value'], 0, ',', ' ')) ?> ₽</b> стоимость склада</div>
    </div>

    <!-- Фильтры -->
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
            <div>
                <label for="sort">Сортировка</label>
                <select name="sort" id="sort">
                    <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Сначала дешевле</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Сначала дороже</option>
                    <option value="title"      <?= $sort === 'title'      ? 'selected' : '' ?>>По алфавиту</option>
                    <option value="stock"      <?= $sort === 'stock'      ? 'selected' : '' ?>>По остатку</option>
                </select>
            </div>
            <div style="align-self:flex-end">
                <button type="submit">Показать</button>
                <a href="index.php" style="margin-left:8px; color:#2563eb;">Сбросить</a>
            </div>
        </div>
    </form>

    <?php if ($paged['items'] === []): ?>
        <p class="empty">Ничего не найдено.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>#</th>
                <th>Название</th>
                <th>Категория</th>
                <th>Цена</th>
                <th>Скидка</th>
                <th>Итог</th>
                <th>Остаток</th>
                <th></th>
            </tr>
            <?php foreach ($paged['items'] as $p): ?>
                <tr>
                    <td><?= e($p['id']) ?></td>
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
                    <td class="actions">
                        <a href="edit.php?id=<?= e($p['id']) ?>">Изменить</a>
                        <a class="del" href="delete.php?id=<?= e($p['id']) ?>">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="pager">
            <?php if ($paged['totalPages'] > 1): ?>
                <?php for ($i = 1; $i <= $paged['totalPages']; $i++): ?>
                    <?php if ($i === $paged['page']): ?>
                        <span class="cur"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= e(query_url(['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <span class="muted">— <?= $paged['total'] ?> шт.</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php
page_footer();