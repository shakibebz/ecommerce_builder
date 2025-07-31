<?php

namespace App\Services\Tenant;

use App\Services\MagentoApiService;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class CmsBlockService
{
    protected $client;
    protected $magentoApi;

    public function __construct(MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }


    public function createCmsBlock(array $cmsBlock)
    {

        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/custom-cms-block";

        Log::info('Creating CMS Block in Magento for Store ID: ' . $cmsBlock['store_id']);

        $payload = [
            'block' => [
                'identifier' => $cmsBlock['identifier'],
                'title' => $cmsBlock['title'],
                'content' => $cmsBlock['content'],
                'active' => $cmsBlock['is_active'],
            ],
            'stores' => [$cmsBlock['store_id']],
        ];


        Log::info('Payload for CMS Block creation: ' . json_encode($payload));

        try {

            $response = $this->magentoApi->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to create CMS Block in Magento for Store ID');
            throw $e;
        }
    }


    public function updateCmsBlock(array $cmsBlock)
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/custom-cms-block/{$cmsBlock['id']}";

        Log::info('Updating CMS Block in Magento for Block ID: ' . $cmsBlock['id'] . ', Store ID: ' . $cmsBlock['store_id']);

        $payload = [
            'block' => [
                'identifier' => $cmsBlock['identifier'],
                'title' => $cmsBlock['title'],
                'content' => $cmsBlock['content'],
                'active' => $cmsBlock['is_active'],
            ],
            'stores' => [$cmsBlock['store_id']],
        ];

        Log::info('Payload for CMS Block update: ' . json_encode($payload));

        try {
            $response = $this->magentoApi->client->put($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to update CMS Block in Magento for Block ID: ' . $cmsBlock['id']);
            throw $e;
        }
    }

    public function getCmsBlocks()
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsBlock";


        $searchCriteria = [
            'searchCriteria' => [
                'sortOrders' => [
                    [
                        'field' => 'identifier',
                        'direction' => 'ASC'
                    ]
                ]
            ]
        ];

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsBlock/search?" . http_build_query($searchCriteria);

        try {
            $response = $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to fetch CMS Blocks from Magento: ' . $e->getMessage());
            throw $e;
        }
    }


    public function deleteCmsByBlock(int $blockId)
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsBlock";

        try {
            $response = $this->magentoApi->client->delete("{$endpoint}/{$blockId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $ex) {
            Log::error('Failed to delete CMS Block with ID ' . $blockId . ': ' . $ex->getMessage());
            throw $ex;
        }
    }

    public function getCmsBlockById(int $blockId)
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsBlock";

        try {
            $response = $this->magentoApi->client->get("{$endpoint}/{$blockId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $ex) {
            Log::error('Failed to get CMS Block with ID ' . $blockId . ': ' . $ex->getMessage());
            throw $ex;
        }
    }


    /**
     * Retrieves all CMS blocks for a given store ID from Magento API.
     *
     * @param int $storeId The ID of the store to filter CMS blocks.
     * @return array The CMS blocks data from the API response.
     * @throws InvalidArgumentException If the store ID is invalid.
     * @throws RequestException If the API request fails.
     */
    public function getAllCmsBlocksForAStore(int $storeId): array
    {
        if ($storeId <= 0) {
            Log::error('Invalid Store ID provided: ' . $storeId);
            throw new InvalidArgumentException('Store ID must be a positive integer.');
        }

        $token = $this->magentoApi->getAccessToken();

        $searchCriteria = [
            'searchCriteria' => [
                'filter_groups' => [
                    [
                        'filters' => [
                            [
                                'field' => 'store_id',
                                'value' => $storeId,
                                'condition_type' => 'eq',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $query = http_build_query($searchCriteria);
        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsBlock/search?{$query}";

        Log::info('Retrieving CMS Blocks for Store ID: ' . $storeId, ['endpoint' => $endpoint]);

        try {
            $response = $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode JSON response for Store ID: ' . $storeId);
                throw new RuntimeException('Invalid JSON response from Magento API.');
            }

            return $responseData;
        } catch (RequestException $e) {
            Log::error('Failed to retrieve CMS Blocks for Store ID: ' . $storeId, [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
