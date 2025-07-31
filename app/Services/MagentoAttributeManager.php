<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MagentoAttributeManager
{
    private PendingRequest $httpClient;
    private array $attributeCache = [];

    public function __construct(string $baseUrl, string $accessToken)
    {
        if (!$baseUrl || !$accessToken) {
            throw new Exception('Magento credentials are required for AttributeManager.');
        }
        $this->httpClient = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout(120)
            ->withToken($accessToken);
    }

    /**
     * Ensures an attribute exists and is assigned to the specified attribute set.
     * This method is public so it can be called directly for text/textarea attributes.
     */
    public function ensureAttributeExists(string $attributeCode, int $attributeSetId, string $frontendLabel, string $inputType): void
    {
        if (isset($this->attributeCache[$attributeCode]['exists'])) {
            return;
        }

        $response = $this->httpClient->get("/rest/V1/products/attributes/{$attributeCode}");

        if ($response->status() === 404) {
            $this->createAttribute($attributeCode, $frontendLabel, $inputType);
            $this->assignAttributeToSet($attributeCode, $attributeSetId);
        }

        $this->attributeCache[$attributeCode]['exists'] = true;
    }

    /**
     * Gets the ID for an attribute option (e.g., the ID for 'Blue').
     * Creates the attribute and/or option if they don't exist. For 'select' types only.
     */
    public function getOrCreateOptionId(string $attributeCode, string $optionLabel, int $attributeSetId, string $frontendLabel, string $inputType): ?int
    {
        $this->ensureAttributeExists($attributeCode, $attributeSetId, $frontendLabel, $inputType);

        if (isset($this->attributeCache[$attributeCode]['options'][$optionLabel])) {
            return $this->attributeCache[$attributeCode]['options'][$optionLabel];
        }

        if (!isset($this->attributeCache[$attributeCode]['all_options'])) {
            $response = $this->httpClient->get("/rest/V1/products/attributes/{$attributeCode}/options");
            $options = $response->successful() ? $response->json() : [];
            // Filter out the empty placeholder option Magento adds
            $this->attributeCache[$attributeCode]['all_options'] = array_filter($options, fn ($opt) => !empty($opt['value']));
        }

        $existingOption = collect($this->attributeCache[$attributeCode]['all_options'])
            ->firstWhere('label', $optionLabel);

        if ($existingOption) {
            $optionId = (int) $existingOption['value'];
            $this->attributeCache[$attributeCode]['options'][$optionLabel] = $optionId;
            return $optionId;
        }

        $response = $this->httpClient->post("/rest/V1/products/attributes/{$attributeCode}/options", [
            'option' => ['label' => $optionLabel]
        ]);

        if (!$response->successful()) {
            Log::error("Failed to create option '{$optionLabel}' for attribute '{$attributeCode}'.", $response->json());
            return null;
        }

        $newOptionId = (int) $response->json();
        $this->attributeCache[$attributeCode]['options'][$optionLabel] = $newOptionId;
        $this->attributeCache[$attributeCode]['all_options'][] = ['label' => $optionLabel, 'value' => $newOptionId];
        return $newOptionId;
    }

    private function createAttribute(string $attributeCode, string $frontendLabel, string $inputType): void
    {
        $payload = [
            'attribute' => [
                'attribute_code' => $attributeCode,
                'frontend_input' => $inputType,
                'is_required' => false,
                'is_user_defined' => true,
                //'is_global' => 1,
                'is_searchable' => true,
                'is_filterable' => ($inputType === 'select'),
                'is_comparable' => true,
                'default_frontend_label' => $frontendLabel,
            ],
        ];
    Log::info('Payload being sent to Magento:', ['payload' => $payload]);
        //dd($payload);

        $this->httpClient->post('/rest/V1/products/attributes', $payload)->throw();
    }

    private function assignAttributeToSet(string $attributeCode, int $attributeSetId): void
    {
        $groupsResponse = $this->httpClient->get("/rest/V1/products/attribute-sets/{$attributeSetId}/groups/list", [
            'searchCriteria' => ['pageSize' => 1]
        ])->throw()->json();

        $attributeGroupId = $groupsResponse['items'][0]['attribute_group_id'] ?? null;
        if (!$attributeGroupId) {
            throw new Exception("Could not find an attribute group in set ID {$attributeSetId}.");
        }

        $this->httpClient->post('/rest/V1/products/attribute-sets/attributes', [
            'attributeSetId' => $attributeSetId,
            'attributeGroupId' => $attributeGroupId,
            'attributeCode' => $attributeCode,
            'sortOrder' => 100,
        ])->throw();
    }
}
