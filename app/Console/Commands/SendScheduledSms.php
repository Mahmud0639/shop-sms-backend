<?php

namespace App\Console\Commands;

use App\Models\SmsSchedule;
use App\Services\SmsService;
use Illuminate\Console\Command;

class SendScheduledSms extends Command
{
    protected $signature = 'sms:send-scheduled';
    protected $description = 'নির্দিষ্ট সময় অনুযায়ী শিডিউলকৃত SMS গুলো স্বয়ংক্রিয়ভাবে পাঠাবে';

    public function handle()
    {
        // বর্তমান তারিখ এবং সময় (ঘণ্টা অনুযায়ী)
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:00');

        // পেন্ডিং শিডিউলগুলো বের করা (যার তারিখ পার হয়ে গেছে বা আজকের এই সময়ের মধ্যে)
        $schedules = SmsSchedule::with(['shop', 'customer'])
            ->where('status', 'pending')
            ->where(function ($query) use ($today, $currentTime) {
                $query->whereDate('scheduled_date', '<', $today)
                      ->orWhere(function ($q) use ($today, $currentTime) {
                          $q->whereDate('scheduled_date', $today)
                            ->where('scheduled_time', '<=', $currentTime);
                      });
            })
            ->get();

        foreach ($schedules as $schedule) {
            $shop = $schedule->shop;
            $customer = $schedule->customer;

            // ওয়ালেট ব্যালেন্স চেক (ব্যালেন্স না থাকলে Failed হবে)
            if (($shop->sms_wallet_balance ?? 0) < 1) {
                $schedule->update(['status' => 'failed']);
                continue;
            }

            // TODO: রিয়েল SMS পাঠানোর API কল
            // SmsService::send($customer->phone, $schedule->message_body);

            // স্ট্যাটাস আপডেট ও ওয়ালেট থেকে ১টি SMS বিয়োগ
            $schedule->update(['status' => 'sent']);
            $shop->decrement('sms_wallet_balance', 1);
        }

        $this->info('Scheduled SMS process completed!');
    }
}