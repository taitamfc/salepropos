<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Sale;
use App\Product_Sale;
use App\Customer;
use App\Unit;
use App\Product;
use App\Product_Warehouse;
use App\Http\Resources\SaleResource;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 20;
        $items = Sale::query(true)->orderBy('id','DESC');
        if( $limit != -1 ){
            $items = $items->paginate(20);
        }else{
            $items = $items->all();
        }
        return SaleResource::collection($items);
    }
	public function show($id)
    {
        $item = Sale::find($id);
        return new SaleResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except(['_token','_method']);
        $data['sale_status'] = 1;
        $data['user_id'] = 1;
        $data['reference_no'] = 'posr-' . date("Ymd") . '-'. date("his");
        $balance = $data['grand_total'] - $data['paid_amount'];
        if($balance > 0 || $balance < 0){
            $data['payment_status'] = 2;
        }else{
            $data['payment_status'] = 4;
        }

        $data['customer_id'] = $this->createOrGetCustomer($data);

        $saved = $lims_sale_data = Sale::create($data);
        $lims_customer_data = Customer::find($data['customer_id']);
        //collecting male data
        $mail_data['email'] = $lims_customer_data->email;
        $mail_data['reference_no'] = $lims_sale_data->reference_no;
        $mail_data['sale_status'] = $lims_sale_data->sale_status;
        $mail_data['payment_status'] = $lims_sale_data->payment_status;
        $mail_data['total_qty'] = $lims_sale_data->total_qty;
        $mail_data['total_price'] = $lims_sale_data->total_price;
        $mail_data['order_tax'] = $lims_sale_data->order_tax;
        $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
        $mail_data['order_discount'] = $lims_sale_data->order_discount;
        $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
        $mail_data['grand_total'] = $lims_sale_data->grand_total;
        $mail_data['paid_amount'] = $lims_sale_data->paid_amount;

        $product_id = $data['product_ids'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $sale_unit = $data['purchase_unit'];
        $net_unit_price = $data['net_unit_price'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_sale = [];

        foreach ($product_id as $i => $id) {
            $lims_product_data = Product::where('id', $id)->first();
            $product_sale['variant_id'] = null;
            if($lims_product_data->type == 'combo' && $data['sale_status'] == 1){
                $product_list = explode(",", $lims_product_data->product_list);
                $qty_list = explode(",", $lims_product_data->qty_list);
                $price_list = explode(",", $lims_product_data->price_list);

                foreach ($product_list as $key=>$child_id) {
                    $child_data = Product::find($child_id);
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $data['warehouse_id'] ],
                        ])->first();

                    $child_data->qty -= $qty[$i] * $qty_list[$key];
                    $child_warehouse_data->qty -= $qty[$i] * $qty_list[$key];

                    $child_data->save();
                    $child_warehouse_data->save();
                }
            }

            if($sale_unit[$i] != 'n/a') {
                $lims_sale_unit_data  = Unit::find($sale_unit[$i]);
                $sale_unit_id = $lims_sale_unit_data->id;
                $lims_product_data->is_variant = false;


                if($data['sale_status'] == 1){
                    if($lims_sale_unit_data->operator == '*'){
                        $quantity = $qty[$i] * $lims_sale_unit_data->operation_value;
                    }elseif($lims_sale_unit_data->operator == '/'){
                        $quantity = $qty[$i] / $lims_sale_unit_data->operation_value;
                    }

                    //deduct quantity
                    $lims_product_data->qty = $lims_product_data->qty - $quantity;
                    $lims_product_data->save();
                    
                    //deduct quantity from warehouse
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($id, $data['warehouse_id'])->first();
                    $lims_product_warehouse_data->qty -= $quantity;
                    $lims_product_warehouse_data->save();
                }
            }
            else
                $sale_unit_id = 0;
            if($product_sale['variant_id']){
                $variant_data = Variant::select('name')->find($product_sale['variant_id']);
                $mail_data['products'][$i] = $lims_product_data->name . ' ['. $variant_data->name .']';
            }
            else
                $mail_data['products'][$i] = $lims_product_data->name;
            if($lims_product_data->type == 'digital')
                $mail_data['file'][$i] = url('/product/files').'/'.$lims_product_data->file;
            else
                $mail_data['file'][$i] = '';
            if($sale_unit_id)
                $mail_data['unit'][$i] = $lims_sale_unit_data->unit_code;
            else
                $mail_data['unit'][$i] = '';

            $product_sale['sale_id'] = $lims_sale_data->id ;
            $product_sale['product_id'] = $id;
            $product_sale['qty'] = $mail_data['qty'][$i] = $qty[$i];
            $product_sale['sale_unit_id'] = $sale_unit_id;
            $product_sale['net_unit_price'] = $net_unit_price[$i];
            $product_sale['discount'] = $discount[$i];
            $product_sale['tax_rate'] = $tax_rate[$i];
            $product_sale['tax'] = $tax[$i];
            $product_sale['total'] = $mail_data['total'][$i] = $total[$i];
            Product_Sale::create($product_sale);
        }



        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except(['_token','_method']);
        $data['sale_status'] = 1;
        $balance = $data['grand_total'] - $data['paid_amount'];
        if($balance < 0 || $balance > 0)
            $data['payment_status'] = 2;
        else{
            $data['payment_status'] = 4;
        }

        $lims_sale_data = Sale::find($id);
        $data['customer_id'] = $lims_sale_data->customer_id;
        $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
        $product_id = $data['product_ids'];
        $product_code = $data['product_code'];
        $product_variant_id = $data['product_variant_id'] ?? [];
        $qty = $data['qty'];
        $sale_unit = $data['purchase_unit'];
        $net_unit_price = $data['net_unit_price'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $old_product_id = [];
        $product_sale = [];

        foreach ($lims_product_sale_data as  $key => $product_sale_data) {
            $old_product_id[] = $product_sale_data->product_id;
            $old_product_variant_id[] = null;
            $lims_product_data = Product::find($product_sale_data->product_id);

            if( ($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo') ) {
                $product_list = explode(",", $lims_product_data->product_list);
                $qty_list = explode(",", $lims_product_data->qty_list);

                foreach ($product_list as $index=>$child_id) {
                    $child_data = Product::find($child_id);
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $lims_sale_data->warehouse_id ],
                        ])->first();

                    $child_data->qty += $product_sale_data->qty * $qty_list[$index];
                    $child_warehouse_data->qty += $product_sale_data->qty * $qty_list[$index];

                    $child_data->save();
                    $child_warehouse_data->save();
                }
            }
            elseif( ($lims_sale_data->sale_status == 1) && ($product_sale_data->sale_unit_id != 0)) {
                $old_product_qty = $product_sale_data->qty;
                $lims_sale_unit_data = Unit::find($product_sale_data->sale_unit_id);
                
                if ($lims_sale_unit_data->operator == '*'){
                    $old_product_qty = $old_product_qty * $lims_sale_unit_data->operation_value;
                }else{
                    $old_product_qty = $old_product_qty / $lims_sale_unit_data->operation_value;
                }
                $product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_sale_data->product_id, $lims_sale_data->warehouse_id)
                    ->first();
                $lims_product_data->qty += $old_product_qty;
                $product_warehouse_data->qty += $old_product_qty;
                $lims_product_data->save();
                $product_warehouse_data->save();
            }
            if($product_sale_data->variant_id && !(in_array($old_product_variant_id[$key], $product_variant_id)) ){
                $product_sale_data->delete();
            }
            elseif( !(in_array($old_product_id[$key], $product_id)) )
                $product_sale_data->delete();
        }

        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            $product_sale['variant_id'] = null;
            if($lims_product_data->type == 'combo' && $data['sale_status'] == 1){
                $product_list = explode(",", $lims_product_data->product_list);
                $qty_list = explode(",", $lims_product_data->qty_list);

                foreach ($product_list as $index=>$child_id) {
                    $child_data = Product::find($child_id);
                    $child_warehouse_data = Product_Warehouse::where([
                        ['product_id', $child_id],
                        ['warehouse_id', $data['warehouse_id'] ],
                        ])->first();

                    $child_data->qty -= $qty[$key] * $qty_list[$index];
                    $child_warehouse_data->qty -= $qty[$key] * $qty_list[$index];

                    $child_data->save();
                    $child_warehouse_data->save();
                }
            }
            if($sale_unit[$key] != 'n/a') {
                $lims_sale_unit_data = Unit::find($sale_unit[$key]);
                $sale_unit_id = $lims_sale_unit_data->id;
                if($data['sale_status'] == 1) {
                    $new_product_qty = $qty[$key];
                    if ($lims_sale_unit_data->operator == '*') {
                        $new_product_qty = $new_product_qty * $lims_sale_unit_data->operation_value;
                    } else {
                        $new_product_qty = $new_product_qty / $lims_sale_unit_data->operation_value;
                    }
                    if($lims_product_data->is_variant) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($pro_id, $lims_product_variant_data->variant_id, $data['warehouse_id'])
                        ->first();
                        
                        $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                        $lims_product_variant_data->qty -= $new_product_qty;
                        $lims_product_variant_data->save();
                    }
                    else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])
                        ->first();
                    }
                    $lims_product_data->qty -= $new_product_qty;
                    $lims_product_warehouse_data->qty -= $new_product_qty;
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }
            }
            else
                $sale_unit_id = 0;
            
            //collecting mail data
            if($product_sale['variant_id']) {
                $variant_data = Variant::select('name')->find($product_sale['variant_id']);
                $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
            }
            else
                $mail_data['products'][$key] = $lims_product_data->name;

            if($lims_product_data->type == 'digital')
                $mail_data['file'][$key] = url('/product/files').'/'.$lims_product_data->file;
            else
                $mail_data['file'][$key] = '';
            if($sale_unit_id)
                $mail_data['unit'][$key] = $lims_sale_unit_data->unit_code;
            else
                $mail_data['unit'][$key] = '';

            $product_sale['sale_id'] = $id ;
            $product_sale['product_id'] = $pro_id;
            $product_sale['qty'] = $mail_data['qty'][$key] = $qty[$key];
            $product_sale['sale_unit_id'] = $sale_unit_id;
            $product_sale['net_unit_price'] = $net_unit_price[$key];
            $product_sale['discount'] = $discount[$key];
            $product_sale['tax_rate'] = $tax_rate[$key];
            $product_sale['tax'] = $tax[$key];
            $product_sale['total'] = $mail_data['total'][$key] = $total[$key];
            
            if($product_sale['variant_id'] && in_array($product_variant_id[$key], $old_product_variant_id)) {
                Product_Sale::where([
                    ['product_id', $pro_id],
                    ['variant_id', $product_sale['variant_id']],
                    ['sale_id', $id]
                ])->update($product_sale);
            }
            elseif( $product_sale['variant_id'] === null && (in_array($pro_id, $old_product_id)) ) {
                Product_Sale::where([
                    ['sale_id', $id],
                    ['product_id', $pro_id]
                    ])->update($product_sale);
            }
            else
                Product_Sale::create($product_sale);
        }
        $saved = $lims_sale_data->update($data);
        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $item = Sale::find($id)->delete();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    public function createOrGetCustomer($data){
        $customer = Customer::where('phone_number', $data['customer_phone'])->first();
        if ($customer === null) {
            $customer = new Customer();
            $customer->name = $data['customer_name'];
            $customer->phone_number = $data['customer_phone'];
            $customer->address = $data['customer_address'];
            $customer->customer_group_id = 1;
            $customer->save();
        }
        return $customer->id;
    }
}
