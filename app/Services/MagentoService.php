<?php

namespace App\Services;

use Exception;
use App\Models\Product;
use App\Models\CategoryMapping;
use App\Models\AttributeMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Jobs\SyncMagentoProductImage;
use Illuminate\Support\Facades\Storage;

final class MagentoService
{
    private string $baseUrl;
    private string $accessToken;
    private MagentoAttributeManager $attributeManager;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.magento.base_url'), '/');
        $this->accessToken = config('services.magento.access_token');

        if (!$this->baseUrl || !$this->accessToken) {
            throw new Exception('Magento service credentials are not configured.');
        }

        // Initialize the manager with the base URL and token
        $this->attributeManager = new MagentoAttributeManager($this->baseUrl, $this->accessToken);
    }

    public function createOrUpdateProduct(Product $product): array
    {
        $sku = rawurlencode($product->sku);
        $productExists = $this->productExists($sku);
        $attributeSetId = 10;


        $magentoCategoryId = null;
        if (!empty($product->category)) {
            $mapping = CategoryMapping::where('source_name', trim($product->category))->first();
            if ($mapping && $mapping->is_mapped) {
                $magentoCategoryId = $mapping->magento_category_id;
            } else {
                Log::info("Skipping category for SKU '{$product->sku}'. Category '{$product->category}' is pending mapping.");
            }
        }

        $payload = [
            'product' => [
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'status' => 1,
                'visibility' => 4,
                'type_id' => 'simple',
                'attribute_set_id' => $attributeSetId,
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => $product->stock_quantity,
                        'is_in_stock' => $product->stock_quantity > 0,
                    ],
                    'category_links' => $magentoCategoryId ? [['position' => 0, 'category_id' => (string)$magentoCategoryId]] : [],
                ],
                'custom_attributes' => $this->handleCustomAttributes($product, $attributeSetId),
            ],
        ];

        $endpoint = $productExists ? "/rest/V1/products/{$sku}" : '/rest/V1/products';
        $method = $productExists ? 'put' : 'post';

        if ($productExists) {
            $payload['saveOptions'] = true;
        }



        // Dispatch image sync jobs to the queue (fast and asynchronous)
        foreach ($product->images as $index => $relativeImagePath) {
            SyncMagentoProductImage::dispatch(
                $product->sku,
                $relativeImagePath,
                $product->name,
                $index,
            );
        }

        $response = $this->makeApiRequest($method, $endpoint, $payload);

        Log::info('Status log:' . $response['status']);

        if (!$response['status']) {
            Log::error("Product creation failed for SKU: {$sku}. Response: " . json_encode($response));
            return $response;
        }

        Log::info("Product created or updated successfully for SKU: {$sku}");

        return $response;
    }

    // In app/Services/MagentoService.php

    private function handleCustomAttributes(Product $product, int $attributeSetId): array
    {
        $magentoAttributes = [
            ['attribute_code' => 'description', 'value' => $product->description ?? ''],
        ];

        foreach ($product->attributes as $label => $value) {
            if (empty($label) || is_null($value) || $value === '') {
                continue;
            }

            $trimmedLabel = trim($label);

            // Step 1: Find the mapping. DO NOT create it here anymore.
            $mapping = AttributeMapping::where('source_label', $trimmedLabel)->first();

            // Step 2: If the mapping doesn't exist or isn't complete, skip.
            if (!$mapping || !$mapping->is_mapped || empty($mapping->magento_attribute_code)) {
                Log::info("Skipping attribute '{$trimmedLabel}' for product SKU '{$product->sku}'. It is unmapped or pending review.");
                continue;
            }

            // Step 3: The mapping is complete. Use it.
            $magentoCode = $mapping->magento_attribute_code;
            $magentoType = $mapping->magento_attribute_type;

            if ($magentoType === 'select') {
                $optionId = $this->attributeManager->getOrCreateOptionId(
                    $magentoCode,
                    (string)$value,
                    $attributeSetId,
                    $trimmedLabel,
                    $magentoType
                );
                if ($optionId) {
                    $magentoAttributes[] = ['attribute_code' => $magentoCode, 'value' => $optionId];
                }
            } else { // Handles 'text', 'textarea'
                $this->attributeManager->ensureAttributeExists(
                    $magentoCode,
                    $attributeSetId,
                    $trimmedLabel,
                    $magentoType
                );
                $magentoAttributes[] = ['attribute_code' => $magentoCode, 'value' => (string)$value];
            }
        }
        return $magentoAttributes;
    }

    private function productExists(string $sku): bool
    {
        try {
            $product = $this->makeApiRequest('get', "/rest/V1/products/{$sku}", [], true);
            return !is_null($product);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function uploadImageForProduct(string $sku, string $productName, int $imageIndex, string $relativeImagePath): void
    {

        $encodedSku = rawurlencode($sku);
        $mediaEndpoint = "/rest/V1/products/{$encodedSku}/media";

        // Build absolute path to the image
        $absolutePath = storage_path('app/public/' . ltrim($relativeImagePath, '/'));

        if (!file_exists($absolutePath)) {
            Log::error("File not found for SKU {$sku}, image #{$imageIndex}. Path: {$absolutePath}");
            return;
        }

        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION) ?: 'jpg';
        $mimeType = mime_content_type($absolutePath);
        if (!$mimeType) {
            Log::error("Could not determine MIME type for file: {$absolutePath}. Skipping upload for SKU {$sku}.");
            return;
        }
        $rawImageContent = file_get_contents($absolutePath);


        $imageName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', "{$sku}-{$imageIndex}.{$extension}");

        Log::info("Uploading image for SKU: {$sku}, Image Index: {$imageIndex}", [
            'image_name' => $imageName,
            'image_path' => $absolutePath,
        ]);


        // Prepare base media payload
        $mediaPayload = [
            'entry' => [
                'media_type' => 'image',
                'label'      => $productName . ' - Image ' . ($imageIndex + 1),
                'position'   => $imageIndex + 1,
                'disabled'   => false,
                'content'    => [
                    'base64_encoded_data' => base64_encode($rawImageContent),
                    'type'                => $mimeType,
                    'name'                => $imageName,
                ],
            ]
        ];

        // Only add the 'types' key for the first image
        if ($imageIndex === 0) {
            $mediaPayload['entry']['types'] = ['image', 'small_image', 'thumbnail'];
        }


        try {
            // makeApiRequest returns the decoded JSON array/object
            $apiResult = $this->makeApiRequest('post', $mediaEndpoint, $mediaPayload);

            // Correctly log the response by passing it as context
            Log::info("API Response for SKU {$sku}, image #{$imageIndex}", [
                'result' => $apiResult
            ]);

            $magentoImageId = is_array($apiResult) ? json_encode($apiResult) : $apiResult;

            Log::info("Successfully uploaded image for SKU {$sku}, image #{$imageIndex}. Magento Response ID: " . $magentoImageId);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Catch specific HTTP client exceptions
            Log::error("Magento API RequestException for SKU {$sku}", [
                'endpoint' => $mediaEndpoint,
                'status' => $e->response->status(),
                'message' => $e->response->json('message', 'Unknown Error'),
                'details' => $e->response->json()
            ]);
            Log::error("Failed to upload image for SKU {$sku}, image #{$imageIndex}. Error: " . $e->getMessage());
        } catch (Exception $e) {
            // Catch any other general exceptions
            Log::error("Failed to upload image for SKU {$sku}, image #{$imageIndex}. General Error: " . $e->getMessage());
        }
    }



    private function makeApiRequest(string $method, string $endpoint, array $payload = [], bool $ignoreNotFound = false): mixed
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(120) // Increased timeout for slow Magento APIs
            ->acceptJson()
            ->withToken($this->accessToken)
            ->$method($endpoint, $payload);

        if ($ignoreNotFound && $response->status() === 404) {
            return null;
        }

        if (!$response->successful()) {
            $errorDetails = $response->json() ?? ['raw_response' => $response->body()];
            Log::error('Magento API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'message' => $response->json('message', 'Unknown error.'),
                'details' => $errorDetails
            ]);

            throw new Exception(
                'Magento API Error: ' . $response->json('message', 'Unknown error.'),
                $response->status()
            );
        }

        return $response->json();
    }

    public function getProductBySku(string $sku)
    {
        try {
            $encodedSku = rawurlencode($sku);
            $response = $this->makeApiRequest('get', "/rest/V1/products/{$encodedSku}");
            return $response ? $response : null;
        } catch (Exception $e) {
            // Log the exception and return null
            Log::error("Failed to fetch product by SKU {$sku}. Error: {$e->getMessage()}");
            return null;
        }
    }
}
