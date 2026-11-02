<?php

namespace App;

// Хранилище в JSON-файле. Логика перенесена из работы 4:
// функция load() ВСЕГДА возвращает массив товаров, даже если файл
// отсутствует или испорчен.
final class JsonFileStorage implements StorageInterface
{
    public function __construct(private readonly string $file)
    {
    }

    public function load(): array
    {
        if (!is_file($this->file) || !is_readable($this->file)) {
            $default = self::defaultCatalog();
            $this->save($default);
            return $default;
        }

        $json = file_get_contents($this->file);

        if ($json === false || !json_validate($json)) {
            return $this->recover();
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !array_is_list($data)) {
            return $this->recover();
        }

        return $data;
    }

    public function save(array $rows): void
    {
        $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new StorageException('Не удалось преобразовать каталог в JSON');
        }

        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $bytes = @file_put_contents($this->file, $json, LOCK_EX);

        if ($bytes === false) {
            throw new StorageException('Не удалось записать файл: ' . $this->file);
        }
    }

    // Испорченный файл переименовываем (а не удаляем) и берём дефолт.
    private function recover(): array
    {
        @rename($this->file, $this->file . '.broken.' . date('YmdHis'));
        $default = self::defaultCatalog();
        $this->save($default);
        return $default;
    }

    // «Заводской» каталог из 8 товаров.
    /** @return array<int, array<string, mixed>> */
    public static function defaultCatalog(): array
    {
        return [
            ['id' => 1, 'title' => 'Ноутбук ASUS', 'category' => 'Техника', 'price' => 54990, 'stock' => 8, 'discount' => 10],
            ['id' => 2, 'title' => 'Смартфон Xiaomi', 'category' => 'Техника', 'price' => 24990, 'stock' => 15, 'discount' => 5],
            ['id' => 3, 'title' => 'Наушники JBL Tune', 'category' => 'Аксессуары', 'price' => 3990, 'stock' => 30, 'discount' => 20],
            ['id' => 4, 'title' => 'Игровая приставка', 'category' => 'Игры', 'price' => 39990, 'stock' => 6, 'discount' => 0],
            ['id' => 5, 'title' => 'Настольная игра «Каркассон»', 'category' => 'Игры', 'price' => 2990, 'stock' => 25, 'discount' => 15],
            ['id' => 6, 'title' => 'Клавиатура Logitech', 'category' => 'Техника', 'price' => 5990, 'stock' => 20, 'discount' => 12],
            ['id' => 7, 'title' => 'Мышь беспроводная', 'category' => 'Аксессуары', 'price' => 1990, 'stock' => 40, 'discount' => 0],
            ['id' => 8, 'title' => 'Зарядное устройство USB', 'category' => 'Аксессуары', 'price' => 1490, 'stock' => 82, 'discount' => 25],
        ];
    }
}
