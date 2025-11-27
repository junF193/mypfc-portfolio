<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Gender;
use App\Enums\ActivityLevel;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'height' => ['nullable', 'numeric', 'min:50', 'max:300'], // cm
            'weight' => ['nullable', 'numeric', 'min:20', 'max:500'], // kg
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'gender' => ['nullable', new Enum(Gender::class)],
            'activity_level' => ['nullable', new Enum(ActivityLevel::class)],
        ];
    }
}
