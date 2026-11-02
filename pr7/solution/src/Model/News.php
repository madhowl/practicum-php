<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use InvalidArgumentException;

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
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('Id новости должен быть положительным');
        }

        if (trim($slug) === '') {
            throw new InvalidArgumentException('Slug новости не может быть пустым');
        }

        if (trim($title) === '') {
            throw new InvalidArgumentException('Название новости не может быть пустым');
        }
    }

    public static function fromArray(array $row): self
    {
        $category = NewsCategory::tryFrom((string) ($row['category'] ?? ''));

        if ($category === null) {
            throw new InvalidArgumentException('Неизвестная категория новости');
        }

        $publishedAt = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            (string) ($row['published_at'] ?? '')
        );

        if ($publishedAt === false) {
            throw new InvalidArgumentException('Дата новости должна быть в формате YYYY-MM-DD');
        }

        return new self(
            id: (int) ($row['id'] ?? 0),
            slug: trim((string) ($row['slug'] ?? '')),
            title: trim((string) ($row['title'] ?? '')),
            category: $category,
            excerpt: trim((string) ($row['excerpt'] ?? '')),
            body: trim((string) ($row['body'] ?? '')),
            publishedAt: $publishedAt,
        );
    }

    public function formattedDate(): string
    {
        return $this->publishedAt->format('d.m.Y');
    }
}
