<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreFavoriteFoodRequest extends FormRequest
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
   public function rules() {
        return [
            'food_log_id' => [
                'required', 'integer', 'exists:food_logs,id',
                Rule::unique('favorite_foods')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
        ];
    }

    public function messages() {
        return ['food_log_id.unique' => '既にお気に入りに登録されています'];
    }
}
