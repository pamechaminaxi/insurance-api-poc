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
        // return [
        //     //'customer_name' => 'required|string|max:255',
        //     'insurance_type' => 'required|in:health,life,motor',
        //     'premium_amount' => 'required|numeric|min:1',
        //     'coverage_amount' => 'required|numeric|min:1',
        //     'status' => 'sometimes|in:draft,submitted,approved,rejected',
        //     'customer_user_id' => [
        //         'required',
        //         'exists:users,id',
        //         function ($attribute, $value, $fail) {
        //             $user = User::find($value);
        //             if ($user && $user->role->name !== 'Customer') {
        //                 $fail('The selected customer user ID must belong to a user with the customer role.');
        //             }

        //             // NEW: Check active status
        //             if ($user && !$user->is_active) {
        //                 $fail('Cannot create quote because the customer account is inactive.');
        //             }
        //         },
        //     ],
        // ];

            $rules = [

                'insurance_type' => 'required|in:health,life,motor',

                'premium_amount' => 'required|numeric|min:1',

                'coverage_amount' => 'required|numeric|min:1',

                'customer_user_id' => [
                    'required',
                    'exists:users,id',

                    function ($attribute, $value, $fail) {

                        $user = User::find($value);

                        // must be customer
                        if ($user && $user->role->name !== 'Customer') {

                            $fail(
                                'The selected customer user ID must belong to a customer.'
                            );
                        }

                        // must be active
                        if ($user && !$user->is_active) {

                            $fail(
                                'Cannot create quote because customer account is inactive.'
                            );
                        }
                    },
                ],
            ];

            // If admin is creating quote
            if (auth()->user()->role->name === 'Admin') {

                $rules['agent_id'] = [

                    'required',
                    'exists:users,id',

                    function ($attribute, $value, $fail) {

                        $agent = User::find($value);

                        // must be agent
                        if ($agent && $agent->role->name !== 'Agent') {

                            $fail(
                                'Selected user must have Agent role.'
                            );
                        }

                        // must be active
                        if ($agent && !$agent->is_active) {

                            $fail(
                                'Cannot assign inactive agent.'
                            );
                        }
                    }
                ];
            }

        return $rules;
    }
}
