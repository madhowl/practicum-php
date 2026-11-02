<?php

namespace App;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

// Catalog — коллекция товаров. Умеет хранить, искать, фильтровать,
// сортировать и разбивать на страницы. Внутри — массив объектов Product.
//
// Реализует два встроенных интерфейса:
//   Countable        — по каталогу работает count();
//   IteratorAggregate — по каталогу работает foreach.
final class Catalog implements Countable, IteratorAggregate
{
    /** @var Product[] */
    private array $products;

    /** @param Product[] $products */
    public function __construct(array $products = [])
    {
        // array_values убирает «дырки» в ключах после фильтрации.
        $this->products = array_values($products);
    }

    public function count(): int
    {
        return count($this->products);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->products);
    }

    /** @return Product[] */
    public function all(): array
    {
        return $this->products;
    }

    public function isEmpty(): bool
    {
        return $this->products === [];
    }

    public function find(int $id): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }
        return null;
    }

    // Следующий свободный id: максимум существующих + 1.
    public function nextId(): int
    {
        $max = 0;
        foreach ($this->products as $product) {
            if ($product->id > $max) {
                $max = $product->id;
            }
        }
        return $max + 1;
    }

    public function add(Product $product): void
    {
        $this->products[] = $product;
    }

    public function update(Product $product): void
    {
        foreach ($this->products as $index => $existing) {
            if ($existing->id === $product->id) {
                $this->products[$index] = $product;
                return;
            }
        }
        throw new ProductNotFoundException($product->id);
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            throw new ProductNotFoundException($id);
        }
        $this->products = array_values(
            array_filter($this->products, fn (Product $product): bool => $product->id !== $id)
        );
    }

    // Поиск по части названия. Возвращает НОВЫЙ каталог,
    // поэтому методы можно выстраивать в цепочку.
    public function search(string $query): self
    {
        $query = trim($query);
        if ($query === '') {
            return new self($this->products);
        }
        $needle = mb_strtolower($query);
        return new self(array_filter(
            $this->products,
            fn (Product $product): bool => str_contains(mb_strtolower($product->title), $needle)
        ));
    }

    public function filterByCategory(Category $category): self
    {
        return new self(array_filter(
            $this->products,
            fn (Product $product): bool => $product->category === $category
        ));
    }

    public function sortByPrice(string $direction = 'asc'): self
    {
        $products = $this->products;
        usort($products, function (Product $a, Product $b) use ($direction): int {
            $result = $a->price <=> $b->price;
            return $direction === 'desc' ? -$result : $result;
        });
        return new self($products);
    }

    public function sortByTitle(): self
    {
        $products = $this->products;
        usort(
            $products,
            fn (Product $a, Product $b): int => mb_strtolower($a->title) <=> mb_strtolower($b->title)
        );
        return new self($products);
    }

    public function sortByStock(string $direction = 'desc'): self
    {
        $products = $this->products;
        usort($products, function (Product $a, Product $b) use ($direction): int {
            $result = $a->stock <=> $b->stock;
            return $direction === 'desc' ? -$result : $result;
        });
        return new self($products);
    }

    // Разбивка на страницы: сколько всего страниц, какие товары на текущей.
    public function paginate(int $page, int $perPage): array
    {
        $total = count($this->products);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($this->products, $offset, $perPage),
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'total' => $total,
        ];
    }

    // Сводка по каталогу для «шапки» страницы.
    public function stats(): array
    {
        $stock = 0;
        $warehouse = 0.0;
        $discounted = 0;

        foreach ($this->products as $product) {
            $stock += $product->stock;
            $warehouse += $product->priceWithDiscount() * $product->stock;
            if ($product->discount > 0) {
                $discounted++;
            }
        }

        return [
            'products' => count($this->products),
            'stock' => $stock,
            'warehouse' => round($warehouse, 2),
            'discounted' => $discounted,
        ];
    }
}
