<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductIngestionRequest extends FormRequest
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
            'products'               => ['required', 'array', 'min:1'],
            'products.*.sku'         => ['required', 'string', 'distinct', 'unique:products,sku'],
            'products.*.name'        => ['required', 'string', 'max:255'],
            'products.*.price'       => ['required', 'numeric', 'min:0'],
            'products.*.description' => ['nullable', 'string'],
            'products.*.status'      => ['nullable', 'string', 'in:PendingReview,Active,Inactive'],
            'products.*.images'      => ['nullable', 'array'],
            'products.*.attributes'  => ['nullable', 'array'],
            'products.*.images.*'    => ['nullable', 'url'],
            'products.*.category'    => ['nullable', 'string', 'max:255'],
            'products.*.brand'       => ['nullable', 'string', 'max:255'],
            'products.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'products.*.source_url'  => ['nullable', 'string', 'url'],
            'products.*.crawler_payload' => ['nullable', 'array'],
        ];
    }
}
