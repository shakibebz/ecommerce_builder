<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\MagentoService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MagentoSyncController extends Controller
{
    public function __construct(protected MagentoService $magentoService)
    {
    }

    public function __invoke(Product $product): JsonResponse
    {

        if ($product->status === ProductStatus::Synced) {
            return response()->json([
                'message' => 'Product is already synced.',
                'product' => $product,
            ], 409);
        }

        try {
            $this->magentoService->createOrUpdateProduct($product);

            $product->update([
                'status' => ProductStatus::Synced,
                'sync_error_message' => null,
            ]);

            return response()->json([
                'message' => 'Product synced to Magento successfully.',
                'product' => $product,
            ]);

        } catch (Exception $e) {
            Log::error('Magento Sync Failed for SKU: ' . $product->sku, [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);

            $product->update([
                'status' => ProductStatus::SyncFailed,
                'sync_error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to sync product to Magento.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }
}
