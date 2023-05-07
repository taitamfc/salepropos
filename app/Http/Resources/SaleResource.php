<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
        $data['created_at_format']  = date('m/d/Y H:i',strtotime($data['created_at']));
        $data['warehouse_name']     = $this->warehouse->name ?? '';
        $data['customer_info']      = $this->customer->name ? $this->customer->name .' ('.$this->customer->phone_number.')' : '';
        $data['due']                = $this->grand_total - $this->paid_amount;
        $data['due_format']         = number_format($data['due']);
        $data['grand_total_format'] = number_format($data['grand_total']);
        $data['paid_amount_format'] = number_format($data['paid_amount']);
        $data['products']           = ProductSaleResource::collection($this->products);
        $data['customer_name']      = $this->customer->name;
        $data['customer_phone']     = $this->customer->phone_number;
        $data['customer_address']   = $this->customer->address;
        return $data;
    }
}
