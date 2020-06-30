<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'material_bulk', 'order_status','order_detail','order_item_complete','delete_order_item_complete','stock_item','stock_item_delete','ship_rate','warehouse_option','track_order','stock'
    ];
}
