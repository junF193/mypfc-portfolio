<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TDEEService;
use App\Enums\DietGoal;

class TDEEServiceTest extends TestCase
{
    private TDEEService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TDEEService();
    }

    public function test_calculate_target_calories_lose()
    {
        // TDEE = 2000
        // Lose = -500 -> 1500
        $result = $this->service->calculateTargetCalories(2000, DietGoal::Lose);
        $this->assertEquals(1500, $result);
    }

    public function test_calculate_target_calories_maintain()
    {
        // TDEE = 2000
        // Maintain = 0 -> 2000
        $result = $this->service->calculateTargetCalories(2000, DietGoal::Maintain);
        $this->assertEquals(2000, $result);
    }

    public function test_calculate_target_calories_gain()
    {
        // TDEE = 2000
        // Gain = +300 -> 2300
        $result = $this->service->calculateTargetCalories(2000, DietGoal::Gain);
        $this->assertEquals(2300, $result);
    }

    public function test_calculate_target_calories_safety_guard()
    {
        // TDEE = 1500
        // Lose = -500 -> 1000
        // Safety Guard -> 1200
        $result = $this->service->calculateTargetCalories(1500, DietGoal::Lose);
        $this->assertEquals(1200, $result);
    }

    public function test_calculate_target_pfc_rounding()
    {
        // Target = 2000
        // P: 15% = 300kcal = 75g
        // F: 25% = 500kcal = 55.55...g -> 55.6
        // C: 60% = 1200kcal = 300g
        
        $result = $this->service->calculateTargetPFC(2000);
        
        $this->assertEquals(75.0, $result['protein']);
        $this->assertEquals(55.6, $result['fat']);
        $this->assertEquals(300.0, $result['carbs']);
    }
}
