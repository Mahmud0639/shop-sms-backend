<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\SmsService;
use App\Models\SmsLog; // ফাইলের একদম উপরে যুক্ত করুন

class CustomerController extends Controller
{
    // কাস্টমারদের লিস্ট পাওয়া (সার্চ সাপোর্টসহ)
    public function index(Request $request)
    {
        $query = Customer::where('shop_id', $request->user()->id);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->latest()->get()->map(function ($customer) {
            return [
                'id'         => $customer->id,
                'name'       => $customer->name,
                'phone'      => $customer->phone,
                'address'    => $customer->address,
                'total_due'  => $customer->total_due,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $customers
        ]);
    }

    // কাস্টমারের ডিটেইলস ও লেনদেনের তালিকা
    public function show(Request $request, $id)
    {
        $customer = Customer::where('shop_id', $request->user()->id)
            ->with(['transactions' => function ($q) {
                $q->latest();
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $customer->id,
                'name'         => $customer->name,
                'phone'        => $customer->phone,
                'address'      => $customer->address,
                'total_due'    => $customer->total_due,
                'credit_limit' => $customer->credit_limit,
                'transactions' => $customer->transactions,
            ]
        ]);
    }

    // কাস্টমারের জন্য নতুন ট্রানজেকশন (বাকি/জমা) সেভ করা + অটোমেটিক SMS লজিক (Mock Mode)
    public function addTransaction(Request $request, $id)
    {
        $shop = $request->user();
        $customer = Customer::where('shop_id', $shop->id)->findOrFail($id);

        $request->validate([
            'type'   => 'required|in:due,paid',
            'amount' => 'required|numeric|min:1',
            'date'   => 'nullable|date',
        ]);

        // ১. ট্রানজেকশন সেভ করা
        $transaction = $customer->transactions()->create([
            'type'   => $request->type,
            'amount' => $request->amount,
            'date'   => $request->date ?? now()->toDateString(),
        ]);

        // কাস্টমারের আপডেট হওয়া মোট বাকি
        $newTotalDue = $customer->fresh()->total_due;

        // ২. অটোমেটিক SMS প্রসেসিং
        $smsSent = false;
        $smsMessage = null;

        if (($shop->sms_wallet_balance ?? 0) >= 1) {
            // ডায়নামিক মেসেজ ফরম্যাটিং
            if ($request->type === 'due') {
                $smsMessage = "প্রিয় {$customer->name}, {$shop->shop_name}-এ ৳{$request->amount} এর নতুন বাকি যোগ করা হয়েছে। আপনার বর্তমান মোট বাকি ৳{$newTotalDue}। ধন্যবাদ।";
            } else {
                $smsMessage = "প্রিয় {$customer->name}, {$shop->shop_name}-এ ৳{$request->amount} জমা নেওয়া হয়েছে। আপনার বর্তমান মোট বাকি ৳{$newTotalDue}। ধন্যবাদ।";
            }

            // TODO: এখানে রিয়েল SMS API কল যুক্ত হবে (যেমন: BulkSMSBD, Greenweb)
            // SmsGatewayService::send($customer->phone, $smsMessage);

            // ওয়ালেট থেকে ১টি SMS বিয়োগ
            $shop->decrement('sms_wallet_balance', 1);
            $smsSent = true;

            SmsLog::create([
        'shop_id'     => $shop->id,
        'customer_id' => $customer->id,
        'phone'       => $customer->phone,
        'message'     => $smsMessage,
        'type'        => $request->type === 'due' ? 'transaction_due' : 'transaction_paid',
        'status'      => 'sent',
    ]);



        }

        return response()->json([
            'success'           => true,
            'message'           => $request->type === 'due' ? 'বাকি সেভ করা হয়েছে!' : 'টাকা জমা নেওয়া হয়েছে!',
            'data'              => $transaction,
            'current_total_due' => $newTotalDue,
            'auto_sms'          => [
                'sent'          => $smsSent,
                'message'       => $smsMessage,
                'remaining_sms' => $shop->fresh()->sms_wallet_balance ?? 0,
            ]
        ], 201);
    }

    // ম্যানুয়ালি বাকির তাগাদা SMS পাঠানো
    public function sendReminderSms(Request $request, $id)
    {
        $shop = $request->user();
        $customer = Customer::where('shop_id', $shop->id)->findOrFail($id);

        if (($shop->sms_wallet_balance ?? 0) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'আপনার SMS ওয়ালেটে পর্যাপ্ত ব্যালেন্স নেই! রিচার্জ করুন।',
            ], 400);
        }

        $message = "প্রিয় {$customer->name}, {$shop->shop_name}-এ আপনার বর্তমান মোট বাকি ৳{$customer->total_due}। দ্রুত পরিশোধ করার অনুরোধ করা হচ্ছে। ধন্যবাদ।";

