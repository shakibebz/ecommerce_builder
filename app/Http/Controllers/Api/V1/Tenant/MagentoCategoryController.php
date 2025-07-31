<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Services\MagentoApiService;
use App\Services\MagentoSyncService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class MagentoCategoryController extends Controller
{
    protected $client;
    protected $magentoApi;

    public function __construct(MagentoApiService $magentoApi)
    {
         $this->magentoApi = $magentoApi;
    }


    /**
     * @throws Exception
     */
    public function getCategory($id)
    {


        $token = $this->magentoApi->getAccessToken();

        $url="{$this->magentoApi->apiUrl}/rest/V1/categories/{$id}";

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
        ]);

        return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
    }


    /**
     * @throws Exception
     */
    public function createCategory(Request $request)
    {
        $token = $this->magentoApi->getAccessToken();

        $storeCode = $request->input('store_code', 'default');
        $rootCategoryId = $this->getRootCategoryByStoreCode($storeCode);

        if (!$rootCategoryId) {
            return response()->json(['error' => 'Invalid store code or root category not found'], 400);
        }

        $name = $request->input('name');
        if (!$name) {
            return response()->json(['error' => 'The "name" field is required.'], 422);
        }

        $request->validate([
            'store_code' => 'required|string',
            'name' => 'required|string|max:255',
        ]);


        $data = [
            'category' => [
                'name' => $request->input('name'),
                'is_active' => true,
                'parent_id' => $rootCategoryId,
                'include_in_menu' => true,
            ]
        ];

        \Log::info('data is'.json_encode($data, JSON_PRETTY_PRINT));


        $response = $this->client->post("{$this->magentoApi->apiUrl}/rest/V1/categories", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());

    }


    /**
     * @throws Exception
     */
    public function updateCategory(Request $request, $id)
    {
        $token = $this->magentoApi->getAccessToken();
        $data = [
            'category' => [
                'id' => $id,
                'name' => $request->name,
                'isActive' => $request->is_active,
                'include_in_menu' => $request->include_in_menu ?? true,
            ]
        ];

        $response = $this->client->put("{$this->magentoApi->apiUrl}/rest/V1/categories/{$id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);


        return response()->json(json_decode($response->getBody(), true), $response->getStatusCode());
    }


    protected function getRootCategoryByStoreCode(string $storeCode): ?int
    {

        $service= new MagentoSyncService($this->magentoApi);
        $storeGroups = $service->getStoreGroups( $this->magentoApi);


        $storeList = [
            'admin' => 1,
            'en_us' => 2,
            'en' => 3
        ];

        $groupId = $storeList[$storeCode] ?? null;

        if (!$groupId) return null;


        $group = collect($storeGroups)->firstWhere('id', $groupId);

        return $group['root_category_id'] ?? null;
    }

}
