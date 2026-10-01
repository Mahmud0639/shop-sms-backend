<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RechargeHistory;
use App\Models\SmsSchedule;
use App\Models\Shop;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
            'scheduled_time' => 'nullable',
        ]);

        $shop = $request->user();

        $customer = Customer::where('shop_id', $shop->id)
            ->where('id', $request->customer_id)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'কাস্টমার পাওয়া যায়নি।'
            ], 404);
        }

        if (($customer->total_due ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'এই কাস্টমারের কোনো বাকি টাকা নেই!'
            ], 400);
        }

        $existingSchedule = SmsSchedule::where('shop_id', $shop->id)
            ->where('customer_id', $request->customer_id)
            ->where('status', 'pending')
            ->first();

        if ($existingSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'এই কাস্টমারের একটি শিডিউল ইতিমধ্যেই পেন্ডিং রয়েছে!'
            ], 400);
        }

        if (($shop->sms_wallet_balance ?? 0) < 1) {
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
            'scheduled_time' => $request->scheduled_time ?? '10:00:00',
            'status'         => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SMS শিডিউল সফলভাবে সেট করা হয়েছে!',
            'data'    => $schedule->load('customer:id,name,phone')
        ], 201);
    }

    // ২. শিডিউলকৃত SMS লিস্ট ও ওভারভিউ পাওয়া
    public function getSmsSchedules(Request $request)
    {
        $shopId = $request->user()->id;

        $today = now()->toDateString();
        
        $summary = [
            'today_pending' => SmsSchedule::where('shop_id', $shopId)->where('status', 'pending')->whereDate('scheduled_date', $today)->count(),
            'total_pending' => SmsSchedule::where('shop_id', $shopId)->where('status', 'pending')->count(),
            'total_sent'    => SmsSchedule::where('shop_id', $shopId)->where('status', 'sent')->count(),
            'total_failed'  => SmsSchedule::where('shop_id', $shopId)->whereIn('status', ['failed', 'cancelled'])->count(),
        ];

        $query = SmsSchedule::where('shop_id', $shopId)->with('customer:id,name,phone');

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('scheduled_date', 'asc')->latest()->get();

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $schedules
        ]);
    }

    // ৩. পেন্ডিং শিডিউল বাতিল করা
    public function cancelSmsSchedule(Request $request, $id)
    {
        $schedule = SmsSchedule::where('shop_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($schedule->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'শুধুমাত্র পেন্ডিং শিডিউল বাতিল করা সম্ভব!'
            ], 400);
        }

        $schedule->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'SMS শিডিউল সফলভাবে বাতিল করা হয়েছে!'
        ]);
    }

    // ৪. SSLCommerz পেমেন্ট শুরু করা (Initiate Recharge)
    public function rechargeWallet(Request $request)
    {
        $request->validate([
            'package_name' => 'required|string',
            'sms_amount'   => 'required|integer|min:1',
            'price'        => 'required|numeric|min:1',
        ]);

        $shop = $request->user();
        $tran_id = "SMS_" . uniqid();

        // পেন্ডিং হিস্ট্রি এন্ট্রি
        RechargeHistory::create([
            'shop_id'        => $shop->id,
            'package_name'   => $request->package_name,
            'sms_amount'     => $request->sms_amount,
            'price'          => $request->price,
            'transaction_id' => $tran_id,
            'status'         => 'pending',
        ]);

        // SSLCommerz API Payload
$baseUrl = config('app.url'); // https://blazing-awhile-childcare.ngrok-free.dev

$postData = [
    'store_id'         => config('services.sslcommerz.store_id'),
    'store_passwd'     => config('services.sslcommerz.store_password'),
    'total_amount'     => $request->price,
    'currency'         => "BDT",
    'tran_id'          => $tran_id,
    'success_url'      => $baseUrl . '/api/payment/success',
    'fail_url'         => $baseUrl . '/api/payment/fail',
    'cancel_url'       => $baseUrl . '/api/payment/cancel',
    'ipn_url'          => $baseUrl . '/api/payment/ipn',
    'cus_name'         => $shop->name ?? 'Shop Owner',
    'cus_email'        => $shop->email ?? 'shop@example.com',
    'cus_add1'         => 'Bangladesh',
    'cus_phone'        => $shop->phone ?? '01700000000',
    'shipping_method'  => 'NO',
    'product_name'     => $request->package_name,
    'product_category' => 'SMS Topup',
    'product_profile'  => 'non-physical-goods',
    'multi_card_name' => 'bkash', // শুধুমাত্র bKash এলাও করবে
];

        $mode = config('services.sslcommerz.mode');
        $apiUrl = ($mode === 'sandbox')
            ? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php"
            : "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

        $response = Http::asForm()->post($apiUrl, $postData);
        $result = $response->json();

        if (isset($result['status']) && $result['status'] === 'SUCCESS' && !empty($result['GatewayPageURL'])) {
            return response()->json([
                'success'     => true,
                'payment_url' => $result['GatewayPageURL'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'পেমেন্ট গেটওয়ে চালনা করা সম্ভব হচ্ছে না।',
            'error'   => $result,
        ], 400);
    }

 // ৫. পেমেন্ট সফল হলে (Callback / IPN)
   public function handleSuccess(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $val_id = $request->input('val_id');
        $card_type = $request->input('card_type');

        $recharge = RechargeHistory::where('transaction_id', $tran_id)->first();

        if ($recharge && $recharge->status === 'pending') {
            DB::transaction(function () use ($recharge, $val_id, $card_type) {
                $recharge->update([
                    'status'         => 'success',
                    'val_id'         => $val_id,
                    'payment_method' => $card_type,
                ]);

                // দোকানের ওয়ালেট ব্যালেন্স যোগ করা
                $shop = Shop::find($recharge->shop_id);
                if ($shop) {
                    $shop->increment('sms_wallet_balance', $recharge->sms_amount);
                }
            });
        }

        // response()->html() এর বদলে সরাসরি response() ব্যবহার করুন
        return response('
            <!DOCTYPE html>
            <html>
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Payment Successful</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 40px 20px; background: #f4f6f8; }
                    .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: inline-block; }
                    h2 { color: #2e7d32; margin-bottom: 10px; }
                    p { color: #555; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>পেমেন্ট সফল হয়েছে!</h2>
                    <p>আপনার SMS ওয়ালেট ব্যালেন্স সফলভাবে যুক্ত করা হয়েছে।</p>
                    <p><small>অনুগ্রহ করে অপেক্ষা করুন, অ্যাপে ফিরে যাওয়া হচ্ছে...</small></p>
                </div>
            </body>
            </html>
        ', 200)->header('Content-Type', 'text/html');
    }

    // ৬. পেমেন্ট ব্যর্থ হলে
   public function handleFail(Request $request)
    {
        $tran_id = $request->input('tran_id');
        RechargeHistory::where('transaction_id', $tran_id)->update(['status' => 'failed']);

        return response('
            <!DOCTYPE html>
            <html>
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Payment Failed</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 40px 20px; background: #f4f6f8; }
                    .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: inline-block; }
                    h2 { color: #c62828; margin-bottom: 10px; }
                    p { color: #555; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>পেমেন্ট ব্যর্থ হয়েছে!</h2>
                    <p>অনুগ্রহ করে আবার চেষ্টা করুন।</p>
                </div>
            </body>
            </html>
        ', 200)->header('Content-Type', 'text/html');
    }

    // ৭. পেমেন্ট বাতিল হলে
   public function handleCancel(Request $request)
    {
        $tran_id = $request->input('tran_id');
        RechargeHistory::where('transaction_id', $tran_id)->update(['status' => 'cancelled']);

        return response('
            <!DOCTYPE html>
            <html>
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Payment Cancelled</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 40px 20px; background: #f4f6f8; }
                    .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: inline-block; }
                    h2 { color: #ef6c00; margin-bottom: 10px; }
                    p { color: #555; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>পেমেন্ট বাতিল করা হয়েছে।</h2>
                </div>
            </body>
            </html>
        ', 200)->header('Content-Type', 'text/html');
    }

    // ১. ওয়ালেট ব্যালেন্স ও সাম্প্রতিক ৫টি রিচার্জ ইতিহাস পাওয়ার মেথড
    public function getWalletInfo(Request $request)
    {
        $shop = $request->user();

        $history = RechargeHistory::where('shop_id', $shop->id)
            ->where('status', 'success')
            ->orderBy('id', 'desc')
            ->take(5) // প্রধান পেজের জন্য ৫টি আপডেট রাখা হলো
            ->get();

        return response()->json([
            'success'            => true,
            'sms_wallet_balance' => $shop->sms_wallet_balance ?? 0,
            'recharge_history'   => $history
        ]);
    }

    // ২. আগের সব রিচার্জ ইতিহাস পাওয়ার জন্য পেজিনেটেড API মেথড
    public function getAllRechargeHistory(Request $request)
    {
        $shop = $request->user();

        $history = RechargeHistory::where('shop_id', $shop->id)
            ->where('status', 'success')
            ->orderBy('id', 'desc')
            ->paginate(15); // প্রতি পেজে ১৫টি করে ডাটা লোড হবে

        return response()->json([
            'success' => true,
            'data'    => $history
        ]);
    }

    // ২. ডাইনামিক প্যাকেজ পাওয়ার মেথড
    public function getSmsPackages()
    {
        $packages = \App\Models\SmsPackage::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data'    => $packages
        ]);
    }
}