# Работа 6. Каталог товаров — ООП

## Цель

Переписать логику каталога из работы 5 на классы. Данные и поведение товара должны
жить вместе, категория стать строгим типом, а ошибки — обрабатываться предсказуемо.
Внешне приложение остаётся тем же самым CRUD-каталогом, но внутри всё устроено
по-объектному.

Эта работа — итог всего практикума: здесь встречаются массивы, функции, строки,
формы, файлы, JSON из работ 1–5, только теперь они «упакованы» в объекты.

## Что должно получиться

Структура работы:

```
pr6/
├── teoria.md          — этот конспект (уже есть)
├── zadanie.md         — задание (вы читаете)
├── test.php           — автотесты (уже есть)
├── composer.json      — правило автозагрузки PSR-4 (создаёте вы)
├── src/               — ВАШИ классы (создаёте вы)
│   ├── Category.php
│   ├── Product.php
│   ├── ProductValidator.php
│   ├── Catalog.php
│   ├── StorageInterface.php
│   ├── JsonFileStorage.php
│   ├── CatalogRepository.php
│   ├── Html.php
│   ├── ProductNotFoundException.php
│   ├── StorageException.php
│   └── InvalidArgumentException.php
├── bootstrap.php      — подключает vendor/autoload.php (создаёте вы)
├── layout.php         — общие HTML-обёртки (создаёте вы)
├── index.php          — список товаров (создаёте вы)
├── add.php            — добавление (создаёте вы)
├── edit.php           — редактирование (создаёте вы)
├── delete.php         — удаление (создаёте вы)
├── data/catalog.json  — хранилище (создаётся само)
└── vendor/            — автозагрузчик от Composer (появляется после composer install)
```

**Важно про имена файлов.** Автозагрузка в PHP работает по правилу «имя класса =
путь к файлу». Все наши классы живут в пространстве имён `App`, поэтому класс
`App\Product` лежит в файле `src/Product.php`, класс `App\Catalog` — в
`src/Catalog.php` и так далее. Если имя файла и класса не совпадают, автозагрузчик
класс не найдёт.

Делайте шаги по порядку. После каждого шага запускайте тесты — так сразу видно, что
уже работает:

```bash
php test.php
```

В конце должно быть «ошибок: 0». Код завершения `0` — всё хорошо, `1` — есть провалы.

---

## Шаг 0. Пространство имён и автозагрузка через Composer

Первый вопрос: как PHP узнаёт, какой файл подключать, когда встречает `new Product()`?

В прошлых работах мы подключали файлы вручную (`require`). С классами так делать
неудобно — их будет одиннадцать. Поэтому автозагрузку доверим **Composer**: он сам
сгенерирует автозагрузчик по правилу **PSR-4** «имя класса = путь к файлу».

**Задание.**

1. Создайте в папке `pr6` файл `composer.json` с правилом PSR-4 — классы `App\...`
   лежат в `src/`:

   ```json
   {
       "autoload": {
           "psr-4": {
               "App\\": "src/"
           }
       }
   }
   ```

2. Выполните установку — Composer создаст папку `vendor/` и файл-автозагрузчик:

   ```bash
   composer install
   ```

3. Создайте `bootstrap.php`, который подключает готовый автозагрузчик:

   ```php
   require __DIR__ . '/vendor/autoload.php';
   ```

После этого класс `App\Product` подключается из `src/Product.php` автоматически —
ни одного `require` для классов писать не нужно. Достаточно один раз подключить
`bootstrap.php` в начале страницы.

**Проверь себя.** `php -l bootstrap.php` должен написать «No syntax errors detected»,
а `composer install` — создать файл `vendor/autoload.php`.

**Про `vendor/`.** Это папка сгенерированных файлов. Её не редактируют руками и не
добавляют в git: она полностью воспроизводится командой `composer install`.

---

## Шаг 1. `Category` — перечисление категорий

В работе 5 категория была строкой, и опечатка `'технка'` тихо ломала фильтр.
Заменим строку на перечисление — строгий список допустимых значений.

**Файл:** `src/Category.php`, пространство имён `App`.

**Сигнатуры:**

