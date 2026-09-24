<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('SMS_API_KEY', 'your_default_api_key');
        $this->apiUrl = env('SMS_API_URL', 'https://api.greenweb.com.bd/api.php');
    }

    /**
     * বাল্ক এসএমএস পাঠানোর ফাংশন
     */
    public function sendSms($to, $message)
    {
        // বাল্ক এসএমএস প্রোভাইডারের API রিকোয়েস্ট
        $response = Http::post($this->apiUrl, [
            'token' => $this->apiKey,
            'to'    => $to,
            'message' => $message,
        ]);

        return $response->successful();
    }
}