<?php

// Автотесты работы 6. Классы студента лежат в src/.
// Подключаются через Composer (PSR-4). Другой autoload-файл можно задать
// переменной окружения PR6_AUTOLOAD.
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

$autoload = getenv('PR6_AUTOLOAD') ?: __DIR__ . '/vendor/autoload.php';

if (!is_file($autoload)) {
    echo "Не найден автозагрузчик: {$autoload}\n";
    echo "Сначала выполните в папке pr6: composer install\n";
    exit(1);
}

require $autoload;

use App\Catalog;
use App\CatalogRepository;
use App\Category;
use App\Html;
use App\InvalidArgumentException;
use App\JsonFileStorage;
use App\Product;
use App\ProductNotFoundException;
use App\ProductValidator;

echo "Работа 6. ООП: каталог на классах\n\n";

// --- Category ---
check('Category::Tech хранит название', Category::Tech->value, 'Техника');
check('Category::Games->label()', Category::Games->label(), 'Игры');
check('Category::from переводит строку в enum', Category::from('Аксессуары'), Category::Accessories);
check('Category::tryFrom неизвестного даёт null', Category::tryFrom('Ерунда'), null);
check('Category::labels(): 3 категории', count(Category::labels()), 3);

// --- Product ---
$product = new Product(3, 'Наушники JBL Tune', Category::Accessories, 3990.0, 30, 20);
check('priceWithDiscount: 3990 со скидкой 20%', $product->priceWithDiscount(), 3192.0);
check('stockLabel: остаток 30', $product->stockLabel(), 'В наличии');
check('stockLabel: остаток 0', (new Product(1, 'X', Category::Tech, 100.0, 0, 0))->stockLabel(), 'Нет в наличии');
check('stockLabel: остаток 5', (new Product(1, 'X', Category::Tech, 100.0, 5, 0))->stockLabel(), 'Мало');
check('__toString печатает товар', (string) $product, '#3 Наушники JBL Tune — Аксессуары');

$row = $product->toArray();
check('toArray: id', $row['id'], 3);
check('toArray: category строкой', $row['category'], 'Аксессуары');
check('fromArray(toArray) сохраняет название', Product::fromArray($row)->title, 'Наушники JBL Tune');
check('fromArray(toArray) сохраняет категорию', Product::fromArray($row)->category, Category::Accessories);

$threw = false;
try {
    new Product(1, 'X', Category::Tech, -5.0, 1, 0);
} catch (InvalidArgumentException) {
    $threw = true;
}
check_true('отрицательная цена бросает InvalidArgumentException', $threw);

// --- Catalog ---
$catalog = new Catalog(array_map(
    fn (array $r): Product => Product::fromArray($r),
    JsonFileStorage::defaultCatalog()
));

check('count($catalog) через Countable', count($catalog), 8);
check('find(3) находит товар', $catalog->find(3)?->title, 'Наушники JBL Tune');
check('find(999) даёт null', $catalog->find(999), null);
check('nextId для 8 товаров', $catalog->nextId(), 9);

$titles = [];
foreach ($catalog as $p) {
    $titles[] = $p->title;
}
check('foreach обходит все товары', count($titles), 8);

check('поиск «игр» — 2 товара', count($catalog->search('игр')), 2);
check('поиск без учёта регистра и пробелов', count($catalog->search('  НАУШ  ')), 1);
check('пустой поиск возвращает всё', count($catalog->search('')), 8);
check('фильтр «Игры» — 2 товара', count($catalog->filterByCategory(Category::Games)), 2);
check('фильтр «Техника» — 3 товара', count($catalog->filterByCategory(Category::Tech)), 3);

$asc = array_map(fn (Product $p): float => $p->price, $catalog->sortByPrice('asc')->all());
$expected = $asc;
sort($expected);
check('сортировка по цене ↑', $asc, $expected);
$desc = array_map(fn (Product $p): float => $p->price, $catalog->sortByPrice('desc')->all());
$expected = $desc;
rsort($expected);
check('сортировка по цене ↓', $desc, $expected);
check('сортировка по названию: первым самое «маленькое»', $catalog->sortByTitle()->all()[0]->title, 'Зарядное устройство USB');
$stockDesc = array_map(fn (Product $p): int => $p->stock, $catalog->sortByStock('desc')->all());
$expected = $stockDesc;
rsort($expected);
check('сортировка по остатку', $stockDesc, $expected);

$pg = $catalog->paginate(2, 5);
check('пагинация: 2 страницы по 5', $pg['totalPages'], 2);
check('пагинация: на 2-й странице 3 товара', count($pg['items']), 3);
check('пагинация: страница не выходит за границы', $catalog->paginate(42, 5)['page'], 2);
check('пагинация: текущая страница', $pg['page'], 2);

