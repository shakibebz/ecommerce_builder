<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MagentoSyncService
{
    public  $client;
    public  $apiUrl;
    public  $username;
    public  $password;
    protected  $magentoApi;
    public  $token = null;


    public function __construct(MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }


    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function syncDataToMagento($name, $code, MagentoApiService $magentoApi)
    {
        \Log::debug('sync step 1');
        $token = $magentoApi->getAccessToken();
        \Log::debug('sync code'. $code);

        $websitePayload = [
            'website' => [
                'code' => $code,
                'name' => $name,
                'sort_order' => 0,
                'default_group_id' => 0,
                'is_default' => false,
            ],
        ];
        \Log::debug('sync step 2');
        try {
            Log::info('Attempting to create Magento website', ['code' => $code]);
            $response = $magentoApi->client->post("{$magentoApi->apiUrl}/rest/V1/store/websites", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $websitePayload,
            ]);
            Log::info('Magento website creation response received.');
            $responseData = json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            $body = (string) $e->getResponse()->getBody();

            // If website already exists, log it and continue
            if (str_contains($body, 'Website with the same code already exists')) {
                Log::warning("Magento website already exists with code '{$code}'. Will proceed to fetch its ID.");
            } else {
                Log::error('Error creating Magento website.', ['error' => $e->getMessage(), 'body' => $body]);
                throw $e;
            }
        }

        // Fetch all websites to get the website ID
        try {
            Log::info('Fetching all websites to find created website ID');
            $getWebsitesResponse = $magentoApi->client->get("{$magentoApi->apiUrl}/rest/V1/store/websites", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $websites = json_decode($getWebsitesResponse->getBody(), true);
            Log::info('Fetched websites list from Magento', ['websites' => $websites]);

            $matchedWebsite = collect($websites)->firstWhere('code', $code);
            if (!$matchedWebsite) {
                throw new Exception("Website with code '{$code}' not found after creation attempt.");
            }
            $websiteId = $matchedWebsite['id'];
            Log::info('Website ID found: ' . $websiteId);
        } catch (Exception $e) {
            Log::error('Failed to fetch website ID from Magento.', ['error' => $e->getMessage()]);
            throw $e;
        }

/*        $uniqueCode = strtolower(
                preg_replace('/[^a-z0-9_]/', '_', $name)
            ) . '_' . uniqid();
        $storeGroupName = 'StoreGroup_' . $uniqueCode;*/


        // Create store group
        $storeGroup = $this->createStoreGroup($this->magentoApi, $websiteId, $name, $code);
        $storeGroupId = $storeGroup['id'];
        Log::info('Store Group created with ID: ' . $storeGroupId);


        $storeView = $this->createStoreView($this->magentoApi, $websiteId, $storeGroupId, $code, $name);
        Log::info('Store View created.', ['storeView' => $storeView]);

        return [
            'website' => $matchedWebsite,
            'store_group' => $storeGroup,
            'store_view' => $storeView,
        ];
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function createStoreGroup(MagentoApiService $magentoApi, $websiteId, $name , $code_final, $rootCategoryId = 2)
    {
        $token = $magentoApi->getAccessToken();

        // Generate a unique, lowercase, underscore-safe code from the name (or you can pass it explicitly)
        //$code = strtolower(str_replace(' ', '_', $name));

        $payload = [
            'storeGroup' => [
                'name' => $name,
                'code' => $code_final,
                'website_id' => $websiteId,
                'root_category_id' => $rootCategoryId,
                'default_store_id' => 1,
            ],
        ];

        try {
            Log::info('Sending store group payload to Magento', ['payload' => $payload]);

            $response = $magentoApi->client->post("{$magentoApi->apiUrl}/rest/V1/store/storeGroups", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody(), true);
            Log::info('Magento store group created successfully.', ['response' => $responseData]);

            return $responseData;
        } catch (Exception $e) {
            Log::error('Error creating store group in Magento.', ['error' => $e->getMessage()]);
            throw new Exception('Error creating store group: ' . $e->getMessage());
        }
    }


    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function createStoreView(MagentoApiService $magentoApi, $websiteId, $groupId, $code_final, $name, $sortOrder = 0, $storeId=null )
    {
        $token = $magentoApi->getAccessToken();

        $payload = [
            'store' => [
                'code' => $code_final,
                'name' => $name,
                'website_id' => $websiteId,
                'group_id' => $groupId,
                'is_active' => 1,
                'sort_order' => $sortOrder,
            ],
        ];

        try {
            $response = $magentoApi->client->post("{$magentoApi->apiUrl}/rest/V1/store/storeViews", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseBody = (string) $response->getBody();
            $responseData = json_decode($responseBody, true);

            if (!is_array($responseData)) {
                Log::error('Invalid store view response JSON.', ['response' => $responseBody]);
                throw new Exception('Invalid response received from Magento store view API.');
            }

            Log::info('Magento store view created successfully.', ['response' => $responseData]);

            if (isset($responseData['code'])) {
                \App\Models\Stores::where('id', $storeId)->update([
                    'code' => $responseData['code']
                ]);
                Log::info("Store model updated with Magento store view code: {$responseData['code']}");
            }

            return $responseData;


        } catch (ClientException $e) {
            $body = (string) $e->getResponse()->getBody();
            Log::error('Magento store view API returned client error.', ['body' => $body]);
            throw new Exception('Client error when creating store view: ' . $body);
        } catch (Exception $e) {
            Log::error('Error creating store view in Magento.', ['error' => $e->getMessage()]);
            throw new Exception('Error creating store view: ' . $e->getMessage());
        }
    }

    public function getStoreGroups(MagentoApiService $magentoApi)
    {
        $token = $magentoApi->getAccessToken();
        $response = $magentoApi->client->get("{$magentoApi->apiUrl}/rest/V1/store/storeGroups", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }


    public function getCustomersByWebsiteId(MagentoApiService $magentoApi, $websiteId)
    {
        $token = $magentoApi->getAccessToken();

        $url = "{$magentoApi->apiUrl}/rest/V1/customers/search?" . http_build_query([
                'searchCriteria[filter_groups][0][filters][0][field]' => 'website_id',
                'searchCriteria[filter_groups][0][filters][0][value]' => $websiteId,
                'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
                'searchCriteria[pageSize]' => 100,
                'searchCriteria[currentPage]' => 1,
            ]);

        $response = $magentoApi->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

/*    function generateRandomUniqueStoreCode(string $locale, int $prefixLength = 4): string
    {
        $locale = strtolower(trim($locale));
        $code = '';

        do {
            $randomPrefix = Str::lower(Str::random($prefixLength));
            $code = "{$randomPrefix}_{$locale}";
        } while (DB::table('store')->where('code', $code)->exists());

        return $code;
    }*/


}
