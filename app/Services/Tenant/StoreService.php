<?php
namespace App\Services\Tenant;

use App\Models\Stores;
use App\Services\MagentoSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Jobs\SyncStoreDataToMagento;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreService
{
    public static function createStore($data, $owner_id): array
    {


        try {
            $validated = $data->validate([
                'name' => 'required|string|max:255',
                'domain' => 'required',
          //      'store_category' => 'required|string|max:255',
              //  'registration_date' => 'required|date',
              //  'expiration_date' => 'required|date',
           //     'is_active' => 'required|boolean',
                'code' => 'required|string|max:10',
            ]);
        } catch (ValidationException $e) {
            throw $e; // Optional: let Laravel handle the rest
        }
        $code= $validated['code'];

        $locale = strtolower(trim($code));

        do {
            $randomPrefix = Str::lower(Str::random(4));

            $code_final = "{$randomPrefix}_{$locale}";
            \Log::debug('final code'. $code_final);
        } while (DB::table('stores')->where('code', $code_final)->exists());


        $name= $validated['name'];
        SyncStoreDataToMagento::dispatch($name, $code_final);


        $store = new Stores();

        $store->name = $validated['name'];
        $store->code = $code_final;
        $store->owner_id = $owner_id;
        $store->domain = $validated['domain'];
       // $store->store_category = 'fashion';
        $store->registration_date = Carbon::now()->format('Y-m-d');
        $store->expiration_date = Carbon::today()->addYear()->format('Y-m-d');
        $store->is_active = $validated['is_active'] ?? 1;

        $store->save();

        // Dispatch job
      $syncData= SyncStoreDataToMagento::dispatch($store->name, $store->code);

        if ($syncData)
        {
            return [
                'store' => $store,
                'code'  => $code_final,
            ];
        }
        else
        {
                    return response()->json([
            'status' => 'error',
            'message' => 'store doesnt sync in magento',
        ]);
        }
    }



}
