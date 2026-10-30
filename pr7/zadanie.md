# Работа 7. MVC и FrontController — сайт-визитка с новостями

## Цель

Собрать небольшой сайт-визитку digital-студии с новостями на **MVC** и
**FrontController**. Приложение должно использовать:

- **League Route 7** для маршрутизации;
- **Laminas Diactoros** для PSR-7 запроса и ответа;
- **mock-данные** в JSON и репозиторий;
- **нативные PHP-шаблоны** с Bootstrap 5;
- **ручную сборку зависимостей** без PSR-11-контейнера.

В результате один вход `public/index.php` принимает все запросы, Router выбирает
контроллер, контроллер получает данные из репозитория и возвращает HTML-ответ.

## Что должно получиться

```text
pr7/
├── teoria.md              — конспект (уже есть)
├── zadanie.md             — это задание
├── test.php               — автотесты (уже есть)
├── composer.json          — зависимости и PSR-4
├── config/
│   └── site.php           — название, услуги, контакты студии
├── data/
│   └── news.json          — mock-новости
├── public/
│   └── index.php          — FrontController
├── src/
│   ├── Application.php            — composition root
│   ├── Controller/
│   │   ├── Controller.php
│   │   ├── HomeController.php
│   │   ├── NewsController.php
│   │   └── PageController.php
│   ├── Http/
│   │   └── ResponseEmitter.php
│   ├── Model/
│   │   ├── News.php
│   │   └── NewsCategory.php
│   ├── Repository/
│   │   └── NewsRepository.php
│   ├── Support/
│   │   └── Html.php
│   └── View/
│       └── View.php
├── templates/
│   ├── layout.php
│   ├── home.php
│   ├── about.php
│   ├── contacts.php
│   ├── news/
│   │   ├── index.php
│   │   └── show.php
│   ├── partials/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── news-card.php
│   └── errors/
│       ├── 404.php
│       └── 500.php
└── vendor/                — создаётся Composer
```

Эталонное решение находится в `pr7/solution/`. Сначала попробуйте пройти шаги
самостоятельно, а потом сверьтесь с ним.

После каждого шага запускайте тесты:

```bash
php test.php
```

В конце должно быть «ошибок: 0». Если тестируете эталон:

```bash
PR7_AUTOLOAD=solution/vendor/autoload.php php test.php
```

---

## Шаг 0. Composer и PSR-4

Создайте `composer.json`:

```json
{
    "require": {
        "php": ">=8.3",
        "league/route": "^7.0",
        "laminas/laminas-diactoros": "^3.9"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Выполните:

```bash
composer install
```

Класс `App\Model\News` должен лежать в `src/Model/News.php`, класс
`App\Controller\HomeController` — в `src/Controller/HomeController.php`.
Правило простое: **пространство имён повторяет путь к файлу**.

> `vendor/` не редактируют и не коммитят. Она воспроизводится командой
> `composer install`.

---

## Шаг 1. Mock-данные новостей

Создайте `data/news.json` — массив из 8 новостей. Каждая новость:

```json
{
    "id": 1,
    "slug": "novyj-ofis",
    "title": "Переезжаем в новый офис",
    "category": "company",
    "excerpt": "Короткое описание для карточки.",
    "body": "Первый абзац.\n\nВторой абзац.",
    "published_at": "2026-08-22"
}
```

Требования:

- `slug` — уникальный, только латиница, цифры и дефисы;
- `category` — одно из значений `company`, `projects`, `life`;
- `published_at` — дата в формате `YYYY-MM-DD`;
- `body` — несколько абзацев, разделённых `\n\n`.

Сделайте 8 разных новостей, чтобы были видны карточки, категории и сортировка по дате.
Базы данных в работе нет: JSON — это **mock-хранилище**.

---

## Шаг 2. Категория и модель

### `src/Model/NewsCategory.php`

```php
enum NewsCategory: string
{
    case Company = 'company';
    case Projects = 'projects';
    case Life = 'life';

