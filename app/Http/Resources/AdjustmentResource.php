<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentResource extends JsonResource
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
        $data['warehouse_name'] = $this->warehouse->name ?? '';
        $data['date_format']    = date('d/m/Y H:i',strtotime($this->created_at)) ?? '';
        $data['products']           = ProductAdjustmentResource::collection($this->products);
        return $data;
    }
}
