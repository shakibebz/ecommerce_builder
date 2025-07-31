<?php
namespace App\Http\Controllers\Api\V1\Tenant;

use App\Models\ShippingAddress;
use App\Services\MagentoSyncService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class StoreAddressController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'nullable|exists:tenant,id',
            'street' => 'required|string',
            'city' => 'required|string',
            'region' => 'required|string',
            'region_code' => 'nullable|string',
            'region_id' => 'nullable|string',
            'postcode' => 'required|string',
            'country_id' => 'required|string|size:2',
        ]);

        $address = ShippingAddress::create($validated);

        return response()->json([
            'message' => 'Shipping address stored.',
            'data' => $address
        ], 201);
    }

}
