<?php

namespace App\Services\Tenant;

use App\Services\MagentoApiService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class MagentoProductService
{

    protected  MagentoApiService $magentoApi;

    public function __construct( MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }


    public function getProductsForStore(int $websiteId): array
    {
        $token =  $this->magentoApi->getAccessToken();

        $searchCriteria = [
            'searchCriteria[filter_groups][0][filters][0][field]' => 'website_id',
            'searchCriteria[filter_groups][0][filters][0][value]' => $websiteId,
            'searchCriteria[filter_groups][0][filters][0][condition_type]' => 'eq',
        ];

        // The search criteria are added as query parameters to the endpoint URL.
        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products?" . http_build_query($searchCriteria);

        try {
            $response =  $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody(), true);

            // The actual product list is inside the 'items' key of the response.
            return $responseData['items'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to fetch products from Magento for website ID: ' . $websiteId, [
                'error' => $e->getMessage()
            ]);
            // Re-throw the exception to be handled by the controller.
            throw $e;
        }
    }


    public function doesAttributeExist(string $attributeCode): bool
    {
        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products/attributes/{$attributeCode}";

        try {
            $response =  $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);
            return $response->getStatusCode() === 200;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return false;
            }
            throw $e;
        } catch (Exception $e) {
            Log::error("Error checking attribute existence: $attributeCode", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createAttributeGroup(string $groupName, int $attributeSetId): int
    {
        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products/attribute-sets/groups";

        $payload = [
            'group' => [
                'attribute_group_name' => $groupName,
                'attribute_set_id' => $attributeSetId,
                'extension_attributes' => [
                    'attribute_group_code' => Str::slug($groupName, '_'),
                    'sort_order' => "80"
                ]
            ]
        ];

        $response =  $this->magentoApi->client->post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $responseData = json_decode($response->getBody(), true);
        return $responseData['attribute_group_id'] ?? 0;
    }

    public function assignAttributeToAttributeSet(string $attributeCode, int $attributeSetId): bool
    {
        $token =  $this->magentoApi->getAccessToken();

        // ✅ Hardcoded group ID for "Product Details" group in attribute set 4
        $attributeGroupId = 7; // Replace with the actual ID from your Magento system

        // Send assignment request
        $assignEndpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products/attribute-sets/attributes";

        try {
            $response =  $this->magentoApi->client->post($assignEndpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'attributeSetId' => $attributeSetId,
                    'attributeGroupId' => $attributeGroupId,
                    'attributeCode' => $attributeCode,
                    'sortOrder' => 100,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            Log::error("Failed to assign attribute '{$attributeCode}' to group ID '{$attributeGroupId}' in set '{$attributeSetId}'", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    public function createProductAttribute(array $attributeData): array
    {
        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products/attributes";

        try {
            $response =  $this->magentoApi->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => ['attribute' => $attributeData],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error('Failed to create product attribute', [
                'attribute' => $attributeData['attribute_code'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }



    public function ensureCustomAttributesExistAndAssigned(array $customAttributes, int $attributeSetId)
    {
        foreach ($customAttributes as $attr) {
            $code = $attr['attribute_code'] ?? null;
            if (!$code) continue;

            if (!$this->doesAttributeExist($code)) {
                $this->createProductAttribute([
                    'attribute_code' => $code,
                    'frontend_input' => 'text',
                    'default_frontend_label' => ucfirst(str_replace('_', ' ', $code)),
                    //'is_global' => 1,
                    'is_user_defined' => true,
                    'is_required' => false,
                    'entity_type_id' => 4,
                    'is_visible' => true,
                    'used_in_product_listing' => true
                ]);
            }

            $this->assignAttributeToAttributeSet($code, $attributeSetId);
        }
    }




    /**
     * Creates a new product in Magento and assigns it to a specific website.
     *
     * @param array $product The product data. Minimum required: sku, name, price, attribute_set_id, type_id.
     * @param int $websiteId The ID of the website to assign the product to.
     * @return array The created product data from Magento's API response.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function storeProductForStore(array $product, int $websiteId , $images): array
    {

        if ($this->checkProductExist($product['sku'], $websiteId, $this->magentoApi)) {
            throw new Exception(
                "Product with SKU '{$product['sku']}' already exists and is assigned to website ID '{$websiteId}'."
            );
        }


        $this->ensureCustomAttributesExistAndAssigned($product['custom_attributes'] ?? [], $product['attribute_set_id'] ?? 4);



        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products";

        // Magento API requires the payload to be wrapped in a "product" key.
        // We also add the website ID under `extension_attributes`.
        /* $payload = [
            'product' => array_merge($product, [
                'extension_attributes' => [
                    'website_ids' => [$websiteId],
                ],
            ]),
        ]; */

        $payload = [
            'product' => [
                'sku' => $product['sku'],
                'name' => $product['name'],
                'price' => $product['price'],
                'status' => 1,
                'visibility' => 4,
                'type_id' => 'simple',
                'attribute_set_id' => 4,
                'extension_attributes' => [
                    'website_ids' => [$websiteId],
                    'stock_item' => [
                        'qty' => $product['stock_quantity'],
                        'is_in_stock' => $product['stock_quantity'] > 0,
                    ],
                    'category_links' => $product['category_id'] ? [['position' => 0, 'category_id' => (string)$product['category_id']]] : [],
                ],
                'custom_attributes' => $product['custom_attributes'] ?? [],
            ],
        ];

        try {
            $response =  $this->magentoApi->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $createdProduct =  json_decode($response->getBody(), true);

            Log::info('payload' , $images);

            if (!empty($images)) {
                try {
                    $this->addMediaToProduct($createdProduct['sku'], $images, $this->magentoApi);
                } catch (Exception $mediaException) {
                    // Log a warning that media failed, but don't stop the process.
                    // The product was still created successfully.
                    Log::warning('Product created, but media upload failed.', [
                        'sku' => $createdProduct['sku'],
                        'error' => $mediaException->getMessage()
                    ]);
                }
            }

            return $createdProduct;

        } catch (Exception $e) {
            Log::error('Failed to create product in Magento for website ID: ' . $websiteId, [
                'sku'   => $product['sku'] ?? 'N/A',
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            throw $e;
        }
    }


    /**
     * ✅ NEW METHOD: Handles uploading and assigning images to a product.
     *
     * @param string $sku The product SKU.
     * @param UploadedFile[] $images Array of image files from the request.
     * @return array An array of the created media gallery entry IDs.
     * @throws \InvalidArgumentException if more than two images are provided.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addMediaToProduct(string $sku, array $images): array
    {
        // 1. Enforce the business rule: maximum of two images.
        if (count($images) > 2) {
            throw new \InvalidArgumentException('A maximum of two images can be uploaded per product.');
        }

        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products/" . urlencode($sku) . "/media";
        $mediaEntryIds = [];

        foreach ($images as $index => $imageFile) {
            // 2. Prepare the image data for the API payload.
            $imageContent = base64_encode(file_get_contents($imageFile->getRealPath()));
            $imageName = Str::slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $imageFile->getClientOriginalExtension();

            // 3. Define roles. The first image is the main one for everything.
            $roles = [];
            if ($index === 0) {
                $roles = ['image', 'small_image', 'thumbnail'];
            }

            $payload = [
                'entry' => [
                    'media_type' => 'image',
                    'label'      => ucfirst(str_replace('-', ' ', pathinfo($imageName, PATHINFO_FILENAME))),
                    'position'   => $index + 1,
                    'disabled'   => false,
                    'types'      => $roles,
                    'content'    => [
                        'base64_encoded_data' => $imageContent,
                        'type'                => $imageFile->getMimeType(),
                        'name'                => $imageName,
                    ],
                ],
            ];

            try {
                // 4. Make the API call for each image.
                $response =  $this->magentoApi->client->post($endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload,
                ]);
                $mediaEntryIds[] = json_decode($response->getBody(), true);
            } catch (Exception $e) {
                Log::error('Failed to upload an image to Magento.', [
                    'sku' => $sku,
                    'filename' => $imageFile->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
                // Re-throw to stop the process if one image fails.
                throw $e;
            }
        }

        return $mediaEntryIds;
    }


    /**
     * Retrieves a single product from Magento by its SKU.
     *
     * @param string $sku
     * @return array The product data.
     * @throws ClientException if the product is not found (404) or on other API errors.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getProductBySku(string $sku): array
    {
        $token =  $this->magentoApi->getAccessToken();
        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products/" . urlencode($sku);

        try {
            $response =  $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error('Failed to get product from Magento.', ['sku' => $sku, 'error' => $e->getMessage()]);
            // Re-throw the exception to be handled by the controller
            throw $e;
        }
    }


    /**
     * Checks if a product with a given SKU exists and is assigned to a specific website.
     *
     * @param string $sku The product SKU to check.
     * @param int $websiteId The website ID to check for assignment.
     * @return bool True if the product exists and is assigned to the website, false otherwise.
     *
     * @throws Exception
     */
    public function checkProductExist(string $sku, int $websiteId): bool
    {
        $token =  $this->magentoApi->getAccessToken();

        $endpoint = "{ $this->magentoApi->apiUrl}/rest/V1/products/" . urlencode($sku);

        try {
            $response =  $this->magentoApi->client->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            // If the request succeeds (200 OK), the product exists.

            // Now, we must check if it's assigned to the correct website.
            $product = json_decode($response->getBody(), true);
            $websiteIds = $product['extension_attributes']['website_ids'] ?? [];

            return in_array($websiteId, $websiteIds);
        } catch (ClientException $e) {
            // A 404 Not Found response is the expected outcome if the product SKU does not exist.
            // In this case, it's not an error, so we return false.
            if ($e->getResponse()->getStatusCode() === 404) {
                return false;
            }

            // For any other client error (e.g., 401 Unauthorized), log it and re-throw.
            Log::error('Magento API client error while checking for product SKU: ' . $sku, [
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (Exception $e) {
            // For any other exception (e.g., server error, network issue), log and re-throw.
            Log::error('Failed to check if product exists in Magento for SKU: ' . $sku, [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
