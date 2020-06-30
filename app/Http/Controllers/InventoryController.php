<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;

class InventoryController extends Controller
{
    public function handleInventoryItems(Request $request,$slug){
         Log::info('Inventory '.$slug.':'.json_encode($request->all()));
    }
}
