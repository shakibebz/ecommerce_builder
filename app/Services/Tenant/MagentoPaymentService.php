<?php

namespace App\Services\Tenant;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class MagentoPaymentService
{
    protected  $client;
    protected  $apiUrl;
    protected  $username;
    protected  $password;
    protected  $token = null;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = config('magento.api_url');
        $this->username = config('magento.username');
        $this->password = config('magento.password');
    }

    // Get or reuse access token
    protected function getAccessToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $response = $this->client->post("{$this->apiUrl}/rest/V1/integration/admin/token", [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);
        $this->token = json_decode($response->getBody(), true);
        return $this->token;
    }

    public function getAllEnabledPaymentMethods()
    {
        try {
            $this->token = $this->getAccessToken();


            $response = Http::withToken($this->token)
                ->get("{$this->apiUrl}/rest/V1/configuration");

            if (!$response->successful()) {
                return ['error' => 'Failed to fetch Magento configuration'];
            }

            $allConfig = $response->json();
            $enabledMethods = [];

            foreach ($allConfig as $key => $value) {
                if (str_starts_with($key, 'payment/') && str_ends_with($key, '/active') && $value == '1') {
                    $parts = explode('/', $key);
                    $enabledMethods[] = $parts[1]; // e.g., "checkmo"
                }
            }

            return $enabledMethods;
        } catch (\Exception $ex) {
            response()->json([
                'error' => 'Failed to fetch payment methods',
                'message' => $ex->getMessage(),
            ], 500);
        }
    }
}
