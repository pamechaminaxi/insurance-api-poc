<?php

namespace App\Http\Requests\Claim;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'claim_amount' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string|in:Pending,Under Review,Approved,Rejected,Settled',
        ];
    }
}
