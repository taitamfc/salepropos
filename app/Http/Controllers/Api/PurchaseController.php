<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Purchase;
use App\Http\Resources\PurchaseResource;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 20;
        $items = Purchase::query(true)->orderBy('id','DESC');
        if( $limit != -1 ){
            $items = $items->paginate(20);
        }else{
            $items = $items->all();
        }
        return PurchaseResource::collection($items);
    }
	public function show($id)
    {
        $item = Purchase::find($id);

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
	
	public function store(Request $request)
    {
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            $imageName = $imageName . '.' . $ext;
            $image->move('images/Purchase', $imageName);
            $data['image'] = $imageName;
        }
        $data['name'] = $request->name;
        $data['parent_id'] = $request->parent_id;
        $data['is_active'] = true;
        $saved = Purchase::create($data);

        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            $imageName = $imageName . '.' . $ext;
            $image->move('images/Purchase', $imageName);
            $data['image'] = $imageName;
        }
        $data['name'] = $request->name;
        $data['parent_id'] = $request->parent_id;
        $data['is_active'] = true;
        $saved = Purchase::find($id)->update($data);

        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Purchase::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
