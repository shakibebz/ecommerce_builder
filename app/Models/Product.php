<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'status',
        'attributes',
        'category',
        'images',
        'source_url',
        'brand'
    ];

    protected $casts = [
        'attributes' => 'array',
        'images' => 'array',
        'status' => ProductStatus::class,
        'stock_quantity' => 'integer',
        'price' => 'integer',
    ];
}
