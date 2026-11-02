<?php

declare(strict_types=1);

namespace App\Model;

enum NewsCategory: string
{
    case Company = 'company';
    case Projects = 'projects';
    case Life = 'life';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Компания',
            self::Projects => 'Проекты',
            self::Life => 'Жизнь студии',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Company => 'text-bg-primary',
            self::Projects => 'text-bg-success',
            self::Life => 'text-bg-warning',
        };
    }

    /** @return array<int, string> */
    public static function labels(): array
    {
        return array_map(
            fn (self $category): string => $category->label(),
            self::cases()
        );
    }
}
