<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Warehouse;
use App\Http\Resources\WarehouseResource;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::query(true)->orderBy('id','ASC');
        $items = $this->handleFilter($query,$request);
        return WarehouseResource::collection($items);
    }
	public function show($id)
    {
        $item = Warehouse::find($id);

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Warehouse::create($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Warehouse::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Warehouse::findOrFail($id);
        $item->is_active = false;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Warehouse::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
