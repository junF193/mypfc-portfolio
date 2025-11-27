<?php

namespace App\Services;

use App\Models\User;
use App\Enums\Gender;
use App\Enums\ActivityLevel;

class TDEEService
{
    /**
     * Calculate Basal Metabolic Rate (BMR) using Mifflin-St Jeor Equation.
     */
    public function calculateBMR(User $user): int
    {
        if (!$user->height || !$user->weight || !$user->age || !$user->gender) {
            return 0;
        }

        // Mifflin-St Jeor Equation
        // P = 10W + 6.25H - 5A + S
        // S is +5 for males and -161 for females
        
        $weight = (float) $user->weight;
        $height = (float) $user->height;
        $age = (int) $user->age;

        $base = (10 * $weight) + (6.25 * $height) - (5 * $age);

        $s = match ($user->gender) {
            Gender::Male => 5,
            Gender::Female => -161,
            default => 0, // Should not happen if validated
        };

        return (int) round($base + $s);
    }

    /**
     * Calculate Total Daily Energy Expenditure (TDEE).
     */
    public function calculateTDEE(User $user): int
    {
        $bmr = $this->calculateBMR($user);
        if ($bmr === 0) {
            return 0;
        }

        $activityLevel = $user->activity_level ?? ActivityLevel::Medium;
        // Ensure it's an Enum instance (in case it wasn't cast for some reason, though model casts should handle it)
        if (is_string($activityLevel)) {
            $activityLevel = ActivityLevel::tryFrom($activityLevel) ?? ActivityLevel::Medium;
        }

        return (int) round($bmr * $activityLevel->multiplier());
    }

    /**
     * Calculate Target PFC (Protein, Fat, Carbs) in grams.
     * Default: P=15%, F=25%, C=60%
     */
    public function calculateTargetPFC(int $tdee): array
    {
        if ($tdee === 0) {
            return [
                'protein' => 0,
                'fat' => 0,
                'carbs' => 0,
            ];
        }

        // P: 15% (4kcal/g)
        $proteinKcal = $tdee * 0.15;
        $proteinG = $proteinKcal / 4;

        // F: 25% (9kcal/g)
        $fatKcal = $tdee * 0.25;
        $fatG = $fatKcal / 9;

        // C: 60% (4kcal/g)
        $carbsKcal = $tdee * 0.60;
        $carbsG = $carbsKcal / 4;

        return [
            'protein' => (int) round($proteinG),
            'fat' => (int) round($fatG),
            'carbs' => (int) round($carbsG),
        ];
    }
}
