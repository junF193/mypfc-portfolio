<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\Gender;
use App\Enums\ActivityLevel;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson(route('user.profile.update'), [
            'height' => 175.5,
            'weight' => 65.2,
            'age' => 28,
            'gender' => 'male',
            'activity_level' => 'high',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'プロフィールを更新しました']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'height' => 175.5,
            'weight' => 65.2,
            'age' => 28,
            'gender' => 'male',
            'activity_level' => 'high',
        ]);
    }

    public function test_update_profile_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson(route('user.profile.update'), [
            'height' => 'invalid',
            'gender' => 'unknown',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['height', 'gender']);
    }
}
