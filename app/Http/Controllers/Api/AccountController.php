<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Account;
use App\Http\Resources\AccountResource;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::query(true)->orderBy('id','DESC');
        $items = $this->handleFilter($query,$request);
        return AccountResource::collection($items);
    }
	public function show($id)
    {
        $item = Account::find($id);
        return new AccountResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Account::create($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Account::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Account::findOrFail($id);
        $item->is_active = 0;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Account::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
