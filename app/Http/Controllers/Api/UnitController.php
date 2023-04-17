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
        $limit = $request->limit ?? 20;
        $items = Unit::query(true);
        if( $limit != -1 ){
            $items = $items->paginate(20);
        }else{
            $items = $items->all();
        }
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
        $item = Unit::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
