<?php

namespace App;

// Интерфейс — «договор»: любой класс-хранилище обязан уметь
// загружать данные массивом и сохранять массив.
// Каталог не знает, файл это, база данных или что-то ещё.
interface StorageInterface
{
    /** @return array<int, array<string, mixed>> */
    public function load(): array;

    /** @param array<int, array<string, mixed>> $rows */
    public function save(array $rows): void;
}
