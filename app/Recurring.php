<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recurring extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'recurring_id', 'plan','status'
    ];
}
