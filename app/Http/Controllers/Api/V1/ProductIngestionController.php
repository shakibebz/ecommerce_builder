<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductIngestionRequest;
use App\Services\ProductIngestionService;
use App\Jobs\ProcessProductIngestion;

class ProductIngestionController extends Controller
{
    protected $productIngestionService;

    public function __construct(ProductIngestionService $productIngestionService)
    {
        $this->productIngestionService = $productIngestionService;
    }

    public function store(StoreProductIngestionRequest $request)
    {
        try {
            $productsData = $request->validated()['products'];

            // Delegate the product ingestion to the service
            //$ingestedCount = $this->productIngestionService->ingestProducts($productsData);

            ProcessProductIngestion::dispatch($productsData)->onQueue('product-ingestion');

            return response()->json([
                'message' => "Successfully ingested products."
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while ingesting products.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
