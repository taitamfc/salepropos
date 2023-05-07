<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\GeneralSetting;

class GeneralSettingController extends Controller
{
    public function index(Request $request)
    {
        $item = GeneralSetting::find(1);
        return response()->json($item);
    }
    public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $item = GeneralSetting::find(1)->update($data);
        return response()->json($item);
    }
}
