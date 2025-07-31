<?php

namespace App\Http\Controllers\Api\V1\Tenant\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Tenant\MagentoPaymentService;

class ListPaymentController extends Controller
{

    public $paymentService;

    public function __construct(MagentoPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function listPaymentMethods()
    {
        $methods = $this->paymentService->getAllEnabledPaymentMethods();

        return response()->json($methods);
    }
}
