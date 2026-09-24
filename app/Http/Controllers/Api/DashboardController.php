<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getSummary(Request $request)
    {
        $shop = $request->user();

        // কাস্টমারদের মোট প্রারম্ভিক বাকি (পরবর্তীতে ট্রানজেকশনসহ হিসাব হবে)
        $totalOpeningDue = Customer::where('shop_id', $shop->id)->sum('opening_due');

        // আজকের মোট পাওনা এন্ট্রি (Transactions টেবিল থেকে)
        $todayDue = Transaction::whereHas('customer', function($q) use ($shop) {
            $q->where('shop_id', $shop->id);
        })->where('type', 'due')->whereDate('created_at', now())->sum('amount');

        // আজকের মোট আদায় (Transactions টেবিল থেকে)
        $todayPaid = Transaction::whereHas('customer', function($q) use ($shop) {
            $q->where('shop_id', $shop->id);
        })->where('type', 'paid')->whereDate('created_at', now())->sum('amount');

       // সাম্প্রতিক কাস্টমার
        $recentCustomers = Customer::where('shop_id', $shop->id)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'id'        => $customer->id,
                    'name'      => $customer->name,
                    'phone'     => $customer->phone,
                    'total_due' => $customer->total_due, // ডাইনামিক ট্রানজেকশনসহ মোট বাকি
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'sms_wallet_balance' => $shop->sms_wallet_balance ?? 0,
                'total_due'          => $totalOpeningDue,
                'today_due'          => $todayDue ?? 0,
                'today_paid'         => $todayPaid ?? 0,
                'recent_customers'   => $recentCustomers,
            ]
        ]);
    }
}