<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Supplier;
use App\Http\Resources\SupplierResource;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query(true)->orderBy('id','DESC');
        $items = $this->handleFilter($query,$request);
        return SupplierResource::collection($items);
    }
	public function show($id)
    {
        $item = Supplier::find($id);
        return new SupplierResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Supplier::create($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Supplier::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Supplier::findOrFail($id);
        $item->is_active = false;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Supplier::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
