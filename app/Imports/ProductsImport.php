<?php

namespace App\Imports;

use App\Data\ProductImportData;

use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Services\AdminPanel\ProductImportService;

/**
 * This class's ONLY responsibilities are to read the Excel file and
 * delegate the processing of each row to the ProductImportService.
 * It also handles queuing for better performance and user experience.
 */
class ProductsImport implements ToModel, WithHeadingRow, WithChunkReading, ShouldQueue
{
    /**
     * The service is injected via the constructor, following dependency injection principles.
     */
    public function __construct(private ProductImportService $productImportService)
    {
    }

    /**
     * Process a single row from the spreadsheet.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Basic validation: skip row if our unique identifier (SKU) is missing.
        if (!isset($row['sku']) || empty($row['sku'])) {
            return null;
        }

        // 1. Create a structured DTO from the raw array data.
        $productData = ProductImportData::fromExcelRow($row);

        // 2. Pass the clean DTO to the service to handle all business logic.
        return $this->productImportService->createOrUpdateProduct($productData);
    }

    /**
     * Defines how many rows should be processed at a time when using ShouldQueue.
     * This prevents memory issues with very large files.
     */
    public function chunkSize(): int
    {
        return 200; // Adjust this number based on your server resources.
    }
}
