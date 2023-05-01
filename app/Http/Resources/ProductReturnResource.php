<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductReturnResource extends JsonResource
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
        $data['name'] = $this->product->name ?? '';
        $data['code'] = $this->product->code ?? '';
        $data['id'] = $this->product->id ?? '';
        $data['unit_id'] = $this->product->unit_id ?? '';
        $data['price'] = $this->net_unit_price ?? 0;
        $data['cr_promotion_price'] = $this->discount ?? 0;
        $data['cr_qty'] = $this->qty ?? 0;
        return $data;
    }
}
