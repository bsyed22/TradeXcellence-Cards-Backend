<?php

namespace App\Enum;

enum CoinsBuyStatusEnum: int
{
    case CREATED = 2;
    case PAID = 3;
    case CANCELLED = 4;
    case UNRESOLVED = 5;

    public static function getStatusName(int $statusCode): string
    {
        return match ($statusCode) {
            self::CREATED->value => 'created',
            self::PAID->value => 'paid',
            self::CANCELLED->value => 'cancelled',
            self::UNRESOLVED->value => 'unresolved',
            default => 'unknown',
        };
    }
}
