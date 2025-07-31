<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SyncStoreDataToMagento;
use App\Models\Stores;
use App\Services\MagentoApiService;
use App\Services\Tenant\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StoresController extends Controller
{

    protected MagentoApiService $magentoApi;
    public $token = null;


    public function __construct(MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }


    public function create(Request $request)
    {
        $owner_id = Auth::id();
        $store = StoreService::createStore($request, $owner_id);

        if ($store) {
            return response()->json([
                'status' => 'success',
                'message' => 'Store created successfully',
                'store' => $store
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create store.'
            ]);
        }

    }
    /*    public function create(Request $request)
        {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'domain' => 'required|url',
                'store_category' => 'required|string|max:255',
                'registration_date' => 'required|date',
                'expiration_date' => 'required|date',
                'is_active' => 'required|boolean',
                'code' => 'required|string|max:10',
            ]);


            $data = new Stores();

            $registration_date = Carbon::now()->format('Y-m-d');
            $expiration_date = Carbon::today()->addYear()->format('Y-m-d');

            $data->name = $validated['name'];
            $data->code = $validated['code'];
            $data->owner_id = Auth::id();
            $data->domain = $validated['domain'];
            $data->store_category = $validated['store_category'];
            $data->registration_date = $registration_date;
            $data->expiration_date = $expiration_date;
            $data->is_active = $validated['is_active'];
            //   $data->sort_order = isset($validated['sort_order']) ? $validated['sort_order'] : 0;


            //  $data->website_code = $validated['website_code'];

            \Log::info('Validated Data:', $validated);

            $data->save();
            \Log::info('Website Saved.');

            SyncStoreDataToMagento::dispatch($data->name, $data->code);


            return response()->json([
                'status' => 'success',
                'message' => 'Website created successfully.',
                //  'data' => $data,
            ], 201);
        }*/
}
