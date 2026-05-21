<?php

namespace App\Http\Requests\Quote;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => 'sometimes|string|max:255',
            'insurance_type' => 'sometimes|in:health,life,motor',
            'premium_amount' => 'sometimes|numeric|min:1',
            'coverage_amount' => 'sometimes|numeric|min:1',
            'status' => 'sometimes|in:draft,submitted,approved,rejected',
            'customer_user_id' => [
                'sometimes',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if ($user && $user->role->name !== 'Customer') {
                        $fail('The selected customer user ID must belong to a user with the customer role.');
                    }
                },
            ],
        ];
    }
}
