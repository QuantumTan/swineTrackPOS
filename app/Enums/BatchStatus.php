<?php

namespace App\Enums;

enum BatchStatus: string
{
    case Open = 'Open';
    case SoldOut = 'Sold Out';
    case Closed = 'Closed';

    public function pillClass(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::SoldOut => 'success',
            self::Closed => 'neutral',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }

    /**
     * @return list<string>
     */
    public static function manualValues(): array
    {
        return [
            self::Open->value,
            self::Closed->value,
        ];
    }
}
