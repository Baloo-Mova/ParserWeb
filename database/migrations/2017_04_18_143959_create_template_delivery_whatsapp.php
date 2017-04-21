<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplateDeliveryWhatsapp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::create('template_delivery_whatsapp', function (Blueprint $table) {
            $table->increments('id');
            $table->text('text')->nullable();
            $table->integer('task_id')->index();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_delivery_whatsapp');
    }
}
