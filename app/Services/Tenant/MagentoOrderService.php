<?php

namespace App\Services\Tenant;

use App\Services\MagentoApiService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class MagentoOrderService
{

    protected  MagentoApiService $magentoApi;

    public function __construct(MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }


    /**
     * Fetches all orders for a specific store view.
     * Note: Magento's Order API filters by 'store_id', not 'website_id'.
     * A website can contain multiple stores.
     *
     * @param int $storeId The ID of the store view to fetch orders from.
     * @return array A list of orders.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrdersForStore(int $storeId): array
    {
        $token =  $this->magentoApi->getAccessToken();

        // Magento uses searchCriteria for filtering lists.
        $searchCriteria = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'store_id',
            'searchCriteria[filter_groups][0][filters][0][value]' => $storeId,
            'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
        ];

        // The search criteria are added as query parameters to the endpoint URL.
        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/orders?" . http_build_query($searchCriteria);

        try {
            $response =  $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody(), true);

            Log::info('oreder data', [$responseData]);

            // The actual order list is inside the 'items' key of the response.
            return $responseData;
        } catch (\Exception $e) {
            Log::error('Failed to fetch orders from Magento for store ID: ' . $storeId, [
                'error' => $e->getMessage()
            ]);
            // Re-throw the exception to be handled by the controller.
            throw $e;
        }
    }

    /**
     * Fetches a single order by its entity ID.
     * Note: To fetch a single order, you need its unique ID (entity_id), not the website or store ID.
     *
     * @param int $orderId The entity_id of the order.
     * @return array The order data, or an empty array if not found.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrderById(int $orderId): array
    {
        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/orders/{$orderId}";

        try {
            $response =  $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            // A 404 Not Found response is expected if the order ID does not exist.
            // We return an empty array to match the specified return type.
            if ($e->getResponse()->getStatusCode() === 404) {
                Log::info('Order not found in Magento with ID: ' . $orderId);
                return [];
            }

            // For any other client error (e.g., 401 Unauthorized), log it and re-throw.
            Log::error('Magento API client error while fetching order ID: ' . $orderId, [
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            // For any other exception (e.g., server error, network issue), log and re-throw.
            Log::error('Failed to fetch order from Magento for ID: ' . $orderId, [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    public function getOrderForStore(int $websitId): array
    {
        // Logic to interact with Magento API and fetch an order for the given store ID.
        return []; // Placeholder return value
    }


    public function getStoreIdsByWebsiteId(int $websiteId): array
    {
        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/store/storeViews";

        try {
            $response =  $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $stores = json_decode($response->getBody(), true);

            // Filter store views by website_id
            $storeIds = collect($stores)
                ->filter(fn($store) => $store['website_id'] == $websiteId)
                ->pluck('id')
                ->toArray();

            return $storeIds;
        } catch (\Exception $e) {
            Log::error('Failed to fetch store views from Magento', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
