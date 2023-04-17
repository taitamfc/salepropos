<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id'        => $this->id,
            'unit_name' => $this->unit_name,
            'unit_code' => $this->unit_code,
        ];
        return $data;
    }
}
