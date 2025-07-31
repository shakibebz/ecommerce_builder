<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Session;

class MagentoApiService
{
    public $client;
    public $apiUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = config('magento.api_url');
        $this->username = config('magento.username');
        $this->password = config('magento.password');
    }

    public function getAccessToken()
    {
        // Check if token exists in session and is not expired
        $token = Session::get('magento_token');
        $tokenExpiry = Session::get('magento_token_expiry');

        if ($token && $tokenExpiry && now()->timestamp < $tokenExpiry) {
            return $token;
        }

        // Request new token if none exists or expired
        try {
            $response = $this->client->post("{$this->apiUrl}rest/V1/integration/admin/token", [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);

            $token = json_decode((string) $response->getBody(), true);

            // Store token and expiry in session (e.g., 24 hours = 86400 seconds)
            Session::put('magento_token', $token);
            Session::put('magento_token_expiry', now()->addSeconds(86400)->timestamp);

            return $token;
        } catch (\Exception $e) {
            // Handle error (e.g., log it or throw custom exception)
            throw new \Exception('Failed to retrieve Magento access token: ' . $e->getMessage());
        }
    }
}
