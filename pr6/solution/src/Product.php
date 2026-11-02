<?php

namespace App;

// Product — сущность «товар». Объект неизменяемый (readonly):
// после создания поменять свойства нельзя, только создать новый товар.
final class Product
{
    // Property promotion: свойства объявляются прямо в параметрах конструктора.
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly Category $category,
        public readonly float $price,
        public readonly int $stock,
        public readonly int $discount,
    ) {
        // Инварианты: объект не должен существовать в «невозможном» состоянии.
        // Это ошибка программиста, поэтому бросаем исключение.
        if ($price < 0) {
            throw new InvalidArgumentException('Цена товара не может быть отрицательной');
        }
        if ($stock < 0) {
            throw new InvalidArgumentException('Остаток товара не может быть отрицательным');
        }
        if ($discount < 0 || $discount > 100) {
            throw new InvalidArgumentException('Скидка должна быть от 0 до 100');
        }
    }

    // Цена с учётом скидки, округлённая до копеек.
    public function priceWithDiscount(): float
    {
        return round($this->price * (100 - $this->discount) / 100, 2);
    }

    // Текстовая метка наличия на складе.
    public function stockLabel(): string
    {
        return match (true) {
            $this->stock === 0 => 'Нет в наличии',
            $this->stock <= 5 => 'Мало',
            default => 'В наличии',
        };
    }

    // Товар в виде простого массива — чтобы записать его в JSON.
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category->value,
            'price' => $this->price,
            'stock' => $this->stock,
            'discount' => $this->discount,
        ];
    }

    // Обратное превращение: массив (из JSON) -> объект Product.
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            title: (string) ($row['title'] ?? ''),
            category: Category::tryFrom((string) ($row['category'] ?? '')) ?? Category::Tech,
            price: (float) ($row['price'] ?? 0),
            stock: (int) ($row['stock'] ?? 0),
            discount: (int) ($row['discount'] ?? 0),
        );
    }

    // Магический метод: что напечатается, если объект привести к строке.
    public function __toString(): string
    {
        return sprintf('#%d %s — %s', $this->id, $this->title, $this->category->value);
    }
}
