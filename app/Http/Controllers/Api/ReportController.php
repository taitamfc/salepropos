<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Purchase;
use App\Sale;
use App\ReturnPurchase;
use App\Returns;

class ReportController extends Controller
{
    public function profitLoss(Request $request){
        $f_start_date = date("Y").'-'.date("m").'-'.'01';
        $f_end_date = date("Y").'-'.date("m").'-'.date('t', mktime(0, 0, 0, date("m"), 1, date("Y")));

        $dateFilter = $request->dateFilter ?? '';
        $warehouseId = $request->warehouseId ?? 0;
        
        switch ($dateFilter) {
            case 'thisweek':
                $start = new \DateTime('this week');
                $start->modify('Monday');
                $end = clone $start;
                $end->modify('+6 days');
                $start_date = $start->format('Y-m-d');
                $end_date = $end->format('Y-m-d');

                break;
            case 'today':
                $startTime = strtotime('today 00:00:00');
                $endTime = strtotime('today 23:59:59');
                $start_date = date('Y-m-d',$startTime);
                $end_date   = date('Y-m-d',$endTime);
                break;
            case 'yesterday':
                $startTime = strtotime('yesterday 00:00:00');
                $endTime = strtotime('yesterday 23:59:59');
                $start_date = date('Y-m-d',$startTime);
                $end_date   = date('Y-m-d',$endTime);
                break;
            case 'thismonth':
                $start_date = $f_start_date;
                $end_date = $f_end_date;
                break;
            case 'lastmonth':
                $startTime = strtotime('first day of last month 00:00:00');
                $endTime = strtotime('last day of last month 23:59:59');
                $start_date = date('Y-m-d',$startTime);
                $end_date   = date('Y-m-d',$endTime);
                break;
            default:
                $start_date = $f_start_date;
                $end_date = $f_end_date;
                break;
        }
        $query1 = array(
            'SUM(grand_total) AS grand_total',
            'SUM(paid_amount) AS paid_amount',
            'SUM(total_discount) AS discount'
        );
        $query2 = array(
            'SUM(grand_total) AS grand_total',
            'SUM(total_tax + order_tax) AS tax'
        );

        $purchase = Purchase::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date)
            ->selectRaw(implode(',', $query1));
            if($warehouseId){
                $purchase->where('warehouse_id',$warehouseId);
            }
            $purchase = $purchase->get();

        $total_purchase = Purchase::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date);
            if($warehouseId){
                $total_purchase->where('warehouse_id',$warehouseId);
            }
            $total_purchase = $total_purchase->count();

        $sale = Sale::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date)
            ->selectRaw(implode(',', $query1));
            if($warehouseId){
                $sale->where('warehouse_id',$warehouseId);
            }
            $sale = $sale->get();

        $total_sale = Sale::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date);
            if($warehouseId){
                $total_sale->where('warehouse_id',$warehouseId);
            }
            $total_sale = $total_sale->count();

        $due_purchase = Purchase::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date)
            ->where('payment_status','!=',4)
            ->selectRaw('SUM(grand_total - paid_amount) AS due');
            if($warehouseId){
                $due_purchase->where('warehouse_id',$warehouseId);
            }
            $due_purchase = $due_purchase->pluck('due');
            $due_purchase = $due_purchase[0];


        $due_sale = Sale::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date)
            ->where('payment_status','!=',4)
            ->selectRaw('SUM(grand_total - paid_amount) AS due');
            if($warehouseId){
                $due_sale->where('warehouse_id',$warehouseId);
            }
            $due_sale = $due_sale->pluck('due');
            $due_sale = $due_sale[0];
        
        $purchase_return = ReturnPurchase::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date)
            ->selectRaw(implode(',', $query2));
            if($warehouseId){
                $purchase_return->where('warehouse_id',$warehouseId);
            }
            $purchase_return = $purchase_return->get();

        $total_purchase_return = ReturnPurchase::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date);
            if($warehouseId){
                $total_purchase_return->where('warehouse_id',$warehouseId);
            }
            $total_purchase_return = $total_purchase_return->count();
        
        $sale_return = Returns::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date)
            ->selectRaw(implode(',', $query2));
            if($warehouseId){
                $sale_return->where('warehouse_id',$warehouseId);
            }
            $sale_return = $sale_return->get();


        $total_sale_return = Returns::whereDate('created_at', '>=' , $start_date)
            ->whereDate('created_at', '<=' , $end_date);
            if($warehouseId){
                $total_sale_return->where('warehouse_id',$warehouseId);
            }
            $total_sale_return = $total_sale_return->count();

        $profix_before  = $sale[0]->grand_total - $purchase[0]->grand_total;
        $profix_after   = $sale[0]->grand_total - $purchase[0]->grand_total - $sale_return[0]->grand_total + $purchase_return[0]->grand_total;

        $return = [
            'sale_return' => [
                'grand_total'           => number_format($sale_return[0]->grand_total),
                'total_sale_return' => number_format($total_sale_return),
            ],
            'purchase_return' => [
                'grand_total'           => number_format($purchase_return[0]->grand_total),
                'total_purchase_return' => number_format($total_purchase_return),
            ],
            'purchase' => [
                'grand_total'       => number_format($purchase[0]->grand_total),
                'paid_amount'       => number_format($purchase[0]->paid_amount),
                'discount'          => number_format($purchase[0]->discount),
                'total_purchase'    => number_format($total_purchase),
            ],
            'sale' => [
                'grand_total'       => number_format($sale[0]->grand_total),
                'paid_amount'       => number_format($sale[0]->paid_amount),
                'discount'          => number_format($sale[0]->discount),
                'total_sale'        => number_format($total_sale),
            ],
            'due' => [
                'due_purchase'      => number_format($due_purchase),
                'due_sale'          => number_format($due_sale),
            ],
            'profix' => [
                'before'    => number_format($profix_before),
                'after'     => number_format($profix_after),
            ]
        ];
        return response()->json($return);
        
   }
}
