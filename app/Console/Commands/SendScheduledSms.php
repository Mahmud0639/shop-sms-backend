<?php

namespace App\Console\Commands;

use App\Models\SmsSchedule;
use App\Services\SmsService;
use Illuminate\Console\Command;

class SendScheduledSms extends Command
{
    protected $signature = 'sms:send-scheduled';
    protected $description = 'আজকের তারিখ অনুযায়ী শিডিউল করা SMS কাস্টমারদের পাঠানো হবে';

    public function handle(SmsService $smsService)
    {
        $schedules = SmsSchedule::where('scheduled_date', now()->toDateString())
            ->where('status', 'pending')
            ->get();

        foreach ($schedules as $item) {
            $shop = $item->shop;
            $customer = $item->customer;

            // ওয়ালেট ব্যালেন্স চেক
            if ($shop && $shop->sms_wallet_balance > 0) {
                $isSent = $smsService->sendSms($customer->phone, $item->message_body);

                if ($isSent) {
                    $item->update(['status' => 'sent']);
                    $shop->decrement('sms_wallet_balance', 1); // ওয়ালেট থেকে ১টি SMS ডিডাক্ট
                } else {
                    $item->update(['status' => 'failed']);
                }
            } else {
                $item->update(['status' => 'failed']);
            }
        }
    }
}