    public function label(): string;
    public function badgeClass(): string; // класс Bootstrap: text-bg-primary и т.п.
}
```

`label()` возвращает человекочитаемое название: «Компания», «Проекты», «Жизнь студии».
`badgeClass()` — CSS-класс для бейджа Bootstrap.

### `src/Model/News.php`

```php
final readonly class News
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public NewsCategory $category,
        public string $excerpt,
        public string $body,
        public DateTimeImmutable $publishedAt,
    );

    public static function fromArray(array $row): self;
    public function formattedDate(): string;
}
```

Что нужно сделать:

- в конструкторе проверить, что `id > 0`, `slug` и `title` не пустые;
- `fromArray()` привести типы, получить категорию через `NewsCategory::tryFrom()`;
- если категория неизвестна — бросить `InvalidArgumentException`;
- дату создать через `DateTimeImmutable::createFromFormat('!Y-m-d', ...)`;
- если дата некорректна — бросить `InvalidArgumentException`;
- `formattedDate()` возвращает `d.m.Y`.

Пример:

```php
$news = News::fromArray($row);
echo $news->formattedDate();       // 22.08.2026
echo $news->category->label();     // Компания
```

---

## Шаг 3. Репозиторий

### `src/Repository/NewsRepository.php`

```php
final class NewsRepository
{
    public function __construct(private readonly string $file);

    /** @return News[] */
    public function all(): array;

    /** @return News[] */
    public function latest(int $limit = 3): array;

    public function findBySlug(string $slug): ?News;
}
```

Требования:

- `all()` читает JSON, проверяет, что это список, и превращает строки в `News`;
- `latest($limit)` сортирует копию по `publishedAt` по убыванию и обрезает через
  `array_slice`;
- `findBySlug()` ищет новость по точному `slug`, иначе возвращает `null`;
- если файла нет, он не читается или JSON испорчен — бросить `RuntimeException`;
- закэшировать загруженные новости в приватном свойстве, чтобы не читать файл дважды
  за один запрос.

> Контроллер не должен знать, что внутри JSON. Он работает только с методами
> репозитория. Позже JSON можно заменить на API или базу.

**Проверь себя.** Тесты должны найти 8 новостей, показать 3 последние и найти новость
по `slug`.

---

## Шаг 4. Экранирование и View

### `src/Support/Html.php`

```php
final class Html
{
    public static function e(string|int|float|null $value): string;
}
```

Метод возвращает `htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')`.

### `src/View/View.php`

```php
final class View
{
    public function __construct(
        private readonly string $path,
        private readonly array $shared = [],
    );

    public function render(string $template, array $data = []): string;
    public function partial(string $template, array $data = []): string;
}
```

Как устроить `render()`:

1. отрендерить запрошенный шаблон в строку `$content`;
2. отрендерить `layout`, передав ему `$content` и остальные данные;
3. вернуть итоговую строку.

Для рендера одного файла:

```php
$file = $this->path . '/' . $template . '.php';
$view = $this;
$shared = $this->shared;
extract($data, EXTR_SKIP);
extract($shared, EXTR_SKIP);

ob_start();
require $file;
return (string) ob_get_clean();
```

Обратите внимание на `$shared = $this->shared`: `extract()` принимает массив по
ссылке, а `readonly`-свойство нельзя передавать по ссылке. Копия решает проблему.

`partial()` рендерит файл без layout — для карточек, шапки и подвала.

---

## Шаг 5. Шаблоны и Bootstrap 5

Создайте `templates/layout.php` — общую рамку:

- `<!DOCTYPE html>`, `lang="ru"`, meta viewport;
- Bootstrap 5 CSS и JS через CDN;
- подключение partials `header` и `footer`;
- вывод `$content` без экранирования (это уже готовый HTML);
- active-состояние навигации по переменной `$active`.

`partials/header.php`:

- навигация: «Главная», «Новости», «О нас», «Контакты»;
- активный пункт получает класс `active`;
- название студии берётся из `$site['name']`.

`partials/footer.php`:

- год, название студии, email и телефон;
- всё выводится через `Html::e()`.

`partials/news-card.php`:

- категория бейджем;
- дата;
- заголовок-ссылка на `/news/{slug}`;
- краткое описание.

Страницы:

- `home.php` — hero-блок, услуги, последние 3 новости;
- `news/index.php` — все новости карточками;
- `news/show.php` — заголовок, дата, категория, текст с `nl2br(Html::e(...))`;
- `about.php` — рассказ о студии и принципы;
- `contacts.php` — телефон, почта, адрес, часы работы;
- `errors/404.php` и `errors/500.php` — понятные страницы ошибок.

Правило безопасности: **любой вывод данных — только через `Html::e()`**.

---

## Шаг 6. Контроллеры

### Базовый `src/Controller/Controller.php`

```php
abstract class Controller
{
    public function __construct(protected readonly View $view)
    {
    }

    protected function html(string $template, array $data = [], int $status = 200): ResponseInterface
    {
        return new HtmlResponse($this->view->render($template, $data), $status);
    }
}
```

