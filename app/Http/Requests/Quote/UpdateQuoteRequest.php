<?php

namespace App\Http\Requests\Quote;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

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
            'insurance_type' => 'sometimes|in:health,life,motor',

            'premium_amount' => 'sometimes|numeric|min:1',

            'coverage_amount' => 'sometimes|numeric|min:1',

            'status' => 'sometimes|in:draft,submitted,approved,rejected',

            /*
            |--------------------------------------------------------------------------
            | Customer Validation
            |--------------------------------------------------------------------------
            */

            'customer_user_id' => [

                'sometimes',
                'exists:users,id',

                function ($attribute, $value, $fail) {

                    $user = User::find($value);

                    // Must be customer
                    if (
                        $user
                        &&
                        $user->role->name !== 'Customer'
                    ) {

                        $fail(
                            'The selected customer must have Customer role.'
                        );
                    }

                    // Customer must be active
                    if (
                        $user
                        &&
                        !$user->is_active
                    ) {

                        $fail(
                            'Cannot assign quote to inactive customer.'
                        );
                    }
                },
            ],

            /*
            |--------------------------------------------------------------------------
            | Agent Validation
            |--------------------------------------------------------------------------
            */

            'agent_id' => [

                'sometimes',
                'exists:users,id',

                function ($attribute, $value, $fail) {

                    $agent = User::find($value);

                    // Must be agent
                    if (
                        $agent
                        &&
                        $agent->role->name !== 'Agent'
                    ) {

                        $fail(
                            'The selected user must have Agent role.'
                        );
                    }

                    // Agent must be active
                    if (
                        $agent
                        &&
                        !$agent->is_active
                    ) {

                        $fail(
                            'Cannot assign inactive agent.'
                        );
                    }
                },
            ],
        ];
    }
}

