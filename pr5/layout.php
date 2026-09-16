<?php

// =====================================================================
// Общие HTML-«обёртки» для страниц приложения. Процедурный вариант
// переиспользуемого каркаса без ООП.
// =====================================================================

function page_header(string $title = 'Каталог товаров'): void
{
    echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>$title</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 980px; margin: 0 auto; padding: 0 16px 40px; color: #222; }
        nav { display: flex; gap: 18px; align-items: center; padding: 14px 0; border-bottom: 1px solid #e5e5e5; }
        nav a { color: #2563eb; text-decoration: none; font-weight: 500; }
        nav a.active { color: #111; }
        h1 { font-size: 1.35rem; margin: 22px 0 6px; }
        .muted { color: #888; font-size: .85rem; }
        form { background: #f6f6f6; border: 1px solid #ddd; border-radius: 8px; padding: 14px 16px; margin-top: 16px; }
        label { display: block; margin: 10px 0 3px; font-size: .85rem; color: #555; }
        input, select { padding: 6px 8px; border: 1px solid #bbb; border-radius: 5px; font-size: .95rem; }
        button { margin-top: 14px; padding: 7px 16px; border: 0; border-radius: 5px; background: #2563eb; color: #fff; cursor: pointer; }
        button.danger { background: #c0392b; }
        .grid { display: flex; gap: 10px; flex-wrap: wrap; }
        .error { display: block; margin-top: 4px; color: #c0392b; font-size: .8rem; }
        .notice { background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20; padding: 10px 14px; border-radius: 8px; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e5e5; font-size: .95rem; vertical-align: middle; }
        th { background: #fafafa; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: .78rem; }
        .badge.ok { background: #e8f5e9; color: #1b5e20; }
        .badge.low { background: #fff3e0; color: #e65100; }
        .badge.no { background: #fdecea; color: #b71c1c; }
        .actions a { color: #2563eb; text-decoration: none; margin-right: 10px; }
        .actions a.del { color: #c0392b; }
        .pager { display: flex; gap: 8px; margin-top: 14px; align-items: center; }
        .pager a, .pager span { padding: 4px 10px; border: 1px solid #d3d3d3; border-radius: 5px; text-decoration: none; color: #2563eb; }
        .pager .cur { background: #2563eb; color: #fff; border-color: #2563eb; }
        .stats { display: flex; gap: 16px; flex-wrap: wrap; margin: 6px 0 0; }
        .stats b { display: block; font-size: 1.05rem; }
        .empty { margin-top: 20px; color: #777; }
        code { background: #f2f2f2; padding: 1px 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="active">Каталог</a>
        <a href="add.php">Добавить товар</a>
    </nav>
HTML;
}

function page_footer(): void
{
    echo "\n</body>\n</html>\n";
}

// Кнопки сообщений об успехе/ошибке (для PRG-редиректов).
function banner(?string $type, ?string $text): void
{
    if ($text === null) {
        return;
    }
    $color = match ($type) {
        'error' => 'border:1px solid #ef9a9a;background:#fdecea;color:#b71c1c;',
        default => 'border:1px solid #a5d6a7;background:#e8f5e9;color:#1b5e20;',
    };
    echo '<p class="notice" style="' . $color . '">' . e($text) . '</p>';
}