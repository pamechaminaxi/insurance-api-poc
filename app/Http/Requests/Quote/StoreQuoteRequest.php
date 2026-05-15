<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'insurance_type' => 'required|in:health,life,motor',
            'premium_amount' => 'required|numeric|min:0',
            'status' => 'sometimes|in:draft,submitted,approved,rejected',
            'customer_user_id' => [
                'required',
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
