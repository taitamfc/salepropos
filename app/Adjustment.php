<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $fillable =[
        "reference_no", "warehouse_id", "document", "total_qty", "item", 
         "note"   
    ];
    public function warehouse()
    {
    	return $this->belongsTo('App\Warehouse', 'warehouse_id');
    }
    public function products()
    {
    	return $this->hasMany('App\ProductAdjustment', 'adjustment_id');
    }
}
