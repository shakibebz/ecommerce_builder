<?php

namespace App\Services\Tenant\Product;


use App\Enums\FrontendInputType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class AttributesService
{
    protected $client;
    protected $apiUrl;
    protected $username;
    protected $password;
    protected $token = null;

    public function __construct()
    {
        $this->apiUrl = config('magento.api_url');
        $this->username = config('magento.username');
        $this->password = config('magento.password');
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Fetch and cache the Magento admin access token.
     *
     * @return string
     * @throws RequestException
     */
    protected function getAccessToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        try {
            $response = $this->client->post("/rest/V1/integration/admin/token", [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);
            $this->token = json_decode($response->getBody()->getContents(), true);
            return $this->token;
        } catch (RequestException $e) {
            Log::error('Error fetching access token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Guzzle client with Authorization header.
     *
     * @return Client
     */
    protected function getAuthorizedClient(): Client
    {
        return new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Check if a product attribute exists in Magento.
     *
     * @param string $attributeCode
     * @return array
     */
    public function attributeExists(string $attributeCode): array
    {
        try {
            $response = $this->getAuthorizedClient()->get("/rest/V1/products/attributes/{$attributeCode}");
            return [
                'status' => 'success',
                'exists' => true,
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                return [
                    'status' => 'success',
                    'exists' => false,
                    'message' => "Attribute '{$attributeCode}' does not exist",
                ];
            }
            Log::error('Error checking attribute existence: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : 500,
            ];
        }
    }

    /**
     * Create a new product attribute in Magento.
     *
     * @param string $attributeCode
     * @param string $frontendLabel
     * @param FrontendInputType $frontendInput
     * @return array
     */
    public function createProductAttribute(string $attributeCode, string $frontendLabel, FrontendInputType $frontendInput, int $storeId): array
    {
        // Check if attribute exists
        $exists = $this->attributeExists($attributeCode);
        if ($exists['status'] === 'success' && $exists['exists']) {
            return ['status' => 'error', 'message' => 'Attribute already exists'];
        }
        if ($exists['status'] === 'error') {
            return $exists;
        }

        $payload = [
            'attribute' => [
                'attribute_code' => $attributeCode,
                'frontend_input' => $frontendInput->value,
                'default_frontend_label' => $frontendLabel,
                'frontend_labels' => [
                    [
                        'label' => $frontendLabel,
                        'store_id' => $storeId,
                    ]
                ],
                'scope' => 'store',
                'is_required' => false,
                'is_user_defined' => true,
                'default_value' => '',
                'is_unique' => false,
                'is_visible_on_front' => true,
                'is_searchable' => false,
                'is_filterable' => false,
                'is_comparable' => false,
            ],
        ];


        // Additional configuration for select/multiselect inputs
        if (in_array($frontendInput, [FrontendInputType::SELECT, FrontendInputType::MULTISELECT], true)) {
            $payload['attribute']['options'] = [];
        }

        try {
            $response = $this->getAuthorizedClient()->post('/rest/V1/products/attributes', [
                'json' => $payload,
            ]);
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (RequestException $e) {
            // Provide more detailed error logging
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body.';
            Log::error('Error creating attribute: ' . $e->getMessage(), ['response' => $responseBody]);
            return [
                'status' => 'error',
                'message' => 'Client error: `POST ' . $e->getRequest()->getUri() . '` resulted in a `' . $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase() . "` response:\n" . $responseBody,
                'code' => $e->getResponse()->getStatusCode(),
            ];
        }
    }

    /**
     * Create an attribute group in Magento.
     *
     * @param string $groupName
     * @param int $attributeSetId
     * @return array
     */
    public function createAttributeGroup(string $groupName, int $attributeSetId): array
    {
        $payload = [
            'group' => [
                'attribute_group_name' => $groupName,
                'attribute_set_id' => $attributeSetId,
            ],
        ];

        try {
            $response = $this->getAuthorizedClient()->post("/rest/V1/products/attribute-sets/groups", [
                'json' => $payload,
            ]);
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (RequestException $e) {
            Log::error('Error creating attribute group: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getResponse()->getStatusCode(),
            ];
        }
    }

    /**
     * Assign an attribute to an attribute set and group.
     *
     * @param string $attributeCode
     * @param int $attributeSetId
     * @param int $attributeGroupId
     * @param int $sortOrder
     * @return array
     */
    public function assignAttributeToSet(string $attributeCode, int $attributeSetId, int $attributeGroupId, int $sortOrder = 10): array
    {
        $payload = [
            'attributeSetId' => $attributeSetId,
            'attributeGroupId' => $attributeGroupId,
            'sortOrder' => $sortOrder,
            'attributeCode' => $attributeCode
        ];

        try {
            $response = $this->getAuthorizedClient()->post("/rest/V1/products/attribute-sets/attributes", [
                'json' => $payload,
            ]);
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (RequestException $e) {
            Log::error('Error assigning attribute to set: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getResponse()->getStatusCode(),
            ];
        }
    }

    /**
     * Update a product's store-specific attribute value.
     *
     * @param string $sku
     * @param string $storeCode
     * @param string $attributeCode
     * @param string $value
     * @return array
     */
    public function updateProductAttribute(string $sku, string $storeCode, string $attributeCode, string $value): array
    {
        $payload = [
            'product' => [
                'sku' => $sku,
                'custom_attributes' => [
                    [
                        'attribute_code' => $attributeCode,
                        'value' => $value,
                    ],
                ],
            ],
        ];

        try {
            $response = $this->getAuthorizedClient()->put("/rest/{$storeCode}/V1/products/{$sku}", [
                'json' => $payload,
            ]);
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (RequestException $e) {
            Log::error('Error updating product attribute: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getResponse()->getStatusCode(),
            ];
        }
    }

    /**
     * Trigger re-indexing for specific indexers.
     *
     * @param array $indexerIds
     * @return array
     */
    public function reindex(array $indexerIds = ['catalog_product_attribute']): array
    {
        try {
            $response = $this->getAuthorizedClient()->post('/rest/V1/indexer/reindex', [
                'json' => ['indexerIds' => $indexerIds],
            ]);
            return [
                'status' => 'success',
                'data' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (RequestException $e) {
            Log::error('Error triggering reindex: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getResponse()->getStatusCode(),
            ];
        }
    }
}