### `HomeController`

```php
public function index(ServerRequestInterface $request, array $args): ResponseInterface
```

Возвращает `home` с последними тремя новостями.

### `NewsController`

```php
public function index(ServerRequestInterface $request, array $args): ResponseInterface;
public function show(ServerRequestInterface $request, array $args): ResponseInterface;
```

`show()` берёт `$args['slug']`. Если новости нет — бросает
`League\Route\Http\Exception\NotFoundException`. Это исключение поймает
`Application` и покажет 404.

### `PageController`

```php
public function about(ServerRequestInterface $request, array $args): ResponseInterface;
public function contacts(ServerRequestInterface $request, array $args): ResponseInterface;
```

Контроллеры **не печатают HTML через `echo`** и не читают JSON напрямую. Они получают
готовый `View` и `NewsRepository`.

---

## Шаг 7. `Application`: ручная сборка и маршруты

Создайте `src/Application.php` — composition root. Никакого PSR-11-контейнера.

```php
final class Application
{
    public function __construct(private readonly string $basePath)
    {
        $site = require $basePath . '/config/site.php';
        $view = new View($basePath . '/templates', ['site' => $site]);
        $repository = new NewsRepository($basePath . '/data/news.json');

        $home = new HomeController($view, $repository);
        $news = new NewsController($view, $repository);
        $pages = new PageController($view);

        $router = new Router();
        $router->get('/', [$home, 'index'])->setName('home');
        $router->get('/news', [$news, 'index'])->setName('news.index');
        $router->get('/news/{slug:slug}', [$news, 'show'])->setName('news.show');
        $router->get('/about', [$pages, 'about'])->setName('about');
        $router->get('/contacts', [$pages, 'contacts'])->setName('contacts');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface;
}
```

В `handle()`:

```php
try {
    return $this->router->dispatch($request);
} catch (NotFoundException $error) {
    return new HtmlResponse($view->render('errors/404', [...]), 404);
} catch (Throwable $error) {
    return new HtmlResponse($view->render('errors/500', [...]), 500);
}
```

В режиме `APP_DEBUG=1` можно вывести текст ошибки, иначе — общее сообщение.

**Почему без контейнера.** Все зависимости создаются явно и один раз. Видно, кто кого
получает. Для проекта такого размера это проще и надёжнее, чем настраивать PSR-11.

---

## Шаг 8. FrontController и эмиссия ответа

### `src/Http/ResponseEmitter.php`

```php
final class ResponseEmitter
{
    public function emit(ResponseInterface $response): void;
}
```

- если заголовки ещё не отправлены — вызвать `http_response_code()`;
- пройти по `$response->getHeaders()` и вызвать `header()`;
- вывести `(string) $response->getBody()`.

### `public/index.php`

```php
<?php

declare(strict_types=1);

use App\Application;
use App\Http\ResponseEmitter;
use Laminas\Diactoros\ServerRequestFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$application = new Application(dirname(__DIR__));
$response = $application->handle($request);

(new ResponseEmitter())->emit($response);
```

Это и есть FrontController: единственная точка входа для всех URL.

---

## Шаг 9. Запуск и проверка

```bash
composer install
php -S localhost:8000 -t public
```

Откройте:

- `http://localhost:8000/` — главная;
- `http://localhost:8000/news` — список новостей;
- `http://localhost:8000/news/novyj-ofis` — новость;
- `http://localhost:8000/news/no-such-news` — 404;
- `http://localhost:8000/about` — о студии;
- `http://localhost:8000/contacts` — контакты.

Проверьте, что:

- в исходном коде страницы нет ошибок PHP и предупреждений;
- 404 отдаёт статус `404`, а не `200`;
- заголовок `Content-Type` — `text/html; charset=utf-8`;
- при вводе `<script>` в данных он выводится как текст, а не исполняется.

---

## Самостоятельная работа

Выберите задания по уровню. Базовые задания обязательны, остальные — по желанию.

### Уровень 1. Обязательный

1. **Страницы «О нас» и «Контакты»** — сделайте так, чтобы они были в навигации и
   подсвечивались как активные.
2. **Красивая 404** — добавьте ссылку на главную и понятное сообщение.
3. **Контакты из конфига** — телефон, email и адрес должны браться из `config/site.php`,
   а не быть зашиты в шаблон.
