<?php

// =====================================================================
// Каталог товаров: работа с массивами и функциями.
// Основа будущей витрины — набор функций, которые фильтруют, ищут,
// сортируют и «нарезают» каталог на страницы.
// =====================================================================

// Тот же каталог, что в работе 1.
$catalog = [
    ['title' => 'Ноутбук Lenovo IdeaPad', 'category' => 'Техника',     'price' => 54990, 'stock' => 12, 'discount' => 10],
    ['title' => 'Смартфон Xiaomi Redmi',  'category' => 'Техника',     'price' => 21990, 'stock' => 30, 'discount' => 5],
    ['title' => 'Наушники JBL Tune',      'category' => 'Аксессуары',  'price' => 3490,  'stock' => 0,  'discount' => 0],
    ['title' => 'Клавиатура Logitech',    'category' => 'Аксессуары',  'price' => 2790,  'stock' => 45, 'discount' => 15],
    ['title' => 'Мышь беспроводная',      'category' => 'Аксессуары',  'price' => 1290,  'stock' => 80, 'discount' => 0],
    ['title' => 'Игровая приставка PS5',  'category' => 'Игры',        'price' => 47990, 'stock' => 6,  'discount' => 0],
];

// Цена со скидкой (из работы 1).
function price_with_discount(int $price, int $discount): float
{
    if ($discount <= 0) {
        return (float) $price;
    }
    return round($price * (100 - $discount) / 100, 2);
}

// --- Фильтры ---------------------------------------------------------

// Оставляет товары только выбранной категории.
// Если категория пустая или каталог надо показать целиком — вернём всё.
function filter_by_category(array $catalog, ?string $category): array
{
    if ($category === null || $category === '' || $category === 'all') {
        return $catalog;
    }

    $result = [];
    foreach ($catalog as $product) {
        if ($product['category'] === $category) {
            $result[] = $product;
        }
    }
    return $result;
}

// Оставляет товары в диапазоне цен: от $min (включительно) до $max (включительно).
// Если $max не задан — верхней границы нет.
function filter_by_price(array $catalog, float $min = 0.0, ?float $max = null): array
{
    $result = [];
    foreach ($catalog as $product) {
        if ($product['price'] < $min) {
            continue;
        }
        if ($max !== null && $product['price'] > $max) {
            continue;
        }
        $result[] = $product;
    }
    return $result;
}

// --- Поиск -----------------------------------------------------------

// Поиск по названию без учёта регистра.
function search_by_name(array $catalog, string $query): array
{
    $query = mb_strtolower(trim($query));
    if ($query === '') {
        return $catalog;
    }

    $result = [];
    foreach ($catalog as $product) {
        if (str_contains(mb_strtolower($product['title']), $query)) {
            $result[] = $product;
        }
    }
    return $result;
}

// --- Сортировка ------------------------------------------------------

// Сортировка по цене. Направление: 'asc' — от дешёвых, 'desc' — от дорогих.
function sort_by_price(array $catalog, string $direction = 'asc'): array
{
    usort($catalog, function (array $a, array $b) use ($direction): int {
        if ($direction === 'desc') {
            return $b['price'] <=> $a['price'];
        }
        return $a['price'] <=> $b['price'];
    });
    return $catalog;
}

// Сортировка по названию (по алфавиту, без регистра).
function sort_by_title(array $catalog, string $direction = 'asc'): array
{
    usort($catalog, function (array $a, array $b) use ($direction): int {
        $cmp = strcmp(mb_strtolower($a['title']), mb_strtolower($b['title']));
        return $direction === 'desc' ? -$cmp : $cmp;
    });
    return $catalog;
}

// --- Пагинация -------------------------------------------------------

// Делит список товаров на страницы. Возвращает сразу всё нужное для разметки:
// сами товары текущей страницы, общее количество и число страниц.
function paginate(array $items, int $page, int $perPage): array
{
    $total = count($items);
    $totalPages = max(1, (int) ceil($total / $perPage));

    // Текущая страница не может выходить за допустимый диапазон.
    $page = max(1, min($page, $totalPages));

    $start = ($page - 1) * $perPage;
    $items = array_slice($items, $start, $perPage);

    return [
        'items'      => $items,
        'total'      => $total,
        'page'       => $page,
        'totalPages' => $totalPages,
    ];
}

// --- Вспомогательное --------------------------------------------------

// Уникальные категории каталога (для выпадающего списка фильтров).
function unique_categories(array $catalog): array
{
    $categories = [];
    foreach ($catalog as $product) {
        $categories[$product['category']] = true;
    }
    return array_keys($categories);
}

// Демонстрация работы функций.
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    echo "Каталог целиком (" . count($catalog) . " шт.):\n";
    foreach ($catalog as $p) {
        echo "  - {$p['title']} ({$p['category']}, {$p['price']} ₽)\n";
    }

    echo "\nКатегория «Техника»:\n";
    foreach (filter_by_category($catalog, 'Техника') as $p) {
        echo "  - {$p['title']}\n";
    }

    echo "\nТовары дороже 3000 ₽ и дешевле 30000 ₽:\n";
    foreach (filter_by_price($catalog, 3000, 30000) as $p) {
        echo "  - {$p['title']} ({$p['price']} ₽)\n";
    }

    echo "\nПоиск по «jbl»:\n";
    foreach (search_by_name($catalog, 'jbl') as $p) {
        echo "  - {$p['title']}\n";
    }

    echo "\nТовары по возрастанию цены:\n";
    foreach (sort_by_price($catalog, 'asc') as $p) {
        echo "  - {$p['title']} ({$p['price']} ₽)\n";
    }

    echo "\nСтраница 1 по 3 товара на странице:\n";
    foreach (paginate($catalog, 1, 3)['items'] as $p) {
        echo "  - {$p['title']}\n";
    }

    echo "\nКатегории: " . implode(', ', unique_categories($catalog)) . "\n";
}