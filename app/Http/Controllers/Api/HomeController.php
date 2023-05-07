<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Sale;
use App\Returns;
use App\ReturnPurchase;
use App\Purchase;
use App\Payment;
use App\Http\Resources\SaleResource;
use App\Http\Resources\PurchaseResource;

class HomeController extends Controller
{
    public function index(Request $request)
    {
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
        
        $revenue = Sale::whereDate('created_at', '>=' , $start_date)
        ->whereDate('created_at', '<=' , $end_date);
        if($warehouseId){
            $revenue->where('warehouse_id',$warehouseId);
        }
        $revenue = $revenue->sum('grand_total');
        
        $return = Returns::whereDate('created_at', '>=' , $start_date)
        ->whereDate('created_at', '<=' , $end_date);
        if($warehouseId){
            $return->where('warehouse_id',$warehouseId);
        }
        $return = $return->sum('grand_total');

        $purchase_return = ReturnPurchase::whereDate('created_at', '>=' , $start_date)
        ->whereDate('created_at', '<=' , $end_date);
        if($warehouseId){
            $purchase_return->where('warehouse_id',$warehouseId);
        }
        $purchase_return = $purchase_return->sum('grand_total');

        $purchase = Purchase::whereDate('created_at', '>=' , $start_date)
        ->whereDate('created_at', '<=' , $end_date);
        if($warehouseId){
            $purchase->where('warehouse_id',$warehouseId);
        }
        $purchase = $purchase->sum('grand_total');

        $revenue = $revenue - $return;
        $profit = $revenue + $purchase_return - $purchase;
        $recent_sale = Sale::orderBy('id', 'desc')->take(5)->get();
        $recent_purchase = Purchase::orderBy('id', 'desc')->take(5)->get();
        $recent_payment = Payment::orderBy('id', 'desc')->take(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'start_date'        => $start_date,
                'end_date'          => $end_date,
                'revenue'           => number_format($revenue),
                'profit'            => number_format($profit),
                'sale_return'       => number_format($return),
                'purchase_return'   => number_format($purchase_return),
                'recent_sale'       => SaleResource::collection($recent_sale),
                'recent_purchase'   => PurchaseResource::collection($recent_purchase),
                'recent_payment'    => $recent_payment,
            ]
        ]);
    }

    
}
