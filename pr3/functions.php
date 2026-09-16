<?php

// =====================================================================
// Каталог товаров: строки и формы.
// Чистые функции для валидации и «причёсывания» данных из формы,
// плюс функции поиска/фильтров из работы 2.
// =====================================================================

// Безопасный вывод: превращает опасные символы в безопасные HTML-сущности.
// Обязательно использовать для ЛЮБЫХ данных, попавших к нам от пользователя.
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// --- Строковые операции для поиска и фильтров (из работы 2) ----------

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

// --- Валидация -------------------------------------------------------

// Проверяет данные из формы и возвращает список ошибок.
// Пустой массив = всё в порядке.
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

// Превращает «сырые» данные формы в аккуратный товар с правильными типами.
function sanitize_product(array $data): array
{
    return [
        'title'    => trim((string) ($data['title'] ?? '')),
        'category' => trim((string) ($data['category'] ?? '')),
        'price'    => round((float) ($data['price'] ?? 0), 2),
        'stock'    => (int) ($data['stock'] ?? 0),
        'discount' => (int) ($data['discount'] ?? 0),
    ];
}

// Показ цены и метки наличия (для таблицы).
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