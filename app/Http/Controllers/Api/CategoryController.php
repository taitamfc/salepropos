<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $items = Category::query(true);

        $items = $items->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
	public function show($id)
    {
        $item = Category::find($id);

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
            $image->move('images/category', $imageName);
            $data['image'] = $imageName;
        }
        $data['name'] = $request->name;
        $data['parent_id'] = $request->parent_id;
        $data['is_active'] = true;
        $saved = Category::create($data);

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
            $image->move('images/category', $imageName);
            $data['image'] = $imageName;
        }
        $data['name'] = $request->name;
        $data['parent_id'] = $request->parent_id;
        $data['is_active'] = true;
        $saved = Category::find($id)->update($data);

        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Category::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