$stats = $catalog->stats();
check('статистика: товаров 8', $stats['products'], 8);
check('статистика: остаток 226', $stats['stock'], 226);

// add / update / delete
$catalog->add(new Product(9, 'Телевизор Samsung', Category::Tech, 39990.0, 7, 10));
check('после add стало 9', count($catalog), 9);
$catalog->update(new Product(9, 'Телевизор Samsung QLED', Category::Tech, 45990.0, 3, 15));
check('update меняет название', $catalog->find(9)?->title, 'Телевизор Samsung QLED');
check('update сохраняет id', $catalog->find(9)?->id, 9);
$catalog->delete(9);
check('после delete стало 8', count($catalog), 8);

$notFound = false;
try {
    $catalog->delete(999);
} catch (ProductNotFoundException) {
    $notFound = true;
}
check_true('delete несуществующего бросает исключение', $notFound);

// --- ProductValidator ---
$validator = new ProductValidator();
$valid = ['title' => 'Игровая консоль', 'category' => 'Игры', 'price' => '25000', 'stock' => '2', 'discount' => '0'];
check('валидные данные без ошибок', $validator->validate($valid), []);
check_true('пустое название — ошибка', isset($validator->validate(['title' => ''])['title']));
check_true('короткое название — ошибка', isset($validator->validate(['title' => 'ab'])['title']));
check_true('отрицательная цена — ошибка', isset($validator->validate(['title' => 'X', 'category' => 'Игры', 'price' => '-1'])['price']));
check_true('цена-текст — ошибка', isset($validator->validate(['title' => 'X', 'category' => 'Игры', 'price' => 'дорого'])['price']));
check_true('скидка 110 — ошибка', isset($validator->validate(['title' => 'X', 'category' => 'Игры', 'price' => '1', 'discount' => '110'])['discount']));
check_true('неверная категория — ошибка', isset($validator->validate(['title' => 'X', 'category' => 'Ерунда', 'price' => '1'])['category']));

// --- JsonFileStorage: круг «сохранили — загрузили» ---
$tmp = sys_get_temp_dir() . '/pr6_catalog_' . uniqid() . '.json';
$storage = new JsonFileStorage($tmp);
check('нет файла — load возвращает 8 товаров', count($storage->load()), 8);
check_true('нет файла — файл создан', is_file($tmp));
$storage->save([['id' => 1, 'title' => 'Тест', 'category' => 'Техника', 'price' => 100, 'stock' => 1, 'discount' => 0]]);
$back = $storage->load();
check('сохранили и загрузили 1 товар', count($back), 1);
check('данные совпадают', $back[0]['title'], 'Тест');
unlink($tmp);

// --- JsonFileStorage: восстановление испорченного файла ---
$broken = sys_get_temp_dir() . '/pr6_broken_' . uniqid() . '.json';
file_put_contents($broken, 'это не json {{{');
$storage = new JsonFileStorage($broken);
check('битый файл — вернулся дефолт', count($storage->load()), 8);
check_true('битый файл сохранён как .broken.*', glob($broken . '.broken.*') !== []);
foreach (glob($broken . '.broken.*') as $file) {
    unlink($file);
}
unlink($broken);

// --- CatalogRepository ---
$tmp = sys_get_temp_dir() . '/pr6_repo_' . uniqid() . '.json';
$repo = new CatalogRepository(new JsonFileStorage($tmp));
check('репозиторий загружает 8 товаров', count($repo->load()), 8);

$created = $repo->create(['title' => 'Планшет', 'category' => 'Техника', 'price' => '19990', 'stock' => '4', 'discount' => '0']);
check('create выдал id = 9', $created->id, 9);
check('create сохранил товар', $repo->load()->find(9)?->title, 'Планшет');

$updated = $repo->update(9, ['title' => 'Планшет Lenovo', 'category' => 'Техника', 'price' => '21990', 'stock' => '2', 'discount' => '10']);
check('update меняет название', $updated->title, 'Планшет Lenovo');
check('update изменил данные в файле', $repo->find(9)->title, 'Планшет Lenovo');

$repo->delete(9);
check('delete удалил товар', $repo->load()->find(9), null);

$notFound = false;
try {
    $repo->find(999);
} catch (ProductNotFoundException $e) {
    $notFound = true;
    check('исключение помнит id', $e->productId, 999);
}
check_true('репозиторий бросает ProductNotFoundException', $notFound);
unlink($tmp);

// --- Html ---
check_true('Html::e защищает от <script>', !str_contains(Html::e('<script>x</script>'), '<script>'));
check('Html::e оставляет обычный текст', Html::e('Ноутбук'), 'Ноутбук');

// --- итог ---
echo "\nИтого проверок: {$GLOBALS['tests']}, ошибок: {$GLOBALS['failed']}\n";
exit($GLOBALS['failed'] > 0 ? 1 : 0);
