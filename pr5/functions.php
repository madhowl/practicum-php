<?php

// =====================================================================
// Каталог товаров: итоговое приложение.
// Здесь собраны все наработки работ 1–4: модель (файлы + JSON),
// функции работы с массивом и CRUD-операции.
// =====================================================================

const CATALOG_FILE = __DIR__ . '/data/catalog.json';
const PER_PAGE = 5;

// --------------------------- Модель: файл + JSON ---------------------

function default_catalog(): array
{
    return [
        ['id' => 1, 'title' => 'Ноутбук Lenovo IdeaPad',   'category' => 'Техника',    'price' => 54990, 'stock' => 12, 'discount' => 10],
        ['id' => 2, 'title' => 'Смартфон Xiaomi Redmi',    'category' => 'Техника',    'price' => 21990, 'stock' => 30, 'discount' => 5],
        ['id' => 3, 'title' => 'Наушники JBL Tune',        'category' => 'Аксессуары', 'price' => 3490,  'stock' => 0,  'discount' => 0],
        ['id' => 4, 'title' => 'Клавиатура Logitech',      'category' => 'Аксессуары', 'price' => 2790,  'stock' => 45, 'discount' => 15],
        ['id' => 5, 'title' => 'Мышь беспроводная',        'category' => 'Аксессуары', 'price' => 1290,  'stock' => 80, 'discount' => 0],
        ['id' => 6, 'title' => 'Игровая приставка PS5',    'category' => 'Игры',       'price' => 47990, 'stock' => 6,  'discount' => 0],
        ['id' => 7, 'title' => 'Настольная игра «Мафия»',  'category' => 'Игры',       'price' => 1590,  'stock' => 3,  'discount' => 5],
        ['id' => 8, 'title' => 'Зарядное устройство USB',  'category' => 'Аксессуары', 'price' => 890,   'stock' => 50, 'discount' => 0],
    ];
}

function save_catalog(array $catalog, string $file = CATALOG_FILE): bool
{
    $json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return @file_put_contents($file, $json, LOCK_EX) !== false;
}

function restore_default_catalog(string $file): array
{
    if (is_file($file)) {
        @rename($file, $file . '.broken.' . date('YmdHis'));
    }
    $catalog = default_catalog();
    save_catalog($catalog, $file);
    return $catalog;
}

function load_catalog(string $file = CATALOG_FILE): array
{
    if (!is_file($file) || !is_readable($file)) {
        return restore_default_catalog($file);
    }

    $json = file_get_contents($file);
    if ($json === false) {
        return restore_default_catalog($file);
    }

    if (!json_validate($json)) {
        return restore_default_catalog($file);
    }

    $catalog = json_decode($json, true);
    if (!is_array($catalog) || !array_is_list($catalog)) {
        return restore_default_catalog($file);
    }

    return $catalog;
}

function catalog_stats(array $catalog): array
{
    $stock = 0;
    $value = 0.0;
    foreach ($catalog as $product) {
        $stock += (int) $product['stock'];
        $value += (float) $product['price'] * (int) $product['stock'];
    }
    return [
        'products' => count($catalog),
        'stock'    => $stock,
        'value'    => $value,
    ];
}

// --------------------------- CRUD: работа с товарами -----------------

// Ищет товар по id. Нет такого — возвращает null (а не рушится).
function find_product(array $catalog, int $id): ?array
{
    foreach ($catalog as $product) {
        if ((int) $product['id'] === $id) {
            return $product;
        }
    }
    return null;
}

// Новый id: на единицу больше максимального.
function next_id(array $catalog): int
{
    $max = 0;
    foreach ($catalog as $product) {
        $max = max($max, (int) ($product['id'] ?? 0));
    }
    return $max + 1;
}

// Добавляет новый товар в конец каталога, выдаёт ему id.
function add_product(array $catalog, array $data): array
{
    $catalog[] = [
        'id'       => next_id($catalog),
        'title'    => trim((string) ($data['title'] ?? '')),
        'category' => trim((string) ($data['category'] ?? '')),
        'price'    => (int) round((float) ($data['price'] ?? 0)),
        'stock'    => (int) ($data['stock'] ?? 0),
        'discount' => (int) ($data['discount'] ?? 0),
    ];
    return $catalog;
}

