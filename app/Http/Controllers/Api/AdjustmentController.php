<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Adjustment;
use App\ProductAdjustment;
use App\Product;
use App\Product_Warehouse;
use App\Http\Resources\AdjustmentResource;

class AdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 20;
        $items = Adjustment::query(true);
        if( $limit != -1 ){
            $items = $items->paginate(20);
        }else{
            $items = $items->all();
        }
        return AdjustmentResource::collection($items);
    }
	public function show($id)
    {
        $item = Adjustment::find($id);
        return new AdjustmentResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except('document');
        
        if( isset($data['stock_count_id']) ){
            $lims_stock_count_data = StockCount::find($data['stock_count_id']);
            $lims_stock_count_data->is_adjusted = true;
            $lims_stock_count_data->save();
        }
        $data['reference_no'] = 'adr-' . date("Ymd") . '-'. date("his");
        $saved = $lims_adjustment_data = Adjustment::create($data);

        $product_id = $data['product_ids'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $action = $data['action'];

        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            $lims_product_warehouse_data = Product_Warehouse::where([
                ['product_id', $pro_id],
                ['warehouse_id', $data['warehouse_id'] ],
                ])->first();
            $variant_id = null;

            if($action[$key] == '-') {
                $lims_product_data->qty -= $qty[$key];
                $lims_product_warehouse_data->qty -= $qty[$key];
            }
            elseif($action[$key] == '+') {
                $lims_product_data->qty += $qty[$key];
                $lims_product_warehouse_data->qty += $qty[$key];
            }
            $lims_product_data->save();
            $lims_product_warehouse_data->save();

            $product_adjustment['product_id'] = $pro_id;
            $product_adjustment['variant_id'] = $variant_id;
            $product_adjustment['adjustment_id'] = $lims_adjustment_data->id;
            $product_adjustment['qty'] = $qty[$key];
            $product_adjustment['action'] = $action[$key];
            ProductAdjustment::create($product_adjustment);
        }
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $saved = Adjustment::find($id)->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Adjustment::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
