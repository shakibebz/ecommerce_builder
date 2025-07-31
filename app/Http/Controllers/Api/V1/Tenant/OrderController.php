<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\GuzzleException;
use App\Services\Tenant\MagentoOrderService;

class OrderController extends Controller
{

    public $magentoOrderService;

    /**
     * Class constructor.
     */
    public function __construct(MagentoOrderService $magentoOrderService)
    {
        $this->magentoOrderService = $magentoOrderService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $storeId = $request->store_id;

        try {
            return $this->magentoOrderService->getOrdersForStore($storeId);
        } catch (GuzzleException $e) {
            // Handle API connection errors, token failures, etc.
            Log::error('Failed to fetch orders from Magento.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
