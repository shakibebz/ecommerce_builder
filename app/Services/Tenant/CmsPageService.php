<?php

namespace App\Services\Tenant;

use App\Services\MagentoApiService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class CmsPageService
{
    protected $client;
    protected $magentoApi;

    public function __construct(MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }

    /**
     * Create a new CMS page
     *
     * @param array $cmsPage
     * @return array
     * @throws RequestException
     */
    public function createCmsPage(array $cmsPage)
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/custom-cms-page";

        Log::info('Creating CMS Page in Magento for Store ID: ' . json_encode($cmsPage['store_id']));

        $payload = [
            'page' => [
                'identifier' => $cmsPage['identifier'],
                'title' => $cmsPage['title'],
                'content' => $cmsPage['content'],
                'active' => $cmsPage['is_active'],
                'page_layout' => $cmsPage['page_layout'] ?? '1column',
                'meta_title' => $cmsPage['meta_title'] ?? null,
                'meta_keywords' => $cmsPage['meta_keywords'] ?? null,
                'meta_description' => $cmsPage['meta_description'] ?? null,
            ],
            'stores' => [$cmsPage['store_id']],
        ];

        Log::info('Payload for CMS Page creation: ' . json_encode($payload));

        try {
            $response = $this->magentoApi->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to create CMS Page in Magento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve all CMS pages
     *
     * @return array
     * @throws RequestException
     */
    public function getCmsPages()
    {
        $token = $this->magentoApi->getAccessToken();

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

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsPage/search?" . http_build_query($searchCriteria);

        try {
            $response = $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to fetch CMS Pages from Magento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a CMS page by ID
     *
     * @param int $pageId
     * @return array
     * @throws \Exception
     */
    public function deleteCmsPage(int $pageId)
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsPage/{$pageId}";

        try {
            $response = $this->magentoApi->client->delete($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to delete CMS Page with ID ' . $pageId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve a CMS page by ID
     *
     * @param int $pageId
     * @return array
     * @throws \Exception
     */
    public function getCmsPageById(int $pageId)
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/cmsPage/{$pageId}";

        try {
            $response = $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to get CMS Page with ID ' . $pageId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing CMS page
     *
     * @param int $pageId
     * @param array $cmsPage
     * @return array
     * @throws RequestException
     */
    public function updateCmsPage(int $pageId, array $cmsPage)
    {
        $token = $this->magentoApi->getAccessToken();

        $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/custom-cms-page/{$pageId}";

        Log::info('Updating CMS Page in Magento for ID: ' . $pageId);

        $payload = [
            'page' => [
                'id' => $pageId,
                'identifier' => $cmsPage['identifier'],
                'title' => $cmsPage['title'],
                'content' => $cmsPage['content'],
                'active' => $cmsPage['is_active'],
                'page_layout' => $cmsPage['page_layout'] ?? '1column',
                'meta_title' => $cmsPage['meta_title'] ?? null,
                'meta_keywords' => $cmsPage['meta_keywords'] ?? null,
                'meta_description' => $cmsPage['meta_description'] ?? null,
            ],
            'stores' => [$cmsPage['store_id']],
        ];

        Log::info('Payload for CMS Page update: ' . json_encode($payload));

        try {
            $response = $this->magentoApi->client->put($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to update CMS Page with ID ' . $pageId . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
