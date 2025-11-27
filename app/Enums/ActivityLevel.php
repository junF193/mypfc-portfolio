<?php

namespace App\Enums;

enum ActivityLevel: string
{
    case Low = 'low';       // 1.2
    case Medium = 'medium'; // 1.55
    case High = 'high';     // 1.9

    public function multiplier(): float
    {
        return match($this) {
            self::Low => 1.2,
            self::Medium => 1.55,
            self::High => 1.9,
        };
    }
}
