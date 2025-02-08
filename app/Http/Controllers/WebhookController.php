<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'order_id' => 'required|string',
                'subtotal_price' => 'required|numeric',
                'merchant_domain' => 'required|string',
                'discount_code' => 'nullable|string',
                'customer_email' => 'nullable|email',
                'customer_name' => 'nullable|string',
            ]);

            $this->orderService->processOrder($data);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
