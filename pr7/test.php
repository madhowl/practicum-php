<?php

// Автотесты работы 7. Приложение студента лежит в pr7/.
// Автозагрузчик можно переопределить переменной окружения PR7_AUTOLOAD.
$GLOBALS['tests'] = 0;
$GLOBALS['failed'] = 0;

function check(string $name, mixed $actual, mixed $expected): void
{
    $GLOBALS['tests']++;

    if ($actual === $expected) {
        echo "  PASS  $name\n";
        return;
    }

    $GLOBALS['failed']++;
    echo "  FAIL  $name\n";
    echo "        expected: " . var_export($expected, true) . "\n";
    echo "        actual:   " . var_export($actual, true) . "\n";
}

function check_true(string $name, bool $condition): void
{
    check($name, $condition, true);
}

function check_contains(string $name, string $haystack, string $needle): void
{
    check_true($name, str_contains($haystack, $needle));
}

$autoload = getenv('PR7_AUTOLOAD') ?: __DIR__ . '/vendor/autoload.php';

if (!str_starts_with($autoload, DIRECTORY_SEPARATOR)) {
    $autoload = __DIR__ . '/' . $autoload;
}

if (!is_file($autoload)) {
    echo "Не найден автозагрузчик: {$autoload}\n";
    echo "Сначала выполните в папке pr7: composer install\n";
    exit(1);
}

require $autoload;

use App\Application;
use App\Model\News;
use App\Model\NewsCategory;
use App\Repository\NewsRepository;
use App\Support\Html;
use App\View\View;
use Laminas\Diactoros\ServerRequest;

$basePath = dirname($autoload, 2);

echo "Работа 7. MVC и FrontController: сайт-визитка с новостями\n\n";

// --- Модель ---
$news = new News(
    id: 1,
    slug: 'test-news',
    title: 'Тестовая новость',
    category: NewsCategory::Company,
    excerpt: 'Короткое описание',
    body: "Первый абзац.\n\nВторой абзац.",
    publishedAt: new DateTimeImmutable('2026-01-02'),
);

check('News::formattedDate форматирует дату', $news->formattedDate(), '02.01.2026');
check('NewsCategory::label', NewsCategory::Company->label(), 'Компания');
check('NewsCategory::tryFrom неизвестного значения', NewsCategory::tryFrom('unknown'), null);

$fromArray = News::fromArray([
    'id' => 5,
    'slug' => 'from-array',
    'title' => 'Из массива',
    'category' => 'projects',
    'excerpt' => 'Описание',
    'body' => 'Текст',
    'published_at' => '2026-02-03',
]);

check('News::fromArray сохраняет категорию', $fromArray->category, NewsCategory::Projects);
check('News::fromArray сохраняет дату', $fromArray->formattedDate(), '03.02.2026');

$threw = false;
try {
    News::fromArray([
        'id' => 1,
        'slug' => 'bad',
        'title' => 'Плохая категория',
        'category' => 'unknown',
        'excerpt' => '',
        'body' => '',
        'published_at' => '2026-01-01',
    ]);
} catch (InvalidArgumentException) {
    $threw = true;
}
check_true('неизвестная категория бросает исключение', $threw);

// --- Репозиторий ---
$repository = new NewsRepository($basePath . '/data/news.json');

check('репозиторий загружает 8 новостей', count($repository->all()), 8);
check('latest(3) возвращает 3 новости', count($repository->latest(3)), 3);
check('latest сортирует по дате: первая самая свежая', $repository->latest(1)[0]->slug, 'itogi-sentyabrya');
check('findBySlug находит новость', $repository->findBySlug('novyj-ofis')?->title, 'Переезжаем в новый офис');
check('findBySlug неизвестного slug даёт null', $repository->findBySlug('no-such-news'), null);

// --- Экранирование и View ---
check('Html::e экранирует теги', Html::e('<script>x</script>'), '&lt;script&gt;x&lt;/script&gt;');

$site = require $basePath . '/config/site.php';
$view = new View($basePath . '/templates', ['site' => $site]);
$dangerous = new News(
    id: 99,
    slug: 'xss',
    title: '<script>alert(1)</script>',
    category: NewsCategory::Life,
    excerpt: 'Описание',
    body: 'Текст',
    publishedAt: new DateTimeImmutable('2026-01-01'),
);

$html = $view->render('news/show', [
    'title' => $dangerous->title,
    'active' => 'news',
    'news' => $dangerous,
]);

check_true('View не выводит <script> из данных', !str_contains($html, '<script>alert(1)</script>'));
check_contains('View экранирует заголовок', $html, '&lt;script&gt;alert(1)&lt;/script&gt;');
check_contains('View подставляет layout и Bootstrap', $html, 'bootstrap@5.3.3');
check_contains('View подставляет название сайта', $html, $site['name']);

$card = $view->partial('partials/news-card', ['news' => $fromArray]);
check_contains('partial news-card строит ссылку', $card, '/news/from-array');

// --- Маршруты и FrontController ---
$application = new Application($basePath);

$request = static fn (string $path): ServerRequest => new ServerRequest(
    uri: 'https://example.test' . $path,
    method: 'GET'
);

$response = $application->handle($request('/'));
check('GET / — статус 200', $response->getStatusCode(), 200);
check_contains('GET / — главная страница', (string) $response->getBody(), $site['name']);
check_contains('GET / — блок последних новостей', (string) $response->getBody(), 'Последние новости');
check('GET / — Content-Type', $response->getHeaderLine('Content-Type'), 'text/html; charset=utf-8');

$response = $application->handle($request('/news'));
check('GET /news — статус 200', $response->getStatusCode(), 200);
check_contains('GET /news — список новостей', (string) $response->getBody(), 'Подводим итоги сентября');

$response = $application->handle($request('/news/novyj-ofis'));
check('GET /news/{slug} — статус 200', $response->getStatusCode(), 200);
check_contains('GET /news/{slug} — заголовок новости', (string) $response->getBody(), 'Переезжаем в новый офис');
check_contains('GET /news/{slug} — текст новости', (string) $response->getBody(), 'Новое пространство');

$response = $application->handle($request('/news/no-such-news'));
check('GET /news/unknown — статус 404', $response->getStatusCode(), 404);
check_contains('GET /news/unknown — страница 404', (string) $response->getBody(), 'Страница не найдена');

$response = $application->handle($request('/about'));
check('GET /about — статус 200', $response->getStatusCode(), 200);
check_contains('GET /about — страница о студии', (string) $response->getBody(), 'Кто мы');

$response = $application->handle($request('/contacts'));
check('GET /contacts — статус 200', $response->getStatusCode(), 200);
check_contains('GET /contacts — контакты', (string) $response->getBody(), $site['phone']);

// --- итог ---
echo "\nИтого проверок: {$GLOBALS['tests']}, ошибок: {$GLOBALS['failed']}\n";
exit($GLOBALS['failed'] > 0 ? 1 : 0);
