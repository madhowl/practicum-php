<?php

namespace App;

use RuntimeException;

// Ошибка хранилища: не удалось прочитать или записать файл.
class StorageException extends RuntimeException
{
}
