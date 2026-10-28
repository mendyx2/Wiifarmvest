<?php
namespace App\Lib;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
class Searchable{
    public function searchable(){
        return function($params, $like = true) {
            $search = request()->search;
            if (!$search) {
                return $this;
            }

            if (!is_array($params)) {
                throw new \Exception("Search parameters should be an array");
            }

            $search = $like ? "%$search%" : $search;
            $this->where(function ($q) use ($params, $search) {
                foreach ($params as $param) {
                    $relationData = explode(':', $param);
                    if (@$relationData[1]) {
                        foreach (explode(',', $relationData[1]) as $column) {
                            if(!$relationData[0]){
                                continue;
                            }
                            $q->orWhereHas($relationData[0], function ($q) use ($column, $search) {
                                $q->where($column, 'like', $search);
                            });
                        }
                    } else {
                        $column = $param;
                        $q->orWhere($column, 'LIKE', $search);
                    }
                }
            });
            return $this;
        };
    }
    public function filter() {
        return function($params){

            if (!is_array($params)) {
                throw new \Exception("Search parameters should be an array");
            }

            foreach ($params as $param) {
                $relationData = explode(':', $param);
                $filters = array_keys(request()->all());
                if (@$relationData[1]) {
                    foreach (explode(',', $relationData[1]) as $column) {
                        if (request()->$column != null) {
                            $this->whereHas($relationData[0],function($q) use ($column, $relationData){
                                $q->where($column,request()->$column);
                            });
                        }
                    }
                }else{
                    $column = $param;
                    if (in_array($column, $filters) && request()->$column != null) {
                        if(gettype(request()->$column) =='array'){
                            $this->whereIn($column, request()->$column);
                        }else{
                            $this->where($column, request()->$column);
                        }
                    }
                }
            }
            return $this;
        };
    }
    public function dateFilter(){
        return function($column = 'created_at'){
            if (!request()->date) {
                return $this;
            }
            try{
                // "start - end". Split on " - " first so ISO dates (2026-10-01 - 2026-10-26,
                // sent by the React filters) work; a bare "-" still works for "10/01/2026-10/26/2026".
                $raw  = (string) request()->date;
                $date = match (true) {
                    str_contains($raw, ' - ')                         => explode(' - ', $raw, 2),
                    (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($raw)) => [$raw], // one ISO day
                    default                                           => explode('-', $raw),
                };
                $startDate = Carbon::parse(trim($date[0]))->format('Y-m-d');
                $endDate = @$date[1] ? Carbon::parse(trim(@$date[1]))->format('Y-m-d') : $startDate;
            }catch(\Exception $e){
                throw ValidationException::withMessages(['error' => 'Invalid date format']);
            }
            return $this->whereDate($column, '>=', $startDate)->whereDate($column, '<=', $endDate);
        };
    }
}
