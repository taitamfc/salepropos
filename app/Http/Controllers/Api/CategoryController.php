<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Category;
use App\Product;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query(true)->orderBy('id','DESC');
        $items = $this->handleFilter($query,$request);
        return CategoryResource::collection($items);
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
        $lims_category_data = Category::findOrFail($id);
        $lims_category_data->is_active = false;
        $lims_product_data = Product::where('category_id', $id)->get();
        foreach ($lims_product_data as $product_data) {
            $product_data->is_active = false;
            $product_data->save();
        }
        $lims_category_data->save();
        return response()->json([
            'success' => true,
            'data' => $lims_category_data
        ]);
    }
    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Category::findOrFail($id);
        $item->is_active = $is_active;

        if( $is_active ){
            $lims_product_data = Product::where('category_id', $id)->get();
            foreach ($lims_product_data as $product_data) {
                $product_data->is_active = true;
                $product_data->save();
            }
        }else{
            $lims_product_data = Product::where('category_id', $id)->get();
            foreach ($lims_product_data as $product_data) {
                $product_data->is_active = false;
                $product_data->save();
            }
        }

        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
