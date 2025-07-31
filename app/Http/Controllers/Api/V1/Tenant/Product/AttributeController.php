<?php

namespace App\Http\Controllers\Api\V1\Tenant\Product;

use App\Enums\FrontendInputType;
use App\Http\Controllers\Controller;
use App\Services\Tenant\Product\AttributesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

/**
 * @group Magento Attributes
 *
 * APIs for managing Magento product attributes.
 */
class AttributeController extends Controller
{
    protected AttributesService $attributesService;

    public function __construct(AttributesService $attributesService)
    {
        $this->attributesService = $attributesService;
    }

    /**
     * Check if a product attribute exists.
     *
     * @route GET /api/magento/attributes/{attributeCode}
     * @param string $attributeCode The code of the attribute to check (e.g., "color").
     */
    public function show(string $attributeCode): JsonResponse
    {
        $result = $this->attributesService->attributeExists($attributeCode);
        return $this->handleServiceResponse($result);
    }

    /**
     * Create a new product attribute.
     *
     * @route POST /api/magento/attributes
     * @bodyParam attribute_code string required The unique code for the attribute. Example: "custom_spec_1"
     * @bodyParam frontend_label string required The public-facing label for the attribute. Example: "Custom Specification"
     * @bodyParam frontend_input string required The input type for the attribute. Example: "text"
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'attribute_code' => 'required|string|regex:/^[a-z0-9_]+$/|max:30',
            'frontend_label' => 'required|string|max:255',
            'frontend_input' => ['required', new Enum(FrontendInputType::class)],
            'store_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $frontendInputEnum = FrontendInputType::from($validated['frontend_input']);

        $result = $this->attributesService->createProductAttribute(
            $validated['attribute_code'],
            $validated['frontend_label'],
            $frontendInputEnum,
            $validated['store_id']
        );

        return $this->handleServiceResponse($result, 201); // 201 Created on success
    }

    /**
     * Create a new attribute group within an attribute set.
     *
     * @route POST /api/magento/attribute-sets/{attributeSetId}/groups
     * @bodyParam group_name string required The name for the new group. Example: "Technical Specs"
     */
    public function createGroup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'required|string|max:255',
            'attribute_set_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $result = $this->attributesService->createAttributeGroup($request->input('group_name'), $request->input('attribute_set_id'));

        return $this->handleServiceResponse($result, 201);
    }

    /**
     * Assign an attribute to an attribute set and group.
     *
     * @route POST /api/magento/attribute-sets/assign-attribute
     * @bodyParam attribute_code string required The code of the attribute to assign.
     * @bodyParam attribute_set_id int required The ID of the attribute set.
     * @bodyParam attribute_group_id int required The ID of the attribute group within the set.
     * @bodyParam sort_order int The sort order for the attribute. Defaults to 10.
     */
    public function assignToSet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'attribute_code' => 'required|string|max:255',
            'attribute_set_id' => 'required|integer',
            'attribute_group_id' => 'required|integer',
            'sort_order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $result = $this->attributesService->assignAttributeToSet(
            $validated['attribute_code'],
            $validated['attribute_set_id'],
            $validated['attribute_group_id'],
            $validated['sort_order'] ?? 10
        );

        return $this->handleServiceResponse($result);
    }

    /**
     * Update a product's attribute value for a specific store view.
     *
     * @route PUT /api/magento/products/{sku}/attributes
     * @bodyParam store_code string required The store view code (e.g., "default", "en_us").
     * @bodyParam attribute_code string required The attribute to update.
     * @bodyParam value string required The new value for the attribute.
     */
    public function updateProductAttribute(Request $request, string $sku): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'store_code' => 'required|string',
            'attribute_code' => 'required|string',
            'value' => 'present|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $result = $this->attributesService->updateProductAttribute(
            $sku,
            $validated['store_code'],
            $validated['attribute_code'],
            $validated['value']
        );

        return $this->handleServiceResponse($result);
    }

    /**
     * Trigger a Magento reindex process.
     *
     * @route POST /api/magento/reindex
     * @bodyParam indexer_ids array An array of indexer IDs to run. Defaults to ['catalog_product_attribute'].
     */
    public function reindex(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'indexer_ids' => 'sometimes|array',
            'indexer_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $indexerIds = $request->input('indexer_ids', ['catalog_product_attribute']);
        $result = $this->attributesService->reindex($indexerIds);

        return $this->handleServiceResponse($result);
    }

    /**
     * Helper to format responses from the service layer.
     *
     * @param array $result The result array from the AttributesService.
     * @param int $successStatusCode The HTTP status code to use on success.
     * @return JsonResponse
     */
    private function handleServiceResponse(array $result, int $successStatusCode = 200): JsonResponse
    {
        if ($result['status'] === 'success') {
            return response()->json($result, $successStatusCode);
        }

        // Default to 500 if a specific error code isn't provided by the service
        $errorCode = $result['code'] ?? 500;
        return response()->json($result, $errorCode);
    }
}
