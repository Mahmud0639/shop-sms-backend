<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RechargeHistory;
use App\Models\SmsSchedule;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsWalletController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    // ১. কাস্টমারের জন্য SMS শিডিউল সেট করা
    public function scheduleSms(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'message_body'   => 'required|string',
            'scheduled_date' => 'required|date|after_or_equal:today',
        ]);

        $shop = $request->user();

        // ওয়ালেটে পর্যাপ্ত SMS ব্যালেন্স আছে কিনা তা চেক করা
        if ($shop->sms_wallet_balance < 1) {
            return response()->json([
                'success' => false,
                'message' => 'আপনার ওয়ালেটে পর্যাপ্ত SMS ব্যালেন্স নেই। অনুগ্রহ করে রিচার্জ করুন।'
            ], 400);
        }

        $schedule = SmsSchedule::create([
            'shop_id'        => $shop->id,
            'customer_id'    => $request->customer_id,
            'message_body'   => $request->message_body,
            'scheduled_date' => $request->scheduled_date,
            'status'         => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SMS শিডিউল সফলভাবে সেট করা হয়েছে!',
            'data'    => $schedule
        ]);
    }

    // ২. ওয়ালেট রিচার্জ এন্ট্রি (Bkash / Nagad TrxID সফল হওয়ার পর)
    public function rechargeWallet(Request $request)
    {
        $request->validate([
            'package_name'   => 'required|string',
            'sms_amount'     => 'required|integer|min:1',
            'price'          => 'required|numeric|min:1',
            'payment_method' => 'required|in:bkash,nagad,rocket',
            'transaction_id' => 'required|string',
        ]);

        $shop = $request->user();

        DB::transaction(function () use ($request, $shop) {
            // ১. রিচার্জ হিস্ট্রি সেভ করা
            RechargeHistory::create([
                'shop_id'        => $shop->id,
                'package_name'   => $request->package_name,
                'sms_amount'     => $request->sms_amount,
                'price'          => $request->price,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'status'         => 'success',
            ]);

            // ২. শপের ওয়ালেট ব্যালেন্স বাড়ানো
            $shop->increment('sms_wallet_balance', $request->sms_amount);
        });

        return response()->json([
            'success' => true,
            'message' => 'রিচার্জ সফল হয়েছে!',
            'current_balance' => $shop->fresh()->sms_wallet_balance
        ]);
    }
}