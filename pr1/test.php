<?php

// Простейший тест-раннер: никаких внешних библиотек, php test.php прямо из терминала.
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

// Подключаем решение. Вывод страницы перехватываем, чтобы он не мешал тестам.
// Подключаем решение (само оно ничего не выводит при require).
require __DIR__ . '/solution.php';
$output = render_catalog($catalog);

echo "Работа 1. Основы языка\n\n";

// --- price_with_discount ---
check('скидка 10% от 100', price_with_discount(100, 10), 90.0);
check('скидка 0% не меняет цену', price_with_discount(100, 0), 100.0);
check('скидка 5% от 21990', price_with_discount(21990, 5), 20890.5);

// --- stock_label ---
check('0 штук -> нет в наличии', stock_label(0), 'Нет в наличии');
check('5 штук -> мало', stock_label(5), 'Мало');
check('6 штук -> в наличии', stock_label(6), 'В наличии');
check('12 штук -> в наличии', stock_label(12), 'В наличии');

// --- структура каталога ---
check_true('в каталоге не меньше 6 товаров', count($catalog) >= 6);
$requiredFields = ['title', 'category', 'price', 'stock', 'discount'];
foreach ($requiredFields as $field) {
    check_true('у каждого товара есть поле ' . $field, array_key_exists($field, $catalog[0]));
}

// --- итоговые расчёты ---
foreach ($catalog as $product) {
    check_true('все цены в каталоге — положительные числа', $product['price'] > 0);
    check_true('все остатки — неотрицательные числа', $product['stock'] >= 0);
}
check('в демо-выводе упоминается каталог', str_contains($output, 'Каталог товаров'), true);
check('в демо-выводе видна итоговая стоимость', str_contains($output, 'Итоговая стоимость склада'), true);

// Демо-вывод содержит корректную итоговую стоимость (посчитанную по формуле).
$expectedTotal = 0.0;
foreach ($catalog as $product) {
    $expectedTotal += price_with_discount($product['price'], $product['discount']) * $product['stock'];
}
check_true('в отчёте есть итоговое число', str_contains($output, number_format(round($expectedTotal, 2), 2, ',', ' ')));

// --- итог ---
echo "\nИтого проверок: {$GLOBALS['tests']}, ошибок: {$GLOBALS['failed']}\n";
exit($GLOBALS['failed'] > 0 ? 1 : 0);