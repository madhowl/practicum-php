<?php

namespace App;

use InvalidArgumentException as BaseInvalidArgumentException;

// Ошибка «неверный аргумент» — нарушение правил класса.
// Наследуемся от стандартного InvalidArgumentException, чтобы
// перехватывать и свой, и встроенный тип.
class InvalidArgumentException extends BaseInvalidArgumentException
{
}
