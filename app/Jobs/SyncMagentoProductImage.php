<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\MagentoService; // We can even reuse the service

class SyncMagentoProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Allow for automatic retries if the job fails
    public int $tries = 3;
    public int $backoff = 60; // Wait 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $sku,
        public string $relativeImagePath,
        public string $productName,
        public int $imageIndex,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MagentoService $magentoService): void
    {
        try {

            $product = $magentoService->getProductBySku($this->sku);

            if (!$product) {
                Log::error("Product with SKU {$this->sku} does not exist in Magento. Skipping image upload.");
                return;
            }


            $imageContent = Storage::disk('public')->get($this->relativeImagePath);

            if (strlen($imageContent) > 10 * 1024 * 1024) {
                Log::error("Image for SKU {$this->sku}, Image Index: {$this->imageIndex} is too large. Skipping upload.");
                return;
            }

            Log::info('image Index:' . $this->imageIndex);

            $magentoService->uploadImageForProduct(
                $this->sku,
                $this->productName,
                $this->imageIndex,
                $this->relativeImagePath,
            );

            Log::info("Successfully processed image upload job for SKU {$this->sku}, image #{$this->imageIndex}.");
        } catch (\Exception $e) {
            Log::error("Image upload job failed for SKU {$this->sku}, image #{$this->imageIndex}. Error: {$e->getMessage()}");
            // Throw the exception again to make the job fail, so the queue can retry it.
            $this->fail($e);
        }
    }
}
