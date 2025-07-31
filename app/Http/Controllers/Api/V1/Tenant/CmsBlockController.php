<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Services\MagentoApiService;
use App\Services\MagentoSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Tenant\CmsBlockService;
use GuzzleHttp\Exception\ClientException;

class CmsBlockController extends Controller
{

    public $cmsBlockService;

    /**
     * Class constructor.
     */
    public function __construct(CmsBlockService $cmsBlockService)
    {
        $this->cmsBlockService = $cmsBlockService;
    }

    public function store(Request $request)
    {

        try {
            $cmsBlock = $request->validate([
                'identifier' => 'required|string',
                'title' => 'required|string',
                'content' => 'required|string',
                'is_active' => 'boolean',
                'store_id' => 'required|integer',
            ]);

            $respone = $this->cmsBlockService->createCmsBlock($cmsBlock);

            return response()->json($respone, 201);
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


    public function show(int $cmsBlock)
    {
        try {
            $cmsBlocks = $this->cmsBlockService->getCmsBlockById($cmsBlock);
            return response()->json($cmsBlocks, 200);
        } catch (ClientException $e) {
            // Handle API errors from Guzzle
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);
            return response()->json([
                'message' => 'An error occurred with the Magento API.',
                'details' => $body['message'] ?? 'No details provided.',
            ], $response->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        try {
            $cmsBlocks = $this->cmsBlockService->getCmsBlocks();

            return response()->json($cmsBlocks, 200);
        } catch (ClientException $e) {
            // Handle API errors from Guzzle
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);
            return response()->json([
                'message' => 'An error occurred with the Magento API.',
                'details' => $body['message'] ?? 'No details provided.',
            ], $response->getStatusCode());
        } catch (\Exception $ex) {
        }
    }

    public function destroy(int $cmsBlock)
    {
        Log::info('Deleting CMS Block with ID: ' . $cmsBlock);

        try {
            $this->cmsBlockService->deleteCmsByBlock($cmsBlock);
            return response()->json(['message' => 'Cms Block deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {

        try {
            $cmsBlock = $request->validate([
                'id' => 'required|integer',
                'identifier' => 'required|string',
                'title' => 'required|string',
                'content' => 'required|string',
                'is_active' => 'boolean',
                'store_id' => 'required|integer',
            ]);

            $response = $this->cmsBlockService->updateCmsBlock($cmsBlock);

            return response()->json($response, 200);
        } catch (ClientException $e) {
            // Handle API errors from Guzzle
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);
            return response()->json([
                'message' => 'An error occurred with the Magento API.',
                'details' => $body['message'] ?? 'No details provided.',
            ], $response->getStatusCode());
        } catch (\Exception $e) {
            // Handle other errors, like the block not existing
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 500;
            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }
}
