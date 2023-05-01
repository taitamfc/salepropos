<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductPurchaseResource;


class TransferResource extends JsonResource
{
    private $statues = [
        1 => 'Hoàn thành',
        2 => 'Chờ xử lý',
        3 => 'Đã gửi',
    ];
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['from_warehouse_name'] = $this->fromWarehouse->name ?? '';
        $data['to_warehouse_name'] = $this->toWarehouse->name ?? '';
        $data['status_name']        = $this->statues[$this->status] ?? '';
        $data['products']           = ProductPurchaseResource::collection($this->products);
        return $data;
    }
}
