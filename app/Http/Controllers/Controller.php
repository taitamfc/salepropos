<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function handleFilter($query,$request,$tabel = ''){
        $filter = $request->filter && count($request->filter) ? $request->filter : [];
        $limit = $request->limit ?? 20;
        
        $onlyActive = $request->onlyActive ?? false;
        if($onlyActive){
            $query->where($tabel.'is_active',1);
        }
        if( isset($filter['is_active']) ){
            $is_active = $filter['is_active'];
            if($is_active == 1){
                $query->where($tabel.'is_active',1);
            }
            if($is_active == 2){
                $query->where($tabel.'is_active',0);
            }
            unset($filter['is_active']);
        }
        if(isset($filter['fromDate']) && $filter['fromDate']){
            $query->where($tabel.'created_at','>=',$filter['fromDate'].' 00:00:00');
            unset($filter['fromDate']);
        }
        if ( isset($filter['toDate']) && $filter['toDate']) {
            $query->where($tabel.'created_at','<=',$filter['toDate'].' 23:59:59');
            unset($filter['toDate']);

        }
        if( count($filter) ){
            foreach($filter as $field => $value){
                $value = trim($value);
                if($value === '') continue;
                if($field == 'name' || $field == 'title'){
                    $query->where($tabel.$field,'LIKE','%'.$value.'%');
                }else{
                    $query->where($tabel.$field,$value);
                }
            }
        }
        if( $limit != -1 ){
            $items = $query->paginate($limit);
        }else{
            $items = $query->get();
        }
        return $items;
    }
}
