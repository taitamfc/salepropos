<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Product_Warehouse;

class ProductAdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['name']       = $this->product->name ?? '';
        $data['code']       = $this->product->code ?? '';
        $data['id']         = $this->product->id ?? '';
        $data['price']      = $this->net_unit_cost ?? '';
        $data['unit_id']    = $this->product->unit_id ?? '';
        $data['change_qty'] = $this->qty ?? '';
        // $data['adjustment'] = $this->adjustment ?? '';
        $data['qty']        = Product_Warehouse::select('qty')->where('product_id',$this->product_id)->where('warehouse_id',$this->adjustment->warehouse_id)->limit(1)->value('qty');
        return $data;
    }
}
