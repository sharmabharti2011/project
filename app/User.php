<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id','name', 'email', 'password','shop_name','shop_url','acccess_token','status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    public function get_Webhook (){
         return $this->belongsTo('App\Webhook', 'id','user_id');
    }
    public function get_api_setting (){
         return $this->belongsTo('App\ApiSetting', 'id','user_id');
    }
    public function get_dev_setting (){
         return $this->belongsTo('App\DeveloperSetting', 'id','user_id');
    }
}
