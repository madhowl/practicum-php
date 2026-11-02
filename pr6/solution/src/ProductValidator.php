<?php

namespace App;

// Валидатор проверяет данные, пришедшие из формы.
// Возвращает МАССИВ ошибок: ['title' => '...', 'price' => '...'].
// Пустой массив [] означает, что ошибок нет.
final class ProductValidator
{
    public function validate(array $input): array
    {
        $errors = [];

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'] = 'Название не может быть пустым';
        } elseif (mb_strlen($title) < 3) {
            $errors['title'] = 'Название должно быть не короче 3 символов';
        }

        $category = (string) ($input['category'] ?? '');
        if (Category::tryFrom($category) === null) {
            $errors['category'] = 'Выберите категорию из списка';
        }

        $price = $input['price'] ?? '';
        if (!is_numeric($price) || (float) $price <= 0) {
            $errors['price'] = 'Цена должна быть числом больше нуля';
        }

        $stock = $input['stock'] ?? '';
        if (!is_numeric($stock) || (int) $stock < 0) {
            $errors['stock'] = 'Остаток должен быть числом не меньше нуля';
        }

        $discount = $input['discount'] ?? '';
        if (!is_numeric($discount) || (int) $discount < 0 || (int) $discount > 100) {
            $errors['discount'] = 'Скидка должна быть числом от 0 до 100';
        }

        return $errors;
    }
}
