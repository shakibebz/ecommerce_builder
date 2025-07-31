<?php

namespace App\Services\Tenant;

use App\Services\MagentoApiService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class UserAdminService
{

    protected  MagentoApiService $magentoApi;

    public function __construct(MagentoApiService $magentoApi)
    {
        $this->magentoApi = $magentoApi;
    }


    public function storeAdminUser(array $userData): array
    {
        $token = $this->magentoApi->getAccessToken();

        Log::info('Creating admin user with data: ', $userData);

        $payload = [
            'user' => [
                'username'   => $userData['username'],
                'firstname'  => $userData['firstname'],
                'lastname'   => $userData['lastname'],
                'email'      => $userData['email'],
                'password'   => $userData['password'],
                'interface_locale' => 'en_US',
                'is_active'  => 1,
            ]
        ];


        try {
            $endpoint = "{$this->magentoApi->apiUrl}/rest/V1/users";

            $response = $this->magentoApi->client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $createdUser =  json_decode($response->getBody(), true);

            return $createdUser;
        } catch (\Exception $e) {
            // Correctly access the nested username key
            Log::info("The user with the username '" . $payload['user']['username'] . "' could not be created.");

            // It's also highly recommended to log the actual error from Magento!
            Log::error("Magento API Error: " . $e->getMessage());

            throw $e;
        }
    }
}
