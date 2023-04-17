<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Product;
use App\Http\Resources\ProductResource;


class ProductController extends Controller
{
    public function index()
    {
        $items = Product::query(true);

        $items = $items->paginate(20);
        return ProductResource::collection($items);
    }
    public function show($id)
    {
        $item = Product::find($id);
        return new ProductResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Product::create($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Product::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Product::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
