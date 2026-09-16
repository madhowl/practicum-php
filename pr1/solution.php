<?php

// =====================================================================
// Каталог товаров: начало проекта.
// Здесь мы только знакомимся с языком: массивы, условия, циклы, match.
// =====================================================================

// Каждый товар — ассоциативный массив с одинаковым набором полей.
$catalog = [
    ['title' => 'Ноутбук Lenovo IdeaPad', 'category' => 'Техника',     'price' => 54990, 'stock' => 12, 'discount' => 10],
    ['title' => 'Смартфон Xiaomi Redmi',  'category' => 'Техника',     'price' => 21990, 'stock' => 30, 'discount' => 5],
    ['title' => 'Наушники JBL Tune',      'category' => 'Аксессуары',  'price' => 3490,  'stock' => 0,  'discount' => 0],
    ['title' => 'Клавиатура Logitech',    'category' => 'Аксессуары',  'price' => 2790,  'stock' => 45, 'discount' => 15],
    ['title' => 'Мышь беспроводная',      'category' => 'Аксессуары',  'price' => 1290,  'stock' => 80, 'discount' => 0],
    ['title' => 'Игровая приставка PS5',  'category' => 'Игры',        'price' => 47990, 'stock' => 6,  'discount' => 0],
];

// Цена товара с учётом скидки.
function price_with_discount(int $price, int $discount): float
{
    if ($discount <= 0) {
        return (float) $price;
    }
    return round($price * (100 - $discount) / 100, 2);
}

// Словесная метка наличия товара на складе.
function stock_label(int $stock): string
{
    return match (true) {          // match — аналог switch, но без break
        $stock === 0 => 'Нет в наличии',
        $stock <= 5  => 'Мало',
        default      => 'В наличии',
    };
}

// Демонстрация: красиво печатает каталог и итоги по складу.
// Функция получает каталог параметром и считает всё сама (никаких глобальных переменных).
function render_catalog(array $catalog): string
{
    $warehouse_total = 0.0;
    $products_on_sale = 0;

    foreach ($catalog as $product) {
        $price = price_with_discount($product['price'], $product['discount']);
        $warehouse_total += $price * $product['stock'];
        if ($product['discount'] > 0) {
            $products_on_sale++;
        }
    }

    // Символ '—' расширяет строку до фиксированной ширины.
// Обычный printf("%-Ns") считает байты, а кириллица — 2 байта на символ,
// поэтому колонки «плывут». Пишем свой паддинг по числу символов.
function pad_cell(string $text, int $width): string
{
    return $text . str_repeat(' ', max(0, $width - mb_strlen($text)));
}

$lines = [];
    $lines[] = 'Каталог товаров (всего позиций: ' . count($catalog) . ")\n";

    $line = pad_cell('N', 4) . pad_cell('Название', 26) . pad_cell('Категория', 14) . pad_cell('Цена', 10) . pad_cell('Скидка', 9) . pad_cell('Итог', 12) . pad_cell('Остаток', 12);
    $lines[] = str_repeat('-', mb_strlen($line));
    $lines[] = $line;
    $lines[] = str_repeat('-', mb_strlen($line));

    for ($i = 0; $i < count($catalog); $i++) {
        $p = $catalog[$i];
        $final = price_with_discount($p['price'], $p['discount']);
        $lines[] = pad_cell((string) ($i + 1), 4)
            . pad_cell($p['title'], 26)
            . pad_cell($p['category'], 14)
            . pad_cell(number_format($p['price'], 0, ',', ' '), 10)
            . pad_cell($p['discount'] . ' %', 9)
            . pad_cell(number_format($final, 2, ',', ' '), 12)
            . pad_cell(stock_label($p['stock']), 12);
    }

    $lines[] = str_repeat('-', mb_strlen($line));
    $lines[] = '';
    $lines[] = "Итоговая стоимость склада: " . number_format($warehouse_total, 2, ',', ' ') . "\u{20BD}";
    $lines[] = 'Товаров со скидкой: ' . $products_on_sale;

    return implode("\n", $lines);
}

// Запускаем выводить, только когда файл выполняется напрямую (из терминала),
// а не подключается в test.php.
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    echo render_catalog($catalog) . "\n";
}