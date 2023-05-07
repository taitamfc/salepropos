<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Warehouse;
use App\Supplier;
use App\Product;
use App\Unit;
use App\Tax;
use App\Account;
use App\Purchase;
use App\ProductPurchase;
use App\Product_Warehouse;
use App\Payment;
use App\PaymentWithCheque;
use App\PaymentWithCreditCard;
use App\PosSetting;
use DB;
use App\GeneralSetting;
use Stripe\Stripe;
use Auth;
use App\User;
use App\ProductVariant;
use App\Http\Resources\PurchaseResource;

class PurchaseController extends Controller
{
   
    public function index(Request $request)
    {
        $query = Purchase::query(true)->orderBy('id','DESC');
        $items = $this->handleFilter($query,$request);
        return PurchaseResource::collection($items);
    }
	public function show($id)
    {
        $item = Purchase::find($id);
        return new PurchaseResource($item);
    }
	
	public function store(Request $request)
    {
        $data = $request->except('document');
        $data['user_id'] = 1;
        $data['reference_no'] = 'pr-' . date("Ymd") . '-'. date("his");
        $data['grand_total'] = str_replace(',','',$data['grand_total']);
        $data['paid_amount'] = str_replace(',','',$data['paid_amount']);
        $saved = Purchase::create($data);

        $lims_purchase_data = Purchase::latest()->first();
        $product_id = $data['product_ids'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        // $recieved = $data['recieved'];
        $recieved = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_purchase = [];

        foreach ($product_id as $i => $id) {
            $lims_purchase_unit_data  = Unit::find($purchase_unit[$i]);

            if ($lims_purchase_unit_data->operator == '*') {
                $quantity = $recieved[$i] * $lims_purchase_unit_data->operation_value;
            } else {
                $quantity = $recieved[$i] / $lims_purchase_unit_data->operation_value;
            }
            $lims_product_data = Product::find($id);
            if($lims_product_data->is_variant) {
                $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($lims_product_data->id, $product_code[$i])->first();
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $id],
                    ['variant_id', $lims_product_variant_data->variant_id],
                    ['warehouse_id', $data['warehouse_id']]
                ])->first();
                $product_purchase['variant_id'] = $lims_product_variant_data->variant_id;
                //add quantity to product variant table
                $lims_product_variant_data->qty += $quantity;
                $lims_product_variant_data->save();
            }
            else {
                $product_purchase['variant_id'] = null;
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $id],
                    ['warehouse_id', $data['warehouse_id'] ],
                ])->first();
            }
            //add quantity to product table
            $lims_product_data->qty = $lims_product_data->qty + $quantity;
            $lims_product_data->save();
            //add quantity to warehouse
            if ($lims_product_warehouse_data) {
                $lims_product_warehouse_data->qty = $lims_product_warehouse_data->qty + $quantity;
            } 
            else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $id;
                $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                $lims_product_warehouse_data->qty = $quantity;
                if($lims_product_data->is_variant)
                    $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
            }

            $lims_product_warehouse_data->save();

            $discount[$i] = str_replace(',','',$discount[$i]);
            $qty[$i] = str_replace(',','',$qty[$i]);
            $net_unit_cost[$i] = str_replace(',','',$net_unit_cost[$i]);  

            $product_purchase['purchase_id'] = $lims_purchase_data->id ;
            $product_purchase['product_id'] = $id;
            $product_purchase['qty'] = $qty[$i];
            $product_purchase['recieved'] = $recieved[$i];
            $product_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
            $product_purchase['net_unit_cost'] = $net_unit_cost[$i];
            $product_purchase['discount'] = $discount[$i];
            $product_purchase['tax_rate'] = $tax_rate[$i];
            $product_purchase['tax'] = $tax[$i];
            $product_purchase['total'] = $total[$i];
            $saved = ProductPurchase::create($product_purchase);
        }

        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function update($id,Request $request)
    {
        $data = $request->except('document');
        $data['grand_total'] = str_replace(',','',$data['grand_total']);
        $data['paid_amount'] = str_replace(',','',$data['paid_amount']);
        $balance = $data['grand_total'] - $data['paid_amount'];
        if ($balance < 0 || $balance > 0) {
            $data['payment_status'] = 1;
        } else {
            $data['payment_status'] = 2;
        }
        $lims_purchase_data = Purchase::find($id);
        
        $lims_product_purchase_data = ProductPurchase::where('purchase_id', $id)->get();

        $product_id = $data['product_ids'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        // $recieved = $data['recieved'];
        $recieved = $data['qty'];
        $purchase_unit = $data['purchase_unit'];
        $net_unit_cost = $data['net_unit_cost'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_purchase = [];

        foreach ($lims_product_purchase_data as $product_purchase_data) {

            $old_recieved_value = $product_purchase_data->recieved;
            $lims_purchase_unit_data = Unit::find($product_purchase_data->purchase_unit_id);
            
            if ($lims_purchase_unit_data->operator == '*') {
                $old_recieved_value = $old_recieved_value * $lims_purchase_unit_data->operation_value;
            } else {
                $old_recieved_value = $old_recieved_value / $lims_purchase_unit_data->operation_value;
            }
            $lims_product_data = Product::find($product_purchase_data->product_id);
            if($lims_product_data->is_variant) {
                $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProduct($lims_product_data->id, $product_purchase_data->variant_id)->first();
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $lims_product_data->id],
                    ['variant_id', $product_purchase_data->variant_id],
                    ['warehouse_id', $lims_purchase_data->warehouse_id]
                ])->first();
                $lims_product_variant_data->qty -= $old_recieved_value;
                $lims_product_variant_data->save();
            }
            else {
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $product_purchase_data->product_id],
                    ['warehouse_id', $lims_purchase_data->warehouse_id],
                    ])->first();
            }

            $lims_product_data->qty -= $old_recieved_value;
            $lims_product_warehouse_data->qty -= $old_recieved_value;
            $lims_product_warehouse_data->save();
            $lims_product_data->save();
            $product_purchase_data->delete();
        }

        foreach ($product_id as $key => $pro_id) {
            $lims_purchase_unit_data  = Unit::find($purchase_unit[$key]);
            // $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$key])->first();
            if ($lims_purchase_unit_data->operator == '*') {
                $new_recieved_value = $recieved[$key] * $lims_purchase_unit_data->operation_value;
            } else {
                $new_recieved_value = $recieved[$key] / $lims_purchase_unit_data->operation_value;
            }

            $lims_product_data = Product::find($pro_id);
            if($lims_product_data->is_variant) {
                $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $pro_id],
                    ['variant_id', $lims_product_variant_data->variant_id],
                    ['warehouse_id', $data['warehouse_id']]
                ])->first();
                $product_purchase['variant_id'] = $lims_product_variant_data->variant_id;
                //add quantity to product variant table
                $lims_product_variant_data->qty += $new_recieved_value;
                $lims_product_variant_data->save();
            }
            else {
                $product_purchase['variant_id'] = null;
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $pro_id],
                    ['warehouse_id', $data['warehouse_id'] ],
                ])->first();
            }

            $lims_product_data->qty += $new_recieved_value;
            if($lims_product_warehouse_data){
                $lims_product_warehouse_data->qty += $new_recieved_value;
                $lims_product_warehouse_data->save();
            }
            else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $pro_id;
                if($lims_product_data->is_variant)
                    $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
                $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                $lims_product_warehouse_data->qty = $new_recieved_value;
                $lims_product_warehouse_data->save();
            }

            $lims_product_data->save();

            $discount[$key] = str_replace(',','',$discount[$key]);
            $qty[$key] = str_replace(',','',$qty[$key]);
            $net_unit_cost[$key] = str_replace(',','',$net_unit_cost[$key]);   

            $product_purchase['purchase_id'] = $id ;
            $product_purchase['product_id'] = $pro_id;
            $product_purchase['qty'] = $qty[$key];
            $product_purchase['recieved'] = $recieved[$key];
            $product_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
            $product_purchase['net_unit_cost'] = $net_unit_cost[$key];
            $product_purchase['discount'] = $discount[$key];
            $product_purchase['tax_rate'] = $tax_rate[$key];
            $product_purchase['tax'] = $tax[$key];
            $product_purchase['total'] = $total[$key];
            ProductPurchase::create($product_purchase);
        }

        $saved = $lims_purchase_data->update($data);

        return response()->json([
            'success' => true,
            'data' => $saved
        ]);
    }
	
	public function destroy($id)
    {
        $lims_purchase_data = Purchase::find($id);
        $lims_product_purchase_data = ProductPurchase::where('purchase_id', $id)->get();
        $lims_payment_data = Payment::where('purchase_id', $id)->get();
        foreach ($lims_product_purchase_data as $product_purchase_data) {
            $lims_purchase_unit_data = Unit::find($product_purchase_data->purchase_unit_id);
            if ($lims_purchase_unit_data->operator == '*')
                $recieved_qty = $product_purchase_data->recieved * $lims_purchase_unit_data->operation_value;
            else
                $recieved_qty = $product_purchase_data->recieved / $lims_purchase_unit_data->operation_value;

            $lims_product_data = Product::find($product_purchase_data->product_id);
            if($product_purchase_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_purchase_data->variant_id)->first();
                $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_purchase_data->product_id, $product_purchase_data->variant_id, $lims_purchase_data->warehouse_id)
                    ->first();
                $lims_product_variant_data->qty -= $recieved_qty;
                $lims_product_variant_data->save();
            }
            else {
                $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_purchase_data->product_id, $lims_purchase_data->warehouse_id)
                    ->first();
            }
            
            $lims_product_data->qty -= $recieved_qty;
            $lims_product_warehouse_data->qty -= $recieved_qty;

            $lims_product_warehouse_data->save();
            $lims_product_data->save();
            $product_purchase_data->delete();
        }
        foreach ($lims_payment_data as $payment_data) {
            if($payment_data->paying_method == "Cheque"){
                $payment_with_cheque_data = PaymentWithCheque::where('payment_id', $payment_data->id)->first();
                $payment_with_cheque_data->delete();
            }
            elseif($payment_data->paying_method == "Credit Card"){
                $payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment_data->id)->first();
                $lims_pos_setting_data = PosSetting::latest()->first();
                \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                \Stripe\Refund::create(array(
                    "charge" => $payment_with_credit_card_data->charge_id,
                ));

                $payment_with_credit_card_data->delete();
            }
            $payment_data->delete();
        }

        $lims_purchase_data->delete();
        return response()->json([
            'success' => true,
            'data' => $lims_purchase_data
        ]);
    }

    public function allDue(Request $request)
    {
        $limit = $request->limit ?? 20;
        $query = Purchase::orderBy('id','DESC')->where('payment_status','!=',4);
        $items = $this->handleFilter($query,$request);
        return PurchaseResource::collection($items);
    }
    public function getPayments($id){
        $items = Payment::where('purchase_id', $id)->get();
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
    public function storePayment($id,Request $request){
        $data = $request->except(['_token','_method']);
        $data['paid_by_id'] = 1;
        $data['account_id'] = 1;
        $data['paying_amount'] = str_replace(',','',$data['paying_amount']);
        $data['amount'] = str_replace(',','',$data['amount']);

        $lims_purchase_data = Purchase::find($id);
        $lims_purchase_data->paid_amount += $data['amount'];
        $balance = $lims_purchase_data->grand_total - $lims_purchase_data->paid_amount;
        if($balance > 0 || $balance < 0)
            $lims_purchase_data->payment_status = 1;
        elseif ($balance == 0)
            // $lims_purchase_data->payment_status = 2;
            $lims_purchase_data->payment_status = 4;
        $lims_purchase_data->save();

        $paying_method = 'Cash';

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = 1;
        $lims_payment_data->purchase_id = $lims_purchase_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->payment_reference = 'ppr-' . date("Ymd") . '-'. date("his");
        $lims_payment_data->amount = $data['amount'];
        $lims_payment_data->change = $data['paying_amount'] - $data['amount'];
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $saved = $lims_payment_data->save();


        return response()->json([
            'success' => true,
            'data' => $saved
        ]);

    }

    public function changeStatus($id,Request $request){
        $is_active = $request->is_active ?? 0;
        $item = Purchase::findOrFail($id);
        $item->is_active = $is_active;
        $item->save();
        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
