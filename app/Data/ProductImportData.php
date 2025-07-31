<?php

namespace App\Data;

use Illuminate\Support\Arr;

/**
 * A Data Transfer Object to represent a single, cleaned row from the product import file.
 * It's immutable (readonly) to prevent accidental changes after creation.
 */
final class ProductImportData
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
        public readonly int $stock_quantity,
        public readonly ?string $category,
        public readonly ?string $brand,
        public readonly ?string $source_url,
        public readonly array $attributes,
        public readonly array $images
    ) {
    }

    /**
     * Factory method to create a DTO from a raw Excel row array.
     * This encapsulates all the messy parsing and cleaning logic in one place.
     *
     * @param array $row The raw data from a spreadsheet row.
     * @return self
     */
    public static function fromExcelRow(array $row): self
    {
        // --- Parse Attributes ---
        $attributesString = Arr::get($row, 'attributes');
        $attributesArray = [];
        if (!empty($attributesString)) {
            // Be robust against malformed JSON from Excel
            $jsonString = str_replace("'", '"', $attributesString);
            $decoded = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attributesArray = $decoded;
            }
        }

        // --- Parse Images ---
        $imagesString = Arr::get($row, 'images');
        $imagesArray = [];
        if (!empty($imagesString)) {
            $decoded = json_decode($imagesString, true);
            // The `?? []` is a concise way to default to an empty array if decoding fails or returns null
            $imagesArray = $decoded ?? [];
        }

        return new self(
            sku:            Arr::get($row, 'sku'),
            name:           Arr::get($row, 'name'),
            description:    Arr::get($row, 'description'),
            price:          (float) Arr::get($row, 'price', 0),
            stock_quantity: (int) Arr::get($row, 'stock_quantity', 0),
            category:       Arr::get($row, 'category'),
            brand:          Arr::get($row, 'brand'),
            source_url:     Arr::get($row, 'source_url'),
            attributes:     $attributesArray,
            images:         $imagesArray
        );
    }
}