```php
enum Category: string
{
    case Tech = 'Техника';
    case Games = 'Игры';
    case Accessories = 'Аксессуары';

    public function label(): string;          // название для человека
    public static function labels(): array;   // ['Техника', 'Игры', 'Аксессуары']
}
```

**Подсказка.**

- `label()` просто возвращает `$this->value` (строковое значение случая);
- `labels()` перебирает `self::cases()` и собирает `->value` через `array_map`
  с стрелочной функцией `fn (self $category): string => ...`.

**Пример использования.**

```php
echo Category::Tech->value;      // Техника
echo Category::Games->label();   // Игры
Category::from('Аксессуары');    // Category::Accessories
Category::tryFrom('Ерунда');     // null
Category::labels();             // ['Техника', 'Игры', 'Аксессуары']
```

**Проверь себя.** Тесты на `Category` должны пройти, даже если остальное ещё не готово.

---

## Шаг 2. `Product` — товар

Сердце проекта. Объект неизменяемый (`readonly`) и сам следит за своими правилами.

**Файл:** `src/Product.php`.

**Сигнатуры:**

```php
final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly Category $category,
        public readonly float $price,
        public readonly int $stock,
        public readonly int $discount,
    );

    public function priceWithDiscount(): float;  // цена со скидкой, округлить до копеек
    public function stockLabel(): string;         // 'Нет в наличии' | 'Мало' | 'В наличии'
    public function toArray(): array;             // товар -> простой массив для JSON
    public static function fromArray(array $row): self; // массив -> товар
    public function __toString(): string;         // '#3 Наушники JBL Tune — Аксессуары'
}
```

**Что нужно сделать.**

- В конструкторе (после promoted-свойств) проверьте **инварианты** и бросьте
  `InvalidArgumentException` (наш класс из шага 8), если:
  - `price < 0` — «Цена товара не может быть отрицательной»;
  - `stock < 0` — «Остаток товара не может быть отрицательным»;
  - `discount < 0` или `discount > 100` — «Скидка должна быть от 0 до 100».
- `priceWithDiscount()`: `round($this->price * (100 - $this->discount) / 100, 2)`.
- `stockLabel()`: через `match (true)` — `0` даёт «Нет в наличии», `1..5` — «Мало»,
  больше 5 — «В наличии».
- `toArray()`: вернуть ассоциативный массив с ключами
  `id, title, category, price, stock, discount`. Категорию положить строкой
  (`$this->category->value`).
- `fromArray()`: **статические** фабричный метод. Приводит типы
  (`(int)`, `(float)`), категорию получает через
  `Category::tryFrom((string) ($row['category'] ?? '')) ?? Category::Tech`.
  Удобно использовать **именованные аргументы**:

  ```php
  return new self(
      id: (int) ($row['id'] ?? 0),
      title: (string) ($row['title'] ?? ''),
      // ... остальные поля
  );
  ```
- `__toString()`: соберите строку через `sprintf('#%d %s — %s', ...)`.

**Пример использования.**

```php
$product = new Product(3, 'Наушники JBL Tune', Category::Accessories, 3990.0, 30, 20);

echo $product->priceWithDiscount();  // 3192.0
echo $product->stockLabel();         // В наличии
echo $product;                       // #3 Наушники JBL Tune — Аксессуары

$row = $product->toArray();          // массив для JSON
$same = Product::fromArray($row);    // обратно объект
```

**Проверь себя.** В тестах должны пройти проверки цены со скидкой, метки наличия,
преобразования в массив и обратно, а также что отрицательная цена бросает исключение.

---

## Шаг 3. `ProductValidator` — проверка формы

Валидация — это **ожидаемая** часть работы с пользователем, поэтому она возвращает
массив ошибок, а не бросает исключение.

**Файл:** `src/ProductValidator.php`.

**Сигнатура:**

```php
final class ProductValidator
{
    /** @return array<string, string> */
    public function validate(array $input): array;
}
```

Метод получает массив (обычно `$_POST`) и возвращает ошибки по полям. Пустой массив
означает «всё хорошо». Правила — те же, что в работе 3:

- **title**: убрать пробелы; пусто — «Название не может быть пустым»; короче 3
  символов — «Название должно быть не короче 3 символов» (длину считайте
  `mb_strlen`);
