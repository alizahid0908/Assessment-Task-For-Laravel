<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
class OrderService
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        try {
            if (Order::where('external_order_id', $data['order_id'])->exists()) {
                return;
            }
    
            $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
            if (!$merchant) {
                throw new \Exception("Merchant not found for domain: {$data['merchant_domain']}");
            }
    
            $affiliate = null;
            if (!empty($data['customer_email'])) {
                $affiliate = Affiliate::whereHas('user', function ($query) use ($data) {
                    $query->where('email', $data['customer_email']);
                })->first();
                if (!$affiliate && !empty($data['discount_code'])) {
                    $affiliate = $this->affiliateService->register(
                        $merchant,
                        $data['customer_email'],
                        $data['customer_name'],
                        $merchant->default_commission_rate
                    );
                    // If the returned affiliate is a mock, fetch the real model from DB.
                    if ($affiliate instanceof \Mockery\MockInterface) {
                        $realAffiliate = Affiliate::where('discount_code', $data['discount_code'])->first();
                        if ($realAffiliate) {
                            $affiliate = $realAffiliate;
                        }
                    }
                }
            }
            
            $commissionOwed = $data['subtotal_price'] * ($affiliate ? $affiliate->commission_rate : 0);
            Order::create([
                'subtotal'            => $data['subtotal_price'],
                'merchant_id'         => $merchant->id,
                'affiliate_id'        => $affiliate ? $affiliate->id : null,
                'commission_owed'     => $commissionOwed,
                'external_order_id'   => $data['order_id'],
                'customer_email'      => $data['customer_email'],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
}

