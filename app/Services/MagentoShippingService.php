<?php
namespace App\Services;

use App\Models\ShippingAddress;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MagentoShippingService
{

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function getShippingMethods($cartId, ShippingAddress $address, MagentoApiService $magentoApi)
    {


        $token= $magentoApi->getAccessToken();
        \Log::debug('Token:', ['token' => $token]);

        $payload = [
            'address' => [
                'country_id' => $address->country_id,
                'postcode' => $address->postcode,
                'region' => $address->region,
                'region_code' => $address->region_code,
                'region_id' => $address->region_id,
                'city' => $address->city,
                'street' =>  is_array($address->street)
                    ? $address->street
                    : [$address->street],
            ]
        ];

        \Log::debug('Magento shipping payload:', $payload);


        $url = "{$magentoApi->apiUrl}/rest/V1/carts/{$cartId}/estimate-shipping-methods";
        \Log::debug('Token:', ['url' => $url]);
        $response = Http::withToken($token)->post($url, $payload);
        \Log::debug('Magento shipping response:', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);
        return $response->json();
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function setShippingMethod($cartId, ShippingAddress $address, $carrierCode, $methodCode , MagentoApiService $magentoApi)
    {

        $token= $magentoApi->getAccessToken();
        \Log::debug('Token:', ['token' => $token]);

        $payload = [
            'addressInformation' => [
                'shipping_address' => [
                    'country_id' => $address->country_id,
                    'postcode' => $address->postcode,
                    'region' => $address->region,
                    'region_code' => $address->region_code,
                    'region_id' => $address->region_id,
                    'city' => $address->city,
                    'street' => is_array($address->street) ? $address->street : [$address->street],
                ],
                'shipping_method_code' => $methodCode,
                'shipping_carrier_code' => $carrierCode,
            ]
        ];

        \Log::debug('Magento set shipping method payload:', $payload);

        $url = "{$magentoApi->apiUrl}/rest/V1/carts/{$cartId}/shipping-information";
        $response = Http::withToken($token)->post($url, $payload);

        \Log::debug('Magento set shipping method response:', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        return $response->json();
    }

}
