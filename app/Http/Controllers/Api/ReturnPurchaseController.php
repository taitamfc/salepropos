<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ReturnPurchase;
use App\Supplier;
use App\PurchaseProductReturn;
use App\Product;
use App\Unit;
use App\Product_Warehouse;
use App\ProductVariant;
use App\Http\Resources\ReturnPurchaseResource;

class ReturnPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnPurchase::query(true)->orderBy('id','DESC');
        $items = $this->handleFilter($query,$request);
        return ReturnPurchaseResource::collection($items);
    }
	public function show($id)
    {
        $item = ReturnPurchase::find($id);
        return new ReturnPurchaseResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $data['grand_total'] = str_replace(',','',$data['grand_total']);
        $data['reference_no'] = 'prr-' . date("Ymd") . '-'. date("his");
        $data['user_id'] = 1;
        $data['account_id'] = 1;

        $saved = $lims_return_data = ReturnPurchase::create($data);
        $product_id = $data['product_ids'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];

        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            $lims_product_data->is_variant = false;
            $variant_id = null;
            if($purchase_unit[$key] != 'n/a') {
                $lims_purchase_unit_data  = Unit::where('id', $purchase_unit[$key])->first();
                $purchase_unit_id = $lims_purchase_unit_data->id;
                
                if($lims_purchase_unit_data->operator == '*'){
                    $quantity = $qty[$key] * $lims_purchase_unit_data->operation_value;
                }elseif($lims_purchase_unit_data->operator == '/'){
                    $quantity = $qty[$key] / $lims_purchase_unit_data->operation_value;
                }

                $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])->first();
                $lims_product_data->qty -=  $quantity;
                $lims_product_warehouse_data->qty -= $quantity;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            }

            $discount[$key] = str_replace(',','',$discount[$key]);
            $qty[$key] = str_replace(',','',$qty[$key]);
            $net_unit_cost[$key] = str_replace(',','',$net_unit_cost[$key]);  

            PurchaseProductReturn::insert(
                [
                    'return_id' => $lims_return_data->id, 
                    'product_id' => $pro_id, 
                    'variant_id' => $variant_id, 
                    'qty' => $qty[$key], 
                    'purchase_unit_id' => $purchase_unit_id, 
                    'net_unit_cost' => $net_unit_cost[$key], 
                    'discount' => $discount[$key], 
                    'tax_rate' => $tax_rate[$key], 
                    'tax' => $tax[$key], 
                    'total' => $total[$key], 
                    'created_at' => \Carbon\Carbon::now(),  
                    'updated_at' => \Carbon\Carbon::now() 
                ]
            );
        }
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $data['grand_total'] = str_replace(',','',$data['grand_total']);
        $lims_return_data = ReturnPurchase::find($id);
        $lims_product_return_data = PurchaseProductReturn::where('return_id', $id)->get();

        $product_id = $data['product_ids'];
        $product_code = $data['product_code'];
        $product_variant_id = $data['product_variant_id'] ?? [];
        $qty = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];

        foreach ($lims_product_return_data as $key => $product_return_data) {
            $old_product_id[] = $product_return_data->product_id;
            $old_product_variant_id[] = null;
            $lims_product_data = Product::find($product_return_data->product_id);
            if($product_return_data->purchase_unit_id != 0) {
                $lims_purchase_unit_data = Unit::find($product_return_data->purchase_unit_id);
                if ($lims_purchase_unit_data->operator == '*')
                    $quantity = $product_return_data->qty * $lims_purchase_unit_data->operation_value;
                elseif($lims_purchase_unit_data->operator == '/')
                    $quantity = $product_return_data->qty / $lims_purchase_unit_data->operation_value;

                if($product_return_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_return_data->product_id, $product_return_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_return_data->product_id, $product_return_data->variant_id, $lims_return_data->warehouse_id)
                    ->first();
                    $old_product_variant_id[$key] = $lims_product_variant_data->id;
                    $lims_product_variant_data->qty += $quantity;
                    $lims_product_variant_data->save();
                }
                else
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_return_data->product_id, $lims_return_data->warehouse_id)
                    ->first();

                $lims_product_data->qty += $quantity;
                $lims_product_warehouse_data->qty += $quantity;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            }
            if($product_return_data->variant_id && !(in_array($old_product_variant_id[$key], $product_variant_id)) ){
                $product_return_data->delete();
            }
            elseif( !(in_array($old_product_id[$key], $product_id)) )
                $product_return_data->delete();
        }
        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            $product_return['variant_id'] = null;
            if($purchase_unit[$key] != 'n/a'){
                $lims_purchase_unit_data = Unit::where('id', $purchase_unit[$key])->first();
                $purchase_unit_id = $lims_purchase_unit_data->id;
                
                if ($lims_purchase_unit_data->operator == '*'){
                    $quantity = $qty[$key] * $lims_purchase_unit_data->operation_value;
                }elseif($lims_purchase_unit_data->operator == '/'){
                    $quantity = $qty[$key] / $lims_purchase_unit_data->operation_value;
                }

                $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])
                    ->first();

                $lims_product_data->qty -=  $quantity;
                $lims_product_warehouse_data->qty -= $quantity;

                $lims_product_data->save();
                $lims_product_warehouse_data->save();
            }

            $discount[$key] = str_replace(',','',$discount[$key]);
            $qty[$key] = str_replace(',','',$qty[$key]);
            $net_unit_cost[$key] = str_replace(',','',$net_unit_cost[$key]);  

            $product_return['return_id'] = $id ;
            $product_return['product_id'] = $pro_id;
            $product_return['qty'] = $qty[$key];
            $product_return['purchase_unit_id'] = $purchase_unit_id;
            $product_return['net_unit_cost'] = $net_unit_cost[$key];
            $product_return['discount'] = $discount[$key];
            $product_return['tax_rate'] = $tax_rate[$key];
            $product_return['tax'] = $tax[$key];
            $product_return['total'] = $total[$key];

            if($product_return['variant_id'] && in_array($product_variant_id[$key], $old_product_variant_id)) {
                PurchaseProductReturn::where([
                    ['product_id', $pro_id],
                    ['variant_id', $product_return['variant_id']],
                    ['return_id', $id]
                ])->update($product_return);
            }
            elseif( $product_return['variant_id'] === null && (in_array($pro_id, $old_product_id)) ) {
                PurchaseProductReturn::where([
                    ['return_id', $id],
                    ['product_id', $pro_id]
                    ])->update($product_return);
            }
            else
                PurchaseProductReturn::create($product_return);
        }
        $saved = $lims_return_data->update($data);
        
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $lims_return_data = ReturnPurchase::find($id);
        $lims_product_return_data = PurchaseProductReturn::where('return_id', $id)->get();
        foreach ($lims_product_return_data as $key => $product_return_data) {
            $product_return_data->variant_id = false;
            $lims_product_data = Product::find($product_return_data->product_id);

            if($product_return_data->purchase_unit_id != 0){
                $lims_purchase_unit_data = Unit::find($product_return_data->purchase_unit_id);

                if ($lims_purchase_unit_data->operator == '*')
                    $quantity = $product_return_data->qty * $lims_purchase_unit_data->operation_value;
                elseif($lims_purchase_unit_data->operator == '/')
                    $quantity = $product_return_data->qty / $lims_purchase_unit_data->operation_value;

                if($product_return_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_return_data->product_id, $product_return_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_return_data->product_id, $product_return_data->variant_id, $lims_return_data->warehouse_id)->first();
                    $lims_product_variant_data->qty += $quantity;
                    $lims_product_variant_data->save();
                }
                else
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_return_data->product_id, $lims_return_data->warehouse_id)->first();

                $lims_product_data->qty += $quantity;
                $lims_product_warehouse_data->qty += $quantity;
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
                $product_return_data->delete();
            }
        }
        $lims_return_data->delete();
        return response()->json([
            'success' => true,
            'data' => $lims_return_data
        ]);
    }

    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = ReturnPurchase::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