- **category**: `Category::tryFrom(...)` вернул `null` — «Выберите категорию из списка»;
- **price**: не число или меньше/равно нулю — «Цена должна быть числом больше нуля»
  (`is_numeric`);
- **stock**: не число или меньше нуля — «Остаток должен быть числом не меньше нуля»;
- **discount**: не число, меньше 0 или больше 100 — «Скидка должна быть числом от 0 до 100».

**Пример использования.**

```php
$validator = new ProductValidator();
$errors = $validator->validate(['title' => '', 'price' => '-1']);
// ['title' => '...', 'price' => '...']
```

**Подсказка.** Ключ массива ошибок — имя поля формы. Это позволит вывести сообщение
именно под нужным полем.

**Проверь себя.** Проверки валидации в тестах должны пройти.

---

## Шаг 4. `StorageInterface` — договор хранилища

**Файл:** `src/StorageInterface.php`.

```php
interface StorageInterface
{
    /** @return array<int, array<string, mixed>> */
    public function load(): array;

    /** @param array<int, array<string, mixed>> $rows */
    public function save(array $rows): void;
}
```

Пока это просто «список обязанностей»: любой, кто хранит каталог, должен уметь
загрузить массив и сохранить массив. Комментарии-аннотации `@return`/`@param`
помогают подсказкам редактора.

---

## Шаг 5. `JsonFileStorage` — хранилище в файле

Переносим сюда логику работы с файлом из работы 4. Класс подписывает договор из шага 4.

**Файл:** `src/JsonFileStorage.php`.

**Сигнатуры:**

```php
final class JsonFileStorage implements StorageInterface
{
    public function __construct(private readonly string $file);

    public function load(): array;                    // ВСЕГДА возвращает массив товаров
    public function save(array $rows): void;           // пишет JSON, при сбое бросает StorageException
    private function recover(): array;                 // чинит испорченный файл

    /** @return array<int, array<string, mixed>> */
    public static function defaultCatalog(): array;    // 8 товаров «заводских настроек»
}
```

**Что нужно сделать.**

- **Конструктор** запоминает путь к файлу (`private readonly string $file`).
- **`defaultCatalog()`** — статический метод, возвращает те же 8 товаров, что были
  в работе 4, с полями `id` (1–8), `title`, `category`, `price`, `stock`, `discount`.
- **`load()`** по цепочке проверок из работы 4:
  1. файла нет или он не читается → сохранить дефолт и вернуть его;
  2. `file_get_contents` вернул `false` или `json_validate` не прошёл → `recover()`;
  3. `json_decode(..., true)` вернул не список (`!array_is_list`) → `recover()`;
  4. иначе — вернуть данные.
- **`recover()`**: переименовать испорченный файл в `<файл>.broken.<дата>`
  (через `@rename` и `date('YmdHis')`), сохранить дефолт, вернуть дефолт.
- **`save()`**: `json_encode` с флагами `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`;
  если получилось `false` — бросить `StorageException`; создать папку, если её нет
  (`@mkdir`), и записать файл через `file_put_contents(..., LOCK_EX)`; если запись
  не удалась — бросить `StorageException`.

**Пример использования.**

```php
$storage = new JsonFileStorage(__DIR__ . '/data/catalog.json');
$rows = $storage->load();          // массив товаров
$storage->save($rows);             // обратно в файл
```

**Проверь себя.** В тестах есть три сценария: файла нет (создаётся дефолт),
круг «сохранили — загрузили», испорченный файл (появляется `.broken.*`).

---

## Шаг 6. `Catalog` — коллекция товаров

Объект, внутри которого массив товаров и все операции над ним. Чтобы с `Catalog`
работали `count()` и `foreach` как с обычным массивом, реализуйте встроенные
интерфейсы `Countable` и `IteratorAggregate`.

**Файл:** `src/Catalog.php`.

**Сигнатуры:**

