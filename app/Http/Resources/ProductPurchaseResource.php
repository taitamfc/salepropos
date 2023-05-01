<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductPurchaseResource extends JsonResource
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
        $data['price'] = $this->net_unit_cost ?? '';
        $data['unit_id'] = $this->product->unit_id ?? '';
        return $data;
    }
}
