<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSendData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('template_delivery_fb');
        Schema::dropIfExists('template_delivery_mails');
        Schema::dropIfExists('template_delivery_mails_files');
        Schema::dropIfExists('template_delivery_ok');
        Schema::dropIfExists('template_delivery_skypes');
        Schema::dropIfExists('template_delivery_tw');
        Schema::dropIfExists('template_delivery_viber');
        Schema::dropIfExists('template_delivery_vk');
        Schema::dropIfExists('template_delivery_whatsapp');

        Schema::create('delivery_data', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('payload');
            $table->integer('task_group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_data');
    }
}
