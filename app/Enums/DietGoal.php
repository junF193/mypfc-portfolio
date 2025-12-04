<?php

namespace App\Enums;

enum DietGoal: string
{
    case Lose = 'lose';
    case Maintain = 'maintain';
    case Gain = 'gain';

    public function label(): string
    {
        return match($this) {
            self::Lose => '減量',
            self::Maintain => '現状維持',
            self::Gain => '増量',
        };
    }
}
