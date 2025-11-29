<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualFoodRequest extends FormRequest
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
            'food_name' => 'required|string|max:255',
            'energy_kcal_100g' => ['nullable', 'numeric', 'max:999999.99'],
            'proteins_100g' => ['nullable', 'numeric', 'max:999999.99'],
            'fat_100g' => ['nullable', 'numeric', 'max:999999.99'],
            'carbohydrates_100g' => ['nullable', 'numeric', 'max:999999.99'],
            'meal_type' => ['required', 'string', Rule::in(['breakfast', 'lunch', 'dinner', 'snack'])],
            'multiplier' => ['nullable', 'numeric', 'min:0.01', 'max:999.999'],
            'consumed_at' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
