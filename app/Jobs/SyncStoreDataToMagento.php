<?php

namespace App\Jobs;

use App\Services\MagentoApiService;
use App\Services\MagentoSyncService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStoreDataToMagento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $name;
    protected $code;

    public function __construct($name, $code)
    {
        $this->name = $name;
        $this->code = $code;
    }


    public function handle(MagentoSyncService $magentoSyncService, MagentoApiService $magentoApi)
    {
        try {
            \Log::debug('code job'. $this->code);
            $magentoSyncService->syncDataToMagento($this->name, $this->code, $magentoApi);

            \Log::info('Data synced successfully to Magento for store.' , [
            'name' => $this->name,
            'code' => $this->code,
            ]);;
        } catch (\Exception $e) {

            \Log::error('Error syncing store data to Magento.', ['error' => $e->getMessage()]);
        }
    }
}
