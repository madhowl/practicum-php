<?php

namespace App;

// Класс-помощник для безопасного вывода в HTML.
final class Html
{
    public static function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
