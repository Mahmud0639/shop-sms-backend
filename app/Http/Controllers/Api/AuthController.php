<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ১. দোকানদার রেজিস্ট্রেশন API
    public function register(Request $request)
    {
        $request->validate([
            'owner_name' => 'required|string',
            'shop_name'  => 'required|string',
            'phone'      => 'required|string|unique:shops,phone',
            'pin'        => 'required|string|min:4',
            'division_id'=> 'nullable',
            'district_id'=> 'nullable',
            'upazila_id' => 'nullable',
        ]);

        $shop = Shop::create([
            'owner_name'  => $request->owner_name,
            'shop_name'   => $request->shop_name,
            'phone'       => $request->phone,
            'pin'         => Hash::make($request->pin),
            'division_id' => $request->division_id,
            'district_id' => $request->district_id,
            'upazila_id'  => $request->upazila_id,
            'address'     => $request->address,
            'sms_wallet_balance' => 10, // ওয়েলকাম বোনাস ১০ SMS
        ]);

        $token = $shop->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'নিবন্ধন সফল হয়েছে!',
            'token'   => $token,
            'data'    => $shop
        ], 201);
    }

    // ২. লগইন API
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'pin'   => 'required|string',
        ]);

        $shop = Shop::where('phone', $request->phone)->first();

        if (!$shop || !Hash::check($request->pin, $shop->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'মোবাইল নম্বর অথবা পিন সঠিক নয়।'
            ], 401);
        }

        if ($shop->status === 'blocked') {
            return response()->json([
                'success' => false,
                'is_blocked' => true,
                'message' => 'আপনার অ্যাকাউন্টটি বন্ধ রয়েছে।',
                'reason'  => $shop->block_reason
            ], 403);
        }

        $token = $shop->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'লগইন সফল হয়েছে!',
            'token'   => $token,
            'data'    => $shop
        ]);
    }
}