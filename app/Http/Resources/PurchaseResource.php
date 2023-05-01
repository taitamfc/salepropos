<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductPurchaseResource;

class PurchaseResource extends JsonResource
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
        $data['created_at_format']         = date('m/d/Y',strtotime($data['created_at']));
        $data['warehouse_name']     = $this->warehouse->name ?? '';
        $data['grand_total']        = number_format($this->grand_total);
        $data['paid_amount']        = number_format($this->paid_amount);
        $data['total_product']      = $this->products()->count();
        // $data['user_name']          = $this->user->name;
        $data['supplier_name']      = $this->supplier->name ?? '';
        $data['due']                = number_format($this->grand_total - $this->paid_amount);
        $data['products']           = ProductPurchaseResource::collection($this->products);
        return $data;
    }
}
