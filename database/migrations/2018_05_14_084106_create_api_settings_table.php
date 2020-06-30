<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->tinyInteger('material_bulk')->default(1);
            $table->tinyInteger('order_status')->default(1);
            $table->tinyInteger('order_detail')->default(1);
            $table->tinyInteger('order_item_complete')->default(1);
            $table->tinyInteger('delete_order_item_complete')->default(1);
            $table->tinyInteger('stock_item')->default(1);
            $table->tinyInteger('stock_item_delete')->default(1);
            $table->tinyInteger('ship_rate')->default(1);
            $table->tinyInteger('warehouse_option')->default(1);
            $table->tinyInteger('track_order')->default(1);
            $table->tinyInteger('stock')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_settings');
    }
}
