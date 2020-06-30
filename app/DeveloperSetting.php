<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeveloperSetting extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'store_id', 'warehouse_number', 'account_key', 'percentage_product', 'page_size', 'offset','warehouse_token'
    ];      
    
    public function get_user(){
         return $this->hasOne('App\User', 'id','user_id');
    }

}
