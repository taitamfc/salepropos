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
        $limit = $request->limit ?? 20;
        $items = Supplier::query(true);
        if( $limit != -1 ){
            $items = $items->paginate(20);
        }else{
            $items = $items->all();
        }
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
        $item = Supplier::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
