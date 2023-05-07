<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Unit;
use App\Http\Resources\UnitResource;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query(true)->orderBy('id','DESC');
        $items = $this->handleFilter($query,$request);
        return UnitResource::collection($items);
    }
	public function show($id)
    {
        $item = Unit::find($id);
        return new UnitResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Unit::create($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Unit::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $lims_unit_data = Unit::findOrFail($id);
        $lims_unit_data->is_active = false;
        $lims_unit_data->save();
        return response()->json([
            'success' => true,
            'data' => $lims_unit_data
        ]);
    }
    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Unit::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
