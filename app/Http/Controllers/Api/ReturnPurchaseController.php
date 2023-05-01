<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Warehouse;
use App\Http\Resources\WarehouseResource;

class ReturnPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 20;
        $items = Warehouse::query(true);
        if( $limit != -1 ){
            $items = $items->paginate(20);
        }else{
            $items = $items->all();
        }
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
        $item = Warehouse::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
