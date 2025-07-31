<?php

namespace App\Services\AdminPanel;

use App\Data\ProductImportData;
use App\Enums\ProductStatus;
use App\Models\AttributeMapping;
use App\Models\CategoryMapping;
use App\Models\Product;

/**
 * Handles all business logic related to importing products.
 * This class is decoupled from the data source (e.g., Excel, CSV, API).
 */
class ProductImportService
{
    /**
     * Processes a single product data object to create or update a product.
     *
     * @param ProductImportData $productData The clean, structured data for one product.
     * @return Product The created or updated product model, ready to be saved.
     */
    public function createOrUpdateProduct(ProductImportData $productData): Product
    {
        // Handle the creation of mapping records first
        $this->handleCategoryMapping($productData->category);
        $this->handleAttributeMapping($productData->attributes);

        // Find existing product by SKU or create a new model instance in memory.
        $product = Product::firstOrNew(['sku' => $productData->sku]);

        // Prepare data for filling the model
        $data = [
            'name'           => $productData->name,
            'description'    => $productData->description,
            'price'          => $productData->price,
            'stock_quantity' => $productData->stock_quantity,
            'category'       => $productData->category,
            'brand'          => $productData->brand,
            'source_url'     => $productData->source_url,
            'attributes'     => $productData->attributes,
            'images'         => $productData->images,
        ];

        // Mass-assign the data to the model's attributes.
        $product->fill($data);

        // IMPORTANT: Only set the status to 'PendingReview' if it's a brand new product.
        // This prevents overwriting the status of existing products during an update.
        if (! $product->exists) {
            $product->status = ProductStatus::PendingReview;
        }

        // The model is returned without being saved.
        // The `ToModel` concern will handle calling `save()` automatically.
        return $product;
    }

    /**
     * Creates a CategoryMapping record if it doesn't exist.
     */
    private function handleCategoryMapping(?string $categoryName): void
    {
        if (empty($categoryName)) {
            return;
        }

        CategoryMapping::firstOrCreate(
            ['source_name' => trim($categoryName)],
            ['is_mapped' => false]
        );
    }

    /**
     * Creates AttributeMapping records for any new attributes.
     */
    private function handleAttributeMapping(array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        foreach (array_keys($attributes) as $label) {
            $trimmedLabel = trim($label);
            if (!empty($trimmedLabel)) {
                AttributeMapping::firstOrCreate(
                    ['source_label' => $trimmedLabel],
                    ['is_mapped' => false]
                );
            }
        }
    }
}
