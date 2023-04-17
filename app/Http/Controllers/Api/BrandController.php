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
        $limit = $request->limit ?? 20;
        $items = Brand::query(true);
        if( $limit != -1 ){
            $items = $items->paginate(20);
        }else{
            $items = $items->all();
        }
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
        $item = Brand::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
