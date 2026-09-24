<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazila;

class LocationController extends Controller
{
    public function getDivisions()
    {
        return response()->json(['success' => true, 'data' => Division::all()]);
    }

    public function getDistricts($division_id)
    {
        $districts = District::where('division_id', $division_id)->get();
        return response()->json(['success' => true, 'data' => $districts]);
    }

    public function getUpazilas($district_id)
    {
        $upazilas = Upazila::where('district_id', $district_id)->get();
        return response()->json(['success' => true, 'data' => $upazilas]);
    }
}