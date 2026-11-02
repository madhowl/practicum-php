<?php

namespace App;

// Enum (перечисление) — список допустимых значений категории.
// Вместо обычной строки 'Техника' используем Category::Tech:
// компилятор не даст опечататься и подскажет варианты.
enum Category: string
{
    case Tech = 'Техника';
    case Games = 'Игры';
    case Accessories = 'Аксессуары';

    // Человекочитаемая подпись — то, что видит пользователь.
    public function label(): string
    {
        return $this->value;
    }

    // Все названия категорий строками — удобно для выпадающего списка.
    public static function labels(): array
    {
        return array_map(fn (self $category): string => $category->value, self::cases());
    }
}
