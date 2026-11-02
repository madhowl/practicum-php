<?php

namespace App;

use RuntimeException;

// Своё исключение: «товар с таким id не найден».
// Наследуется от RuntimeException — исключение времени выполнения.
class ProductNotFoundException extends RuntimeException
{
    public function __construct(public readonly int $productId)
    {
        parent::__construct("Товар #{$productId} не найден");
    }
}
