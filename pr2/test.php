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

require __DIR__ . '/solution.php';

echo "Работа 2. Массивы и функции\n\n";

// --- filter_by_category ---
$tech = filter_by_category($catalog, 'Техника');
check('фильтр «Техника» вернул 2 товара', count($tech), 2);
check('первый товар техники', $tech[0]['title'], 'Ноутбук Lenovo IdeaPad');
check('фильтр «all» возвращает весь каталог', count(filter_by_category($catalog, 'all')), count($catalog));
check('фильтр пустой строки возвращает весь каталог', count(filter_by_category($catalog, '')), count($catalog));
check('несуществующая категория даёт пустой результат', filter_by_category($catalog, 'Обувь'), []);
foreach ($tech as $p) {
    check_true('каждый результат относится к «Техника»', $p['category'] === 'Техника');
}

// --- filter_by_price ---
$priceRange = filter_by_price($catalog, 3000, 30000);
check('диапазон 3000–30000 не пуст', count($priceRange) > 0, true);
foreach ($priceRange as $p) {
    check_true('цена внутри диапазона [3000..30000]', $p['price'] >= 3000 && $p['price'] <= 30000);
}
check('слишком дорогие не попадают в диапазон до 1000', filter_by_price($catalog, 0, 1000), []);
check('нижняя граница включительно', count(filter_by_price($catalog, 21990, 21995)), 1);

// --- search_by_name ---
check('поиск «jbl» (нижний регистр) находит наушники', count(search_by_name($catalog, 'jbl')), 1);
check('поиск «JBL» (верхний регистр) — тот же результат', count(search_by_name($catalog, 'JBL')), 1);
check('поиск «приставка» находит PS5', search_by_name($catalog, 'приставка')[0]['title'], 'Игровая приставка PS5');
check('пустой запрос = весь каталог', count(search_by_name($catalog, '  ')), count($catalog));
check('нет совпадений', search_by_name($catalog, 'зубчатый'), []);

// --- sort_by_price ---
$pricesAsc = array_column(sort_by_price($catalog, 'asc'), 'price');
$expectedAsc = $pricesAsc;
sort($expectedAsc);
check('цена по возрастанию отсортирована', $pricesAsc, $expectedAsc);

$pricesDesc = array_column(sort_by_price($catalog, 'desc'), 'price');
$expectedDesc = $pricesDesc;
rsort($expectedDesc);
check('цена по убыванию отсортирована', $pricesDesc, $expectedDesc);

// --- sort_by_title ---
check('первая по алфавиту', sort_by_title($catalog)[0]['title'], 'Игровая приставка PS5');

// --- paginate ---
$page3 = paginate($catalog, 1, 4);
check('страница 1 из 2 страниц', $page3['page'], 1);
check('на 1-й странице 4 товара', count($page3['items']), 4);
$page2 = paginate($catalog, 2, 4);
check('на 2-й странице осталось 2 товара', count($page2['items']), 2);
check('найдено 2 страницы', $page3['totalPages'], 2);
check('всего товаров не потеряно', $page3['total'], count($catalog));
$lastPage = paginate($catalog, 2, 4);
$titles = array_column($lastPage['items'], 'title');
check_true('на последней странице есть приставка PS5', in_array('Игровая приставка PS5', $titles, true));
check('страница ноль превращается в первую', paginate($catalog, 0, 3)['page'], 1);
check('слишком большой номер страницы ограничивается', paginate($catalog, 99, 3)['page'], 2);

// --- unique_categories ---
$cats = unique_categories($catalog);
sort($cats);
check('уникальные категории каталога', $cats, ['Аксессуары', 'Игры', 'Техника']);

// --- комбинации ---
$combined = filter_by_category($catalog, 'Аксессуары');
$combined = filter_by_price($combined, 1000, 4000);
$combined = search_by_name($combined, 'мышь');
check('комбинация фильтр+цена+поиск', count($combined), 1);
check('нашлась беспроводная мышь', $combined[0]['title'], 'Мышь беспроводная');

// --- итог ---
echo "\nИтого проверок: {$GLOBALS['tests']}, ошибок: {$GLOBALS['failed']}\n";
exit($GLOBALS['failed'] > 0 ? 1 : 0);