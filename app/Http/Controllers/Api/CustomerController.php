<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // কাস্টমারদের লিস্ট পাওয়া (শপ অনুযায়ী)
   // কাস্টমারদের লিস্ট পাওয়া (সার্চ সাপোর্টসহ)
    public function index(Request $request)
    {
        $query = Customer::where('shop_id', $request->user()->id);

        // সার্চ কুয়েরি থাকলে ফিল্টার হবে
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
                'total_due'  => $customer->total_due, // ডাইনামিক মোট বাকি
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
                'id'              => $customer->id,
                'name'            => $customer->name,
                'phone'           => $customer->phone,
                'address'         => $customer->address,
                'total_due'       => $customer->total_due, // ডাইনামিক মোট বাকি
                'credit_limit'    => $customer->credit_limit,
                'transactions'    => $customer->transactions,
            ]
        ]);
    }

    // কাস্টমারের জন্য নতুন ট্রানজেকশন (বাকি/জমা) সেভ করা
    public function addTransaction(Request $request, $id)
    {
        $customer = Customer::where('shop_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'type'   => 'required|in:due,paid',
            'amount' => 'required|numeric|min:1',
            'date'   => 'nullable|date',
        ]);

        $transaction = $customer->transactions()->create([
            'type'   => $request->type,
            'amount' => $request->amount,
            'date'   => $request->date ?? now()->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->type === 'due' ? 'বাকি সেভ করা হয়েছে!' : 'টাকা জমা নেওয়া হয়েছে!',
            'data'    => $transaction,
            'current_total_due' => $customer->total_due,
        ], 201);
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
            'shop_id'         => $request->user()->id, // Sanctum Auth User ID
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
}