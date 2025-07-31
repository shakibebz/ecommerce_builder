<?php

namespace App\Http\Controllers\Api\V1\Tenant;


use App\Http\Controllers\Controller;
use App\Services\MagentoApiService;


class MagentoInvoiceController extends Controller
{
    protected $client;
    protected $magentoApi;

    public function __construct(MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }

    public function getInvoices()
    {
        $token = $this->magentoApi->getAccessToken();

        $url = "{$this->magentoApi->apiUrl}/rest/V1/invoices";

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to fetch invoices'], 500);
        }

        return response()->json($response->json());
    }

}