```php
final class Catalog implements Countable, IteratorAggregate
{
    /** @param Product[] $products */
    public function __construct(array $products = []);

    public function count(): int;                    // Countable
    public function getIterator(): Traversable;      // IteratorAggregate

    /** @return Product[] */
    public function all(): array;
    public function isEmpty(): bool;
    public function find(int $id): ?Product;
    public function nextId(): int;

    public function add(Product $product): void;
    public function update(Product $product): void;   // бросит ProductNotFoundException
    public function delete(int $id): void;            // бросит ProductNotFoundException

    public function search(string $query): self;
    public function filterByCategory(Category $category): self;
    public function sortByPrice(string $direction = 'asc'): self;
    public function sortByTitle(): self;
    public function sortByStock(string $direction = 'desc'): self;

    public function paginate(int $page, int $perPage): array;
    public function stats(): array;
}
```

**Что нужно сделать по методам.**

- Приватное свойство `private array $products`; в конструкторе прогнать входной
  массив через `array_values`, чтобы ключи были `0,1,2,...`.
- `count()` — `count($this->products)`; `getIterator()` — `new ArrayIterator(...)`.
- `all()` — вернуть массив целиком; `isEmpty()` — проверка на пустоту.
- `find($id)` — цикл по товарам, вернуть `Product` или `null`.
- `nextId()` — максимум существующих `id` плюс 1 (`0 + 1`, если каталог пуст).
- `add()` — добавить товар в конец.
- `update()` — найти товар с тем же `id` и заменить; если не найден — бросить
  `new ProductNotFoundException($id)`.
- `delete($id)` — если товара нет, бросить `ProductNotFoundException`; иначе
  отфильтровать `array_filter` и снова `array_values`.
- `search($query)` — обрезать пробелы; пустой запрос вернуть копию каталога;
  иначе — фильтр по вхождению подстроки в название без учёта регистра
  (`mb_strtolower` + `str_contains`).
- `filterByCategory($category)` — оставить товары нужной категории.
- `sortByPrice`, `sortByStock` — `usort` с `<=>`; для направления `'desc'`
  результат сравнения умножается на `-1`. `sortByTitle` — сравнение
  `mb_strtolower($a->title) <=> mb_strtolower($b->title)`.
- **Все выборки возвращают НОВЫЙ `Catalog`** (`new self(...)`), поэтому методы
  можно выстраивать в цепочку.
- `paginate($page, $perPage)` — вернуть массив:
  `['items' => ..., 'page' => ..., 'perPage' => ..., 'totalPages' => ..., 'total' => ...]`.
  Всего страниц — `max(1, (int) ceil($total / $perPage))`; страницу зажать в
  границы `[1, totalPages]`; товары страницы — `array_slice($this->products, $offset, $perPage)`.
- `stats()` — вернуть `['products' => ..., 'stock' => ..., 'warehouse' => ..., 'discounted' => ...]`:
  число товаров, суммарный остаток, стоимость склада (сумма
  `priceWithDiscount() * stock`, округлить до 2 знаков) и число товаров со скидкой.

**Пример использования.**

```php
$catalog = new Catalog([$product]);
count($catalog);                            // 1
foreach ($catalog as $item) { /* ... */ }   // работает

$found = $catalog
    ->search('игр')
    ->filterByCategory(Category::Games)
    ->sortByPrice('desc');
```

**Проверь себя.** Тесты покрывают `count`, `foreach`, `find`, `nextId`, `add`,
`update`, `delete`, поиск, фильтры, сортировки, пагинацию и статистику.

---

## Шаг 7. `CatalogRepository` — связка каталога и хранилища

Репозиторий знает, **как** превратить строки из хранилища в объекты `Product`
и обратно, и предоставляет удобные операции. Хранилище передаётся снаружи
(внедрение зависимости).

**Файл:** `src/CatalogRepository.php`.

**Сигнатуры:**

```php
final class CatalogRepository
{
    public function __construct(private readonly StorageInterface $storage);

    public function load(): Catalog;
    public function save(Catalog $catalog): void;
    public function find(int $id): Product;                 // бросит ProductNotFoundException
    public function create(array $input): Product;
    public function update(int $id, array $input): Product; // бросит ProductNotFoundException
    public function delete(int $id): void;                  // бросит ProductNotFoundException
    private function makeProduct(int $id, array $input): Product;
}
```

**Что нужно сделать.**

