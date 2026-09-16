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

require __DIR__ . '/model.php';

echo "Работа 4. Файлы и JSON\n\n";

// Временный файл, чтобы не трогать боевой data/catalog.json.
$tmpDir = sys_get_temp_dir();
$testFile = $tmpDir . '/catalog_test_' . uniqid() . '.json';
@unlink($testFile);

// --- загрузка и сохранение (круг: save -> load) ---
$catalog = default_catalog();
$catalog[] = ['id' => 99, 'title' => 'Тест', 'category' => 'Техника', 'price' => 100, 'stock' => 1, 'discount' => 0];
check_true('save_catalog возвращает true', save_catalog($catalog, $testFile));
check_true('файл появился на диске', is_file($testFile));

$loaded = load_catalog($testFile);
check('после save -> load каталог не изменился', $loaded, $catalog);
check('JSON валиден на диске', json_validate((string) file_get_contents($testFile)), true);
check_true('кириллица в файле не экранирована (UNESCAPED_UNICODE)',
    str_contains((string) file_get_contents($testFile), 'Ноутбук'));

// --- отсутствующий файл ---
$missing = $tmpDir . '/catalog_missing_' . uniqid() . '.json';
@unlink($missing);
check('нет файла -> возвращается дефолтный каталог', load_catalog($missing), default_catalog());
check_true('нет файла -> файл создан автоматически', is_file($missing));

// --- испорченный JSON ---
$broken = $tmpDir . '/catalog_broken_' . uniqid() . '.json';
file_put_contents($broken, '{not valid json :(');
check_true('json_validate() определяет мусор как невалидный', !json_validate('{not valid json :('));
check('испорченный файл -> возвращается дефолтный каталог', load_catalog($broken), default_catalog());
check_true('испорченный файл пересоздан корректным', json_validate((string) file_get_contents($broken)));

// --- JSON не массив (например, объект/число) ---
$objectJson = $tmpDir . '/catalog_object_' . uniqid() . '.json';
file_put_contents($objectJson, '{"name":"store"}');
check('валидный JSON, но не массив -> дефолт', load_catalog($objectJson), default_catalog());

// --- save в несуществующую папку ---
check('запись в несуществующую папку возвращает false',
    save_catalog(default_catalog(), $tmpDir . '/no_such_dir_' . uniqid() . '/file.json'), false);

// --- дефолтный каталог ---
$def = default_catalog();
check('в дефолтном каталоге 8 товаров', count($def), 8);
foreach ($def as $p) {
    check_true('у товара есть уникальный id', (int) $p['id'] > 0);
    check_true('поля товара на месте', array_key_exists('title', $p) && array_key_exists('price', $p));
}
$ids = array_column($def, 'id');
check('id не повторяются', count($ids), count(array_unique($ids)));

// --- catalog_stats ---
$stats = catalog_stats($def);
check('статистика: 8 товаров', $stats['products'], 8);
check('статистика: суммарный остаток', $stats['stock'], 226);

// --- уборка ---
foreach ([$testFile, $missing, $broken, $objectJson] as $f) {
    @unlink($f);
    foreach (glob($f . '.broken.*') ?: [] as $b) {
        @unlink($b);
    }
}

// --- итог ---
echo "\nИтого проверок: {$GLOBALS['tests']}, ошибок: {$GLOBALS['failed']}\n";
exit($GLOBALS['failed'] > 0 ? 1 : 0);