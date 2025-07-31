<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Enums\ProductStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;

class AdminProductController extends Controller
{
    // List products, with status filtering
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return ProductResource::collection($query->latest()->paginate(20));
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'status'      => [
            'sometimes',
            'required',
            Rule::in([
                ProductStatus::Approved,
                ProductStatus::Rejected,
                ProductStatus::PendingReview
            ])
        ],
        ]);

        $product->update($validated);

        return new ProductResource($product);
    }
}
