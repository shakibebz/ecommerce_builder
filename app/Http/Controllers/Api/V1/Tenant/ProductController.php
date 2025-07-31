<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Models\Stores;
use App\Services\MagentoApiService;
use Illuminate\Http\Request;
use App\Services\MagentoService;
use App\Http\Controllers\Controller;
use App\Services\MagentoSyncService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Validator;
use App\Services\Tenant\MagentoProductService;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{

    public $magentoProductService;

    /**
     * Class constructor.
     */
    public function __construct(MagentoProductService $magentoProductService)
    {
        $this->magentoProductService = $magentoProductService;

    }

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request, Stores $store)
    {

        //$tenant = $request->user();

        //$websiteId = $tenant->active_store->magento_website_id;

        if (!$request['website_id']) {
            return response()->json(['message' => 'No active Magento store configured for this tenant.'], 404);
        }

        try {
            // Call the new method on the service instance.
            $products = $this->magentoProductService->getProductsForStore($request['website_id']);

            return response()->json(['data' => $products]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve products from Magento.'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        // Step 1: Validate the incoming multipart request
        $request->validate([
            'json_payload' => 'required|string|json', // Ensure it's a valid JSON string
            'images' => 'sometimes|array|max:2',      // Validate max 2 files
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048' // Validate each file
        ]);

        $productData = json_decode($request->input('json_payload'), true);

        $productValidator = Validator::make($productData, [
            'sku' => 'required|string|max:64',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'website_id' => 'required|integer',
            'category_id' => 'nullable|integer',
            'custom_attributes' => 'sometimes|array',
            'custom_attributes.*.attribute_code' => 'required_with:custom_attributes|string',
            'custom_attributes.*.value' => 'required_with:custom_attributes|string',
        ]);

        if ($productValidator->fails()) {
            throw new ValidationException($productValidator);
        }

        $images = $request->hasFile('images') ? $request->file('images') : [];

        try {
            $websiteId = $productData['website_id'];

            $createdProduct = $this->magentoProductService->storeProductForStore(
                $productData,
                $websiteId,
                $images
            );

            return response()->json($createdProduct, 201);
        } catch (ClientException $e) {
            // Handle API errors from Guzzle
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);
            return response()->json([
                'message' => 'An error occurred with the Magento API.',
                'details' => $body['message'] ?? 'No details provided.',
            ], $response->getStatusCode());
        } catch (\Exception $e) {
            // Handle other errors, like the product already existing
            $statusCode = str_contains($e->getMessage(), 'already exists') ? 409 : 500;
            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $sku)
    {
        try {
            $product = $this->magentoProductService->getProductBySku($sku);
            return response()->json($product);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return response()->json(['message' => "Product with SKU '{$sku}' not found."], 404);
            }
            // For other API errors
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            return response()->json([
                'message' => 'An error occurred with the Magento API.',
                'details' => $body['message'] ?? 'No details provided.',
            ], $e->getResponse()->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['message' => 'An internal server error occurred.'], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
