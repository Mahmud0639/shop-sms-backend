<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// প্রতিদিন সকাল ৯টায় শিডিউলড SMS পাঠানোর কমান্ডটি অটোমেটিক চলবে
Schedule::command('sms:send-scheduled')->dailyAt('09:00');