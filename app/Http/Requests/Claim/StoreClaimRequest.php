<?php

namespace App\Http\Requests\Claim;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_id' => 'required|exists:quotes,id',
            'claim_amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'documents' => 'required|array',
            'documents.*' => 'file|extensions:jpg,jpeg,png,pdf|max:10240', // 10MB limit
        ];
    }
}