        // TODO: রিয়েল SMS API ইন্টিগ্রেশন
        // SmsGatewayService::send($customer->phone, $message);

        $shop->decrement('sms_wallet_balance', 1);


        SmsLog::create([
    'shop_id'     => $shop->id,
    'customer_id' => $customer->id,
    'phone'       => $customer->phone,
    'message'     => $message,
    'type'        => 'manual_reminder',
    'status'      => 'sent',
]);


        return response()->json([
            'success'       => true,
            'message'       => 'তাগাদা SMS সফলভাবে পাঠানো হয়েছে!',
            'sms_message'   => $message,
            'remaining_sms' => $shop->fresh()->sms_wallet_balance,
        ]);
    }

    // নতুন কাস্টমার যোগ করা
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string',
            'phone'           => 'required|string',
            'alternate_phone' => 'nullable|string',
            'address'         => 'nullable|string',
            'opening_due'     => 'nullable|numeric',
            'credit_limit'    => 'nullable|numeric',
            'nid_number'      => 'nullable|string',
            'reference_name'  => 'nullable|string',
            'reference_phone' => 'nullable|string',
            'note'            => 'nullable|string',
        ]);

        $customer = Customer::create([
            'shop_id'         => $request->user()->id,
            'name'            => $request->name,
            'phone'           => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'address'         => $request->address,
            'opening_due'     => $request->opening_due ?? 0,
            'credit_limit'    => $request->credit_limit,
            'nid_number'      => $request->nid_number,
            'reference_name'  => $request->reference_name,
            'reference_phone' => $request->reference_phone,
            'note'            => $request->note,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'কাস্টমার সফলভাবে যোগ করা হয়েছে!',
            'data'    => $customer
        ], 201);
    }

    // একসাথে একাধিক বা সব বাকিদারকে তাগাদা SMS পাঠানো
public function sendBulkReminderSms(Request $request, SmsService $smsService)
{
    $shop = $request->user();

    $request->validate([
        'customer_ids' => 'nullable|array',
        'customer_ids.*' => 'exists:customers,id',
        'all_due_customers' => 'nullable|boolean',
    ]);

    // কাস্টমার কোয়েরি
    $query = Customer::where('shop_id', $shop->id);

    if ($request->all_due_customers) {
    // সব বাকিদার যাদের প্রকৃত বাকি > ০
    $query->where(function($q) {
        $q->where('total_due', '>', 0)
          ->orWhere('opening_due', '>', 0);
    });
} elseif (!empty($request->customer_ids)) {
        $query->whereIn('id', $request->customer_ids);
    } else {
        return response()->json([
            'success' => false,
            'message' => 'কোনো কাস্টমার নির্বাচন করা হয়নি!'
        ], 400);
    }

    $customers = $query->get();
    $totalCustomers = $customers->count();

    if ($totalCustomers === 0) {
        return response()->json([
            'success' => false,
            'message' => 'কোনো কাস্টমার পাওয়া যায়নি!'
        ], 400);
    }

    // ওয়ালেট চেক
    if (($shop->sms_wallet_balance ?? 0) < $totalCustomers) {
        return response()->json([
            'success' => false,
            'message' => "পর্যাপ্ত SMS ব্যালেন্স নেই! প্রয়োজন: {$totalCustomers}টি, আপনার আছে: {$shop->sms_wallet_balance}টি।",
        ], 400);
    }

    $sentCount = 0;

    foreach ($customers as $customer) {
        if ($customer->total_due > 0 && $customer->phone) {
            $message = "প্রিয় {$customer->name}, {$shop->shop_name}-এ আপনার বর্তমান মোট বাকি ৳{$customer->total_due}। দ্রুত পরিশোধের জন্য অনুরোধ করা হচ্ছে। ধন্যবাদ।";
            
            // রিয়েল SMS পাঠানো
            $isSent = $smsService->sendSms($customer->phone, $message);
            if ($isSent) {
                $sentCount++;


                SmsLog::create([
                'shop_id'     => $shop->id,
                'customer_id' => $customer->id,
                'phone'       => $customer->phone,
                'message'     => $message,
                'type'        => 'bulk_reminder',
                'status'      => 'sent',
            ]);

            }
        }
    }

    // ওয়ালেট থেকে ব্যালেন্স বিয়োগ
    if ($sentCount > 0) {
        $shop->decrement('sms_wallet_balance', $sentCount);
    }

    return response()->json([
        'success' => true,
        'message' => "সফলভাবে {$sentCount} জন কাস্টমারকে তাগাদা SMS পাঠানো হয়েছে!",
        'remaining_sms' => $shop->fresh()->sms_wallet_balance,
    ]);
}
}