<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    protected MerchantService $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);
    
        $merchant = $this->merchantService->findMerchantByEmail($request->user()->email);
        if (!$merchant) {
            return response()->json(['message' => 'Merchant not found'], 404);
        }
    
        $orders = Order::where('merchant_id', $merchant->id)
            ->whereBetween('created_at', [$validated['from'], $validated['to']])
            ->get();
    
        $count = $orders->count();
    
        // Ensure precision in revenue
        $revenue = $orders->reduce(function ($carry, $order) {
            return bcadd($carry, $order->subtotal, 3);
        }, '0');
    
        // Ensure precision in commissions owed
        $commissionsOwed = $orders->whereNotNull('affiliate_id')->reduce(function ($carry, $order) {
            return bcadd($carry, $order->commission_owed, 3);
        }, '0');
    
        return response()->json([
            'count'            => $count,
            'commissions_owed' => $commissionsOwed,
            'revenue'          => $revenue,
        ]);
    }    
}
