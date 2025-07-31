<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Tenant\UserAdminService;
use GuzzleHttp\Exception\ClientException;

class UserAdminController extends Controller
{
    public $userAdminService;

    /**
     * Class constructor.
     */
    public function __construct(UserAdminService $userAdminService)
    {
        $this->userAdminService = $userAdminService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'   => 'required|string|min:3|max:50|alpha_dash',
            'firstname'  => 'required|string|max:50',
            'lastname'   => 'required|string|max:50',
            'email'      => 'required|email|max:100',
            'password'   => [
                'required',
                'string',
                'min:8'
            ],
            'role_id'    => 'required|integer|min:1',
        ]);

        Log::info('Creating admin user with data: ', $request->all());

        try {
            $respone = $this->userAdminService->storeAdminUser($data);
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
            //$statusCode = str_contains($e->getMessage(), 'already exists') ? 409 : 500;
            return response()->json(['message' => $e->getMessage()]);
        }
    }
}
