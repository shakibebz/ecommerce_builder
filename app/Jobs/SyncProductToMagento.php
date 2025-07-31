<?php

namespace App\Jobs;

use App\Models\Product;
use App\Enums\ProductStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncProductToMagento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Product $product)
    {
    }

    public function handle(): void
    {
        $magentoUrl = config('services.magento.url');
        $apiToken = config('services.magento.token');

        if (!$magentoUrl || !$apiToken) {
            Log::error('Magento credentials not configured.');
            $this->product->update(['status' => 'sync_failed']);
            return;
        }

        try {
            $response = Http::withToken($apiToken)
                ->post("{$magentoUrl}/rest/V1/products", [
                    'product' => [
                        'sku' => $this->product->sku,
                        'name' => $this->product->name,
                        'price' => $this->product->price,
                        'status' => 1,
                        'visibility' => 4,
                        'type_id' => 'simple',
                        'attribute_set_id' => 4, // Default Attribute Set
                        'extension_attributes' => [
                            'stock_item' => [ 'qty' => 100, 'is_in_stock' => true ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $magentoProduct = $response->json();
                $this->product->update([
                    'status' => ProductStatus::Synced,
                    'magento_product_id' => $magentoProduct['id']
                ]);
            } else {
                $this->product->update(['status' => ProductStatus::SyncFailed]);
                Log::error('Magento Sync Failed for SKU: ' . $this->product->sku, [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $this->product->update(['status' => ProductStatus::SyncFailed]);
            Log::critical('Magento Sync Exception for SKU: ' . $this->product->sku, [
                'message' => $e->getMessage()
            ]);
            $this->fail($e); // Mark job as failed
        }
    }
}
