<?php

namespace App\Imports;

use App\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Keygen;

class ProductImport implements ToCollection
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function collection(Collection $rows)
    {
        
        foreach ($rows as $key => $data) 
        {
            if($key == 0) continue; 
            $product = Product::firstOrNew([ 'name'=>$data[2], 'is_active'=>true ]);
            $data[3] = str_replace(',','',$data[3]);
            $data[4] = str_replace(',','',$data[4]);

            $product->name = $data[2];
            $product->code = $data[1] ?? Keygen::numeric(8)->generate();
            $product->type = 1;
            $product->barcode_symbology = 'C128';
            $product->brand_id = 1;
            $product->category_id = $data[6] ?? 0;
            $product->unit_id = 1;
            $product->purchase_unit_id = 1;
            $product->sale_unit_id = 1;
            $product->cost = $data[3] ?? 0;
            $product->price = $data[4] ?? 0;
            $product->tax_method = 1;
            $product->qty = $data[5] ?? 0;
            $product->is_active = true;
            $product->save();
        }
    }
}