4. **Проверка данных** — убедитесь, что весь вывод в шаблонах идёт через `Html::e()`.

### Уровень 2. Новости

5. **Поиск** — добавьте на `/news` форму поиска по названию и краткому описанию.
   Параметр `q` читайте из `$request->getQueryParams()`.
6. **Фильтр по категории** — выпадающий список категорий, параметр `category`.
   Неизвестная категория должна игнорироваться.
7. **Пагинация** — по 4 новости на страницу, параметр `page`. Не выходите за границы
   диапазона. Сохраняйте поиск и фильтр в ссылках.
8. **Сортировка** — по дате (по умолчанию) и по названию; белый список значений.

### Уровень 3. Форма обратной связи

9. **Форма на `/contacts`** — поля «Имя», «Email», «Сообщение».
10. **Валидация** — имя не короче 2 символов, email через `filter_var(...,
    FILTER_VALIDATE_EMAIL)`, сообщение не короче 10 символов.
11. **POST + PRG** — после успешной отправки редирект на `/contacts?sent=1`
    (`RedirectResponse`), сообщение об успехе из query-параметра.
12. **Сохранение введённого** — при ошибках форма показывает введённые значения и
    сообщения под полями.

### Уровень 4. Админка и данные

13. **CRUD новостей** — добавление, редактирование и удаление через
    `/admin/news`. Для форм используйте POST, после успеха — PRG-редирект.
14. **Сохранение JSON** — добавьте в репозиторий метод `save(array $news)`, который
    записывает данные с `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE` и `LOCK_EX`.
15. **CSRF-токен** — скрытое поле в формах и проверка токена в POST-обработчике.
16. **Интерфейс репозитория** — выделите `NewsRepositoryInterface` и сделайте вторую
    реализацию, например читающую данные из другого файла. Подставьте её в
    `Application` без изменения контроллеров.

### Уровень 5. Архитектура

17. **PSR-15 middleware** — логирование метода, пути и времени выполнения запроса.
    Подключите через `$router->middleware(...)`.
18. **Именованные маршруты** — используйте `setName()` и `generateUrl()` для ссылок
    в шаблонах.
19. **Свои исключения** — `NewsNotFoundException` вместо прямого использования
    `NotFoundException` Route.
20. **Тесты** — добавьте в `test.php` проверки поиска, фильтра, пагинации и формы.

### Что нельзя делать

- использовать PSR-11-контейнер (`league/container`, `php-di` и т.п.);
- читать `data/news.json` прямо в контроллере или шаблоне;
- выводить данные без экранирования;
- печатать HTML через `echo` из контроллера;
- возвращать из контроллера строку вместо `ResponseInterface`;
- добавлять `require` для каждого класса вручную — для этого есть Composer.

---

## Частые ошибки

| Сообщение / симптом | Причина |
|---|---|
| `Class "App\..." not found` | не выполнен `composer install`, неверный путь или имя файла |
| `Cannot modify readonly property ...` | передали `readonly`-массив в `extract()` по ссылке; сделайте копию |
| `Uncaught NotFoundException` | не обернули `dispatch()` в `try/catch` в `Application` |
| 404 приходит со статусом 200 | ответ создан без второго аргумента `HtmlResponse(..., 404)` |
| `Call to undefined method ...::dispatch()` | используется не тот класс Router или не установлен `league/route` |
| Страница пустая | шаблон не найден или View не вернул строку из буфера |
| В HTML виден `<script>` | забыли `Html::e()` вокруг вывода |
| `Content-Type` не тот | ответ собран не через `HtmlResponse` или заголовок перезаписан |

---

## Критерии приёмки

- [ ] `composer install` проходит без ошибок;
- [ ] `php test.php` завершается с «ошибок: 0» и кодом `0`;
- [ ] приложение запускается командой `php -S localhost:8000 -t public`;
- [ ] `/`, `/news`, `/news/{slug}`, `/about`, `/contacts` отдают 200;
- [ ] несуществующий адрес отдаёт 404;
- [ ] новости берутся из `data/news.json` через `NewsRepository`;
- [ ] в контроллерах нет чтения JSON и больших кусков HTML;
- [ ] в шаблонах весь пользовательский вывод экранирован;
- [ ] маршруты зарегистрированы в `Application`, используется Route 7;
- [ ] запрос и ответ — объекты PSR-7 (Diactoros);
- [ ] PSR-11-контейнер не используется;
- [ ] выполнено минимум одно задание из уровней 2–5.
