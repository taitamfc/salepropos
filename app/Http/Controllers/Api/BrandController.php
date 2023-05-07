<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Brand;
use App\Http\Resources\BrandResource;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query(true)->orderBy('id','DESC');
        $items = $this->handleFilter($query,$request);
        return BrandResource::collection($items);
    }
	public function show($id)
    {
        $item = Brand::find($id);
        return new BrandResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Brand::create($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Brand::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Brand::findOrFail($id);
        $item->is_active = false;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Brand::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
