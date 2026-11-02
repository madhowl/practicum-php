<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\News;
use RuntimeException;

final class NewsRepository
{
    /** @var News[]|null */
    private ?array $cache = null;

    public function __construct(private readonly string $file)
    {
    }

    /** @return News[] */
    public function all(): array
    {
        return $this->load();
    }

    /** @return News[] */
    public function latest(int $limit = 3): array
    {
        if ($limit < 1) {
            return [];
        }

        $news = $this->load();
        usort(
            $news,
            fn (News $a, News $b): int => $b->publishedAt <=> $a->publishedAt
        );

        return array_slice($news, 0, $limit);
    }

    public function findBySlug(string $slug): ?News
    {
        $slug = trim($slug);

        foreach ($this->load() as $news) {
            if ($news->slug === $slug) {
                return $news;
            }
        }

        return null;
    }

    /** @return News[] */
    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (!is_file($this->file) || !is_readable($this->file)) {
            throw new RuntimeException('Файл новостей не найден: ' . $this->file);
        }

        $json = file_get_contents($this->file);

        if ($json === false) {
            throw new RuntimeException('Не удалось прочитать файл новостей');
        }

        $rows = json_decode($json, true);

        if (!is_array($rows) || !array_is_list($rows)) {
            throw new RuntimeException('Файл новостей должен содержать JSON-массив');
        }

        $this->cache = array_map(
            fn (array $row): News => News::fromArray($row),
            $rows
        );

        return $this->cache;
    }
}