// Обновляет поля товара. Если товар не найден — возвращает каталог без изменений.
function update_product(array $catalog, int $id, array $data): array
{
    foreach ($catalog as $key => $product) {
        if ((int) $product['id'] === $id) {
            $catalog[$key]['title']    = trim((string) ($data['title'] ?? $product['title']));
            $catalog[$key]['category'] = trim((string) ($data['category'] ?? $product['category']));
            $catalog[$key]['price']    = (int) round((float) ($data['price'] ?? $product['price']));
            $catalog[$key]['stock']    = (int) ($data['stock'] ?? $product['stock']);
            $catalog[$key]['discount'] = (int) ($data['discount'] ?? $product['discount']);
            return $catalog;
        }
    }
    return $catalog;
}

// Удаляет товар по id. array_values() — убираем «дыры» в ключах.
function delete_product(array $catalog, int $id): array
{
    foreach ($catalog as $key => $product) {
        if ((int) $product['id'] === $id) {
            unset($catalog[$key]);
            return array_values($catalog);
        }
    }
    return $catalog;
}

// --------------------------- Выборка: поиск/фильтры/сортировка -------

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

function unique_categories(array $catalog): array
{
    $categories = [];
    foreach ($catalog as $product) {
        $categories[$product['category']] = true;
    }
    return array_keys($categories);
}

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

function sort_by_title(array $catalog, string $direction = 'asc'): array
{
    usort($catalog, function (array $a, array $b) use ($direction): int {
        $cmp = strcmp(mb_strtolower($a['title']), mb_strtolower($b['title']));
        return $direction === 'desc' ? -$cmp : $cmp;
    });
    return $catalog;
}

function sort_by_stock(array $catalog, string $direction = 'asc'): array
{
    usort($catalog, function (array $a, array $b) use ($direction): int {
        if ($direction === 'desc') {
            return $b['stock'] <=> $a['stock'];
        }
        return $a['stock'] <=> $b['stock'];
    });
    return $catalog;
}

function paginate(array $items, int $page, int $perPage = PER_PAGE): array
{
    $total = count($items);
    $totalPages = max(1, (int) ceil($total / $perPage));
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

// --------------------------- Валидация и вывод ------------------------

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function validate_product(array $data): array
{
    $errors = [];

    $title = trim((string) ($data['title'] ?? ''));
    if ($title === '') {
        $errors['title'] = 'Укажите название товара.';
    } elseif (mb_strlen($title) < 3) {
        $errors['title'] = 'Название должно быть не короче 3 символов.';
    }

    $category = trim((string) ($data['category'] ?? ''));
    if ($category === '') {
        $errors['category'] = 'Выберите категорию.';
    }

    $price = $data['price'] ?? '';
    if (!is_numeric($price)) {
        $errors['price'] = 'Цена должна быть числом.';
    } elseif ((float) $price <= 0) {
        $errors['price'] = 'Цена должна быть больше нуля.';
    }

    $stock = $data['stock'] ?? '';
    if ($stock !== '' && !is_numeric($stock)) {
        $errors['stock'] = 'Остаток — целое число.';
    } elseif ((int) $stock < 0) {
        $errors['stock'] = 'Остаток не может быть отрицательным.';
    }

    $discount = $data['discount'] ?? '';
    if ($discount !== '' && !is_numeric($discount)) {
        $errors['discount'] = 'Скидка — число в процентах.';
    } elseif ((float) $discount < 0 || (float) $discount > 100) {
        $errors['discount'] = 'Скидка должна быть от 0 до 100 %.';
    }

    return $errors;
}

function price_with_discount(float $price, int $discount): float
{
    if ($discount <= 0) {
        return $price;
    }
    return round($price * (100 - $discount) / 100, 2);
}

function stock_label(int $stock): string
{
    return match (true) {
        $stock === 0 => 'Нет в наличии',
        $stock <= 5  => 'Мало',
        default      => 'В наличии',
    };
}

// Удобные ссылки в разметке: считает href с сохранением GET-параметров.
function query_url(array $params): string
{
    $all = array_merge($_GET, $params);
    return 'index.php?' . http_build_query($all);
}