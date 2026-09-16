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
$catalog = require __DIR__ . '/data.php';

echo "Работа 3. Строки и формы\n\n";

$valid = [
    'title'    => '  Телевизор Samsung  ',
    'category' => 'Техника',
    'price'    => '39990',
    'stock'    => '7',
    'discount' => '10',
];

// --- validate_product ---
check('целиком валидные данные без ошибок', validate_product($valid), []);

check_true('пустое название -> ошибка',
    isset(validate_product(['title' => '   '])['title']));
check_true('короткое название -> ошибка',
    isset(validate_product(['title' => 'Ко'])['title']));
check_true('нет категории -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => ' '])['category']));
check_true('цена не числом -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => 'не число'])['price']));
check_true('цена 0 -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '0'])['price']));
check_true('цена -5 -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '-5'])['price']));
check_true('дробная цена 50.5 проходит',
    validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '50.5']) === []);
check_true('отрицательный остаток -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '10', 'stock' => '-1'])['stock']));
check_true('остаток буквами -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '10', 'stock' => 'abc'])['stock']));
check_true('скидка 150 -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '10', 'discount' => '150'])['discount']));
check_true('скидка -1 -> ошибка',
    isset(validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '10', 'discount' => '-1'])['discount']));
check_true('скидка 0 проходит',
    validate_product(['title' => 'Телевизор', 'category' => 'Техника', 'price' => '10', 'discount' => '0']) === []);

// --- sanitize_product ---
$clean = sanitize_product($valid);
check('приводит цену к числу 39990.0', $clean['price'], 39990.0);
check('убирает лишние пробелы в названии', $clean['title'], 'Телевизор Samsung');
check('остаток — целое число', $clean['stock'], 7);
check('скидка — целое число', $clean['discount'], 10);

// --- e() ---
$evil = '<script>alert("x")</script>';
$escaped = e($evil);
check_true('escape превращает <script>', !str_contains($escaped, '<script>'));
check_true('escape подставляет &lt;', str_contains($escaped, '&lt;script&gt;'));
check('escape простого текста не меняет его', e('Наушники'), 'Наушники');

// --- search_by_name (русский, без регистра) ---
$catalog = search_by_name($catalog, 'науш');
check('русский поиск «науш» находит JBL', count($catalog), 1);
$catalog = require __DIR__ . '/data.php';
$catalog = search_by_name($catalog, 'игр');
check('поиск «игр» находит приставку и настольную игру', count($catalog), 2);

// --- filter_by_category ---
$catalog = require __DIR__ . '/data.php';
check('фильтр «Игры» — 2 товара', count(filter_by_category($catalog, 'Игры')), 2);
check('фильтр «all» — весь каталог', count(filter_by_category($catalog, 'all')), count($catalog));

// --- unique_categories ---
$categories = unique_categories(require __DIR__ . '/data.php');
sort($categories);
check('список категорий без повторов', $categories, ['Аксессуары', 'Игры', 'Техника']);

// --- price_with_discount и stock_label ---
check('цена со скидкой 54990/10%', price_with_discount(54990, 10), 49491.0);
check('сток 0 -> нет в наличии', stock_label(0), 'Нет в наличии');

// --- итог ---
echo "\nИтого проверок: {$GLOBALS['tests']}, ошибок: {$GLOBALS['failed']}\n";
exit($GLOBALS['failed'] > 0 ? 1 : 0);