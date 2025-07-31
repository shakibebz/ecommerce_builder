<?php
namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use App\Services\MagentoApiService;
use App\Services\MagentoShippingService;
use Illuminate\Http\Request;

class EstimateShippingController extends Controller
{
    protected $magento;
    protected $magentoApi;

    public function __construct(MagentoShippingService $magento, MagentoApiService $magentoApi)
    {
        $this->magento = $magento;
        $this->magentoApi = $magentoApi;
    }

    public function estimate(Request $request, $cartId)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
        ]);

        $address = ShippingAddress::findOrFail($request->shipping_address_id);
        \Log::info('Shipping address retrieved:', $address->toArray());

        $methods = $this->magento->getShippingMethods($cartId, $address, $this->magentoApi);
        \Log::info('Magento shipping methods response:', $methods);
        \Log::debug('Cart ID:', ['cart_id' => $cartId]);
        \Log::debug('Address:', $address->toArray());

        return response()->json($methods);
    }

    public function setShipping(Request $request, $cartId)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'carrier_code' => 'required|string',
            'method_code' => 'required|string',
        ]);

        $address = ShippingAddress::findOrFail($request->shipping_address_id);
        $response = $this->magento->setShippingMethod(
            $cartId,
            $address,
            $request->carrier_code,
            $request->method_code,
            $this->magentoApi
        );

        return response()->json($response);
    }
}
