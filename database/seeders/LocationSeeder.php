<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;
use App\Models\District;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // ১. ঢাকা বিভাগ ও তার কয়েকটি প্রধান জেলা
        $dhaka = Division::create(['name' => 'ঢাকা']);
        District::create(['division_id' => $dhaka->id, 'name' => 'ঢাকা']);
        District::create(['division_id' => $dhaka->id, 'name' => 'গাজীপুর']);
        District::create(['division_id' => $dhaka->id, 'name' => 'নারায়ণগঞ্জ']);

        // ২. চট্টগ্রাম বিভাগ ও তার কয়েকটি প্রধান জেলা
        $ctg = Division::create(['name' => 'চট্টগ্রাম']);
        District::create(['division_id' => $ctg->id, 'name' => 'চট্টগ্রাম']);
        District::create(['division_id' => $ctg->id, 'name' => 'কক্সবাজার']);

        // ৩. রাজশাহী বিভাগ ও জেলা
        $raj = Division::create(['name' => 'রাজশাহী']);
        District::create(['division_id' => $raj->id, 'name' => 'রাজশাহী']);
        District::create(['division_id' => $raj->id, 'name' => 'বগুড়া']);

        // ৪. সিলেট বিভাগ ও জেলা
        $sylhet = Division::create(['name' => 'সিলেট']);
        District::create(['division_id' => $sylhet->id, 'name' => 'সিলেট']);
    }
}