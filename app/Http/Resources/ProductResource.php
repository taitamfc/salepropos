<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
        $data['brand_name']     = $this->brand->title ?? '';
        $data['category_name']  = $this->category->name ?? '';
        $data['unit_name']      = $this->unit->unit_name ?? '';
        $data['image']          = $this->image ?? '';
        $data['price_format']   =  number_format($this->price);
        $data['discount']       =  0;
        return $data;
    }
}
