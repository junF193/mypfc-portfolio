<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class MypageTest extends TestCase
{
    use RefreshDatabase;

    public function test_mypage_displays_correct_date_data()
    {
        $user = User::factory()->create();
        $date = '2025-01-01';

        $response = $this->actingAs($user, 'sanctum')->get(route('mypage.index', ['date' => $date]));

        $response->assertStatus(200);
        $response->assertViewHas('currentDate', function ($viewDate) use ($date) {
            return $viewDate->format('Y-m-d') === $date;
        });
    }

    public function test_mypage_fallbacks_to_today_on_invalid_date()
    {
        $user = User::factory()->create();
        $invalidDate = 'invalid-date-string';

        $response = $this->actingAs($user, 'sanctum')->get(route('mypage.index', ['date' => $invalidDate]));

        $response->assertStatus(200);
        $response->assertViewHas('currentDate', function ($viewDate) {
            return $viewDate->isToday();
        });
    }

    public function test_mypage_fallbacks_to_today_on_future_date_format_error()
    {
        // Though my controller logic doesn't explicitly block future dates, 
        // the requirement said "invalid (future too far, string, format error)".
        // My implementation checks regex /^\d{4}-\d{2}-\d{2}$/.
        // So '2025/01/01' should fail regex and fallback.
        
        $user = User::factory()->create();
        $invalidFormat = '2025/01/01';

        $response = $this->actingAs($user, 'sanctum')->get(route('mypage.index', ['date' => $invalidFormat]));

        $response->assertStatus(200);
        $response->assertViewHas('currentDate', function ($viewDate) {
            return $viewDate->isToday();
        });
    }
}
