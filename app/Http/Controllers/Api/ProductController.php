<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Product;
use App\Product_Warehouse;
use App\Http\Resources\ProductResource;
use App\Imports\ProductImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query(true)->orderBy('products.id','DESC');
        $onlyActive = $request->onlyActive ?? false;
        if($onlyActive){
            $query->where('products.is_active',1);
        }

        
        $warehouse_id = $request->warehouse_id ?? 0;
        $filter = $request->filter && count($request->filter) ? $request->filter : [];
        if( isset($filter['warehouse_id']) ){
            $warehouse_id = $filter['warehouse_id'];
            unset($filter['warehouse_id']);
        }
        
        if( isset($filter['is_active']) ){
            $is_active = $filter['is_active'];
            if($is_active == 1){
                $query->where('products.is_active',1);
            }
            if($is_active == 2){
                $query->where('products.is_active',0);
            }
            unset($filter['is_active']);
        }
        if($warehouse_id){
            $query->select('products.*','product_warehouse.qty');
            $query->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id');
            $query->where('product_warehouse.warehouse_id',$warehouse_id);
        }
        if( isset($filter['remain']) ){
            $remain = $filter['remain'];
            if($remain == 1){
                if($warehouse_id){
                    $query->where('product_warehouse.qty','>',0);
                }else{
                    $query->where('products.qty','>',0);
                }
            }
            if($remain == 2){
                if($warehouse_id){
                    $query->where('product_warehouse.qty',0);
                }else{
                    $query->where('products.qty',0);
                }
            }
            unset($filter['remain']);
        }

        $product_ids = $request->product_ids ? explode(',',$request->product_ids) : [];
        if( count($filter) ){
            foreach($filter as $field => $value){
                $value = trim($value);
                if($value === '') continue;
                if( $field == 'name_or_code' ){
                    $query->where('products.name','LIKE','%'.$value.'%');
                    $query->orWhere('products.code','LIKE','%'.$value.'%');
                }else{
                    $query->where($field,$value);
                }
            }
        }
		if( count($product_ids) ){
			$query->whereIn('products.id',$product_ids);
        }

        $items = $query->paginate(20);
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
        $data = $this->_prepare_data($data);
        try {
            $saved = Product::create($data);
            return response()->json([
                'success' => true,
                'data' => $saved
            ]);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg'   => $e->getMessage()
            ]);
        }
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $data = $this->_prepare_data($data);
        $saved = Product::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Product::findOrFail($id);
        $item->is_active = false;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    public function processImport(Request $request){
        $path = $request->file('fileUpload')->store('public/productImports');
        Excel::import(new ProductImport, $path);
        return response()->json([
            'success' => true,
            'data' => $path
        ]);
    }

    private function _prepare_data($data){
        $data['purchase_unit_id'] = $data['unit_id'];
        $data['sale_unit_id'] = $data['unit_id'];
        $data['is_active'] = 1;
        $data['price'] = str_replace(',','',$data['price']);
        $data['cost'] = str_replace(',','',$data['cost']);
        return $data;
    }

    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Product::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
