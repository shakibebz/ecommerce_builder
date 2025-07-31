<?php

namespace App\Jobs;

use App\Services\ProductIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessProductIngestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The array of product data from the crawler.
     *
     * @var array
     */
    protected $productsData;


    /**
     * Create a new job instance.
     *
     * @param array $productsData
     */
    public function __construct(array $productsData)
    {
        $this->productsData = $productsData;
    }

    /**
     * Execute the job.
     * The queue worker will call this method.
     *
     * @param ProductIngestionService $ingestionService
     * @return void
     */
    public function handle(ProductIngestionService $ingestionService): void
    {
        try {
            Log::info('Processing product ingestion job.', ['product_count' => count($this->productsData)]);

            // The heavy lifting happens here, in the background.
            $ingestionService->ingestProducts($this->productsData);

            Log::info('Product ingestion job completed successfully.');
        } catch (Throwable $e) {
            Log::error('Product ingestion job failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // The job will automatically be marked as failed by Laravel.
            $this->fail($e);
        }
    }
}