- `load()`: взять строки из `$this->storage->load()` и через `array_map` превратить
  их в `Product::fromArray(...)`, завернуть в `new Catalog(...)`.
- `save(Catalog $catalog)`: через `array_map` получить `$product->toArray()` и
  передать в `$this->storage->save(...)`.
- `find($id)`: загрузить каталог, найти товар; если `null` — бросить
  `ProductNotFoundException`.
- `create($input)`: загрузить каталог, собрать новый товар с `id = $catalog->nextId()`,
  добавить его, сохранить каталог, вернуть товар.
- `update($id, $input)`: загрузить каталог, собрать товар с этим `id`, вызвать
  `$catalog->update($product)` (он сам бросит исключение, если товара нет),
  сохранить.
- `delete($id)`: загрузить, `$catalog->delete($id)`, сохранить.
- `makeProduct($id, $input)`: собрать `Product` из «сырых» данных формы —
  обрезать пробелы у названия, привести числа к `(float)`/`(int)`, категорию
  получить через `Category::from((string) $input['category'])`.

**Пример использования.**

```php
$repository = new CatalogRepository(new JsonFileStorage(__DIR__ . '/data/catalog.json'));

$created = $repository->create([
    'title' => 'Планшет',
    'category' => 'Техника',
    'price' => '19990',
    'stock' => '4',
    'discount' => '0',
]);
echo $created->id;   // 9
```

**Проверь себя.** Тесты проверяют `create`, `update`, `delete`, `find` и что
отсутствующий товар бросает `ProductNotFoundException`.

---

## Шаг 8. `Html` и свои исключения

**`src/Html.php`** — безопасный вывод в HTML:

```php
final class Html
{
    public static function e(string|int|float|null $value): string;
}
```

Метод возвращает `htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')`. Это
статический метод: вызывается `Html::e($text)`.

**`src/ProductNotFoundException.php`** — своё исключение:

```php
class ProductNotFoundException extends RuntimeException
{
    public function __construct(public readonly int $productId);
}
```

В конструкторе сохраните `productId` и вызовите
`parent::__construct("Товар #{$productId} не найден")`.

**`src/StorageException.php`** — пустой класс, наследник `RuntimeException`:

```php
class StorageException extends RuntimeException
{
}
```

**`src/InvalidArgumentException.php`** — наследник встроенного
`\InvalidArgumentException`. Так мы различаем «ошибку аргумента» в своём коде
и при желании ловим её отдельно:

```php
use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException
{
}
```

**Проверь себя.** `Html::e('<script>')` не должен содержать `<script>`;
несуществующий id должен приводить к `ProductNotFoundException`.

---

## Шаг 9. Страницы приложения

Теперь, когда «модель» готова, перепишем страницы из работы 5 на классы. Логика та
же: поиск, фильтр, сортировка, пагинация, добавление, редактирование, удаление.

### `app.php` — единая сборка

Создайте небольшой файл, который подключает автозагрузку и собирает зависимости:

```php
require __DIR__ . '/bootstrap.php';

const PER_PAGE = 5;

$storage = new JsonFileStorage(__DIR__ . '/data/catalog.json');
$repository = new CatalogRepository($storage);
$validator = new ProductValidator();
```

Все страницы будут начинаться с `require __DIR__ . '/app.php';` и получать готовые
`$repository` и `$validator`.

### `layout.php` — общие обёртки

Функции разметки (это по-прежнему обычные функции, классы им не нужны):
`page_header(string $title, string $active = '')`, `page_footer()`,
`flash_message()`, `banner(string $type, string $text)`,
`query_url(array $params)`, `product_form(array $values, array $errors, string $action, string $label)`.

**Напоминания из прошлых работ:**

- всё, что выводится в HTML, — только через `Html::e(...)`, включая значения полей;
- `query_url()` строит ссылки с сохранением текущих `$_GET` через `array_merge`
  и `http_build_query` — чтобы поиск и фильтр не «слетали» при переходе по страницам;
- сообщение об успехе берётся из адреса (`?added=`, `?updated=`, `?deleted=`).

### `index.php` — список

