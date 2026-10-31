<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Instructor = 'instructor';
    case Administrator = 'admin';

    public function canTeach(): bool
    {
        return $this === self::Instructor || $this === self::Administrator;
    }

    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
