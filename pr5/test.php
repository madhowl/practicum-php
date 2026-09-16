<?php

$GLOBALS['tests'] = 0;
$GLOBALS['failed'] = 0;

function check(string $name, mixed $actual, mixed $expected): void
{
    $GLOBALS['tests']++;
    if ($actual === $expected) {
        echo "  PASS  $name\n";
        return;
    }
    $GLOBALS['failed']++;
    echo "  FAIL  $name\n";
    echo "        expected: " . var_export($expected, true) . "\n";
    echo "        actual:   " . var_export($actual, true) . "\n";
}

function check_true(string $name, bool $condition): void
{
    check($name, $condition, true);
}

require __DIR__ . '/functions.php';

echo "Работа 5. Финальный CRUD\n\n";

// CRUD-функции работают с переданным каталогом и НЕ трогают файл.
$catalog = default_catalog();

// --- find_product ---
$p = find_product($catalog, 3);
check('find_product находит товар #3', $p['title'], 'Наушники JBL Tune');
check('find_product возвращает null для неизвестного id', find_product($catalog, 999), null);

// --- next_id ---
check('next_id для каталога из 8 товаров', next_id($catalog), 9);

// --- add_product ---
$data = ['title' => 'Телевизор Samsung', 'category' => 'Техника', 'price' => '39990', 'stock' => '7', 'discount' => '10'];
$catalog = add_product($catalog, $data);
check('после добавления стало 9 товаров', count($catalog), 9);
$last = $catalog[8];
check('новый товар получил id = 9', $last['id'], 9);
check('цена стала числом', $last['price'], 39990);
check('остаток стал числом', $last['stock'], 7);
check_true('id в каталоге не повторяются',
    count(array_unique(array_column($catalog, 'id'))) === count($catalog));

// --- update_product ---
$update = ['title' => 'Телевизор Samsung QLED', 'category' => 'Техника', 'price' => '45990', 'stock' => '3', 'discount' => '15'];
$updated = update_product($catalog, 9, $update);
$p = find_product($updated, 9);
check('update меняет название', $p['title'], 'Телевизор Samsung QLED');
check('update меняет цену', $p['price'], 45990);
check('update сохраняет id', $p['id'], 9);
check('update не трогает остальные товары', count($updated), 9);
$p8 = find_product($updated, 8);
check('товар #8 не пострадал', $p8['title'], 'Зарядное устройство USB');
$untouched = update_product($updated, 999, $update);
check('update несуществующего id ничего не меняет', $untouched, $updated);

// --- delete_product ---
$deleted = delete_product($updated, 9);
check('после удаления стало 8 товаров', count($deleted), 8);
check('удалённого товара больше нет', find_product($deleted, 9), null);
check_true('после удаления ключи = 0,1,2... (array_values)',
    array_keys($deleted) === range(0, 7));
check('удаление несуществующего id безопасно', delete_product($deleted, 999), $deleted);
$twice = delete_product($deleted, 3);
check('можно удалить второй товар подряд', count($twice), 7);

// --- поиск / фильтры / сортировка / пагинация ---
$catalog = default_catalog();

check('поиск «игр» — 2 товара', count(search_by_name($catalog, 'игр')), 2);
check('фильтр «Игры» — 2 товара', count(filter_by_category($catalog, 'Игры')), 2);
$asc = array_column(sort_by_price($catalog, 'asc'), 'price');
$expected = $asc;
sort($expected);
check('сортировка по цене', $asc, $expected);
$byTitle = sort_by_title($catalog);
check('сортировка по названию (З < И)', $byTitle[0]['title'], 'Зарядное устройство USB');
$byStock = array_column(sort_by_stock($catalog, 'desc'), 'stock');
$expectedStock = $byStock;
rsort($expectedStock);
check('сортировка по остатку', $byStock, $expectedStock);

$pg = paginate($catalog, 2, PER_PAGE);
check('пагинация: 2 страницы при PER_PAGE=5', $pg['totalPages'], 2);
check('пагинация: на 2-й странице 3 товара', count($pg['items']), 3);
check('пагинация: страница не выходит за границы', paginate($catalog, 42, PER_PAGE)['page'], $pg['totalPages']);

// --- валидация ---
$valid = ['title' => 'Игровая консоль', 'category' => 'Игры', 'price' => '25000', 'stock' => '2', 'discount' => '0'];
check('валидные данные без ошибок', validate_product($valid), []);
check_true('пустое название — ошибка', isset(validate_product(['title' => ''])['title']));
check_true('отрицательная цена — ошибка', isset(validate_product(['title' => 'X', 'category' => 'Игры', 'price' => '-1'])['price']));
check_true('скидка свыше 100 — ошибка', isset(validate_product(['title' => 'X', 'category' => 'Игры', 'price' => '1', 'discount' => '110'])['discount']));

// --- файл: round-trip на временном файле ---
$tmp = sys_get_temp_dir() . '/pr5_catalog_' . uniqid() . '.json';
check_true('сохранение в temp-файл', save_catalog($catalog, $tmp));
check('загрузка из temp-файла идентична', load_catalog($tmp), $catalog);
unlink($tmp);

check('хранение работает на постоянстве default_catalog', count(default_catalog()), 8);
$catStat = catalog_stats(default_catalog());
check('статистика: товаров 8', $catStat['products'], 8);
check('статистика: остаток 226', $catStat['stock'], 226);

// --- e() ---
check_true('escape защищает от <script>', !str_contains(e('<script>x</script>'), '<script>'));

// --- итог ---
echo "\nИтого проверок: {$GLOBALS['tests']}, ошибок: {$GLOBALS['failed']}\n";
exit($GLOBALS['failed'] > 0 ? 1 : 0);