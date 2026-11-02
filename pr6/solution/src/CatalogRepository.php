<?php

namespace App;

// Репозиторий — «посредник» между объектами каталога и хранилищем.
// Ему передают StorageInterface; менять файл на базу данных можно,
// не трогая сами страницы. Это и есть внедрение зависимости (DI).
final class CatalogRepository
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function load(): Catalog
    {
        $products = array_map(
            fn (array $row): Product => Product::fromArray($row),
            $this->storage->load()
        );
        return new Catalog($products);
    }

    public function save(Catalog $catalog): void
    {
        $rows = array_map(
            fn (Product $product): array => $product->toArray(),
            $catalog->all()
        );
        $this->storage->save($rows);
    }

    // Найти товар или бросить исключение, если его нет.
    public function find(int $id): Product
    {
        $product = $this->load()->find($id);

        if ($product === null) {
            throw new ProductNotFoundException($id);
        }

        return $product;
    }

    public function create(array $input): Product
    {
        $catalog = $this->load();
        $product = $this->makeProduct($catalog->nextId(), $input);
        $catalog->add($product);
        $this->save($catalog);

        return $product;
    }

    public function update(int $id, array $input): Product
    {
        $catalog = $this->load();
        $product = $this->makeProduct($id, $input);
        $catalog->update($product); // бросит ProductNotFoundException, если id нет
        $this->save($catalog);

        return $product;
    }

    public function delete(int $id): void
    {
        $catalog = $this->load();
        $catalog->delete($id); // бросит ProductNotFoundException, если id нет
        $this->save($catalog);
    }

    // Собирает объект Product из «сырых» данных формы.
    private function makeProduct(int $id, array $input): Product
    {
        return new Product(
            id: $id,
            title: trim((string) ($input['title'] ?? '')),
            category: Category::from((string) ($input['category'] ?? '')),
            price: (float) ($input['price'] ?? 0),
            stock: (int) ($input['stock'] ?? 0),
            discount: (int) ($input['discount'] ?? 0),
        );
    }
}
