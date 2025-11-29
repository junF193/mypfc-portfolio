<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFromHistoryFoodRequest extends FormRequest
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
            'from_history_id' => ['nullable', 'integer', 'exists:food_logs,id'],
            'meal_type' => ['required', 'string', Rule::in(['breakfast', 'lunch', 'dinner', 'snack'])],
            'multiplier' => ['required', 'numeric', 'min:0.01', 'max:999.999'],
            'percent' => ['nullable', 'numeric', 'min:1', 'max:9999'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $multiplier = 1.0;

        if ($this->filled('percent')) {
            $multiplier = (float)$this->input('percent') / 100.0;
        } elseif ($this->filled('multiplier')) {
            $multiplier = (float)$this->input('multiplier');
        }

        $this->merge([
            'multiplier' => round($multiplier, 3),
        ]);
    }
}