1. `$catalog = $repository->load();`
2. Прочитайте и «зажмите» параметры: `search`, `category` (только `all` или
   существующая категория), `sort` (белый список
   `price_asc, price_desc, title, stock`), `page`.
3. Соберите выборку цепочкой: `search()` → `filterByCategory()` →
   `sortBy*` через `match`.
4. `$pagination = $filtered->paginate($page, PER_PAGE);`
5. Статистику возьмите из полного каталога: `$catalog->stats()`.
6. Выведите таблицу товаров, пагинацию и сообщение из `flash_message()`.
   Если товаров нет — покажите «Ничего не найдено», а не ошибку.

### `add.php` — добавление

- **GET** — просто форма.
- **POST** — `$errors = $validator->validate($_POST);`.
  - если ошибок нет: `$product = $repository->create($_POST);` и редирект
    `header('Location: index.php?added=' . $product->id); exit;`
  - если ошибки есть: показать форму снова, с сообщениями и сохранёнными
    значениями (`$values = $_POST;`).

### `edit.php` — редактирование

- Возьмите `$id = (int) ($_GET['id'] ?? 0);`.
- `$product = $repository->find($id);` в `try`, а в `catch (ProductNotFoundException)`
  отдайте `http_response_code(404);`, текст «Товар #N не найден» и `exit;`.
- **GET** — форма, поля предзаполнены `$product->toArray()`.
- **POST** — валидация; при успехе `$repository->update($id, $_POST);` и редирект
  `index.php?updated=$id` (id не меняется).

### `delete.php` — удаление с подтверждением

- `find($id)` с обработкой 404, как в `edit.php`.
- **GET** — карточка товара и вопрос «Удалить?».
- Кнопка — маленькая **POST-форма** на `delete.php?id=N`.
- **POST** — `$repository->delete($id);` и редирект `index.php?deleted=$id`.

**Проверь себя.** Откройте сайт и прогоните полный круг:
добавить → товар в таблице и в `data/catalog.json`; изменить → данные обновились,
id тот же; удалить → товар исчез; `edit.php?id=999` → 404.

---

## Частые ошибки и как их узнать

| Сообщение PHP                                   | Что случилось                                      |
|-------------------------------------------------|----------------------------------------------------|
| `Class "App\Product" not found`                 | нет файла `src/Product.php`, опечатка в имени класса, не подключён `bootstrap.php` или не выполнен `composer install` |
| `must implement interface ...`                  | в классе не написаны методы из интерфейса          |
| `Cannot access private property`                | обращение к `private`-свойству снаружи класса      |
| `Cannot modify readonly property`               | попытка изменить `readonly`-свойство после `new`   |
| `Too few arguments to function __construct`     | при `new` передали меньше аргументов, чем нужно    |
| `Uncaught ProductNotFoundException`             | не обернули `find()`/`update()` в `try/catch`      |

## Запуск

Сначала один раз установите автозагрузчик (нужен Composer):

```bash
composer install                # создаст vendor/autoload.php по правилу PSR-4
```

Затем:

```bash
php test.php                    # автотесты классов (ищут их в src/)
php -S localhost:8000 -t .      # сайт: http://localhost:8000/index.php
```

Тесты запускаются из папки `pr6`. Они НЕ трогают `data/catalog.json` — работают
на временных файлах в системном каталоге.

## Критерии приёмки

- [ ] `composer install` проходит без ошибок и создаёт `vendor/autoload.php`;
- [ ] `php test.php` завершается с «ошибок: 0» и кодом `0`;
- [ ] в каталоге `src/` есть все классы, пути и имена файлов совпадают с правилом PSR-4;
- [ ] в браузере: добавить → товар появился и лежит в `data/catalog.json`;
- [ ] изменить → данные и сообщение «обновлён» видны, id не изменился;
- [ ] удалить → товар исчез, на странице сообщение «удалён»;
- [ ] `edit.php?id=999` возвращает статус 404 и текст «Товар #999 не найден»;
- [ ] поиск + фильтр + пагинация не «слетают» при переходе по страницам;
- [ ] добавление с ценой-текстом показывает ошибку под полем и не теряет введённое;
- [ ] категория — это `Category`, а не строка; в коде нет `$product['title']`.
