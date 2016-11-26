<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplateDeliveryMailsFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_delivery_mails_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mail_id');
            $table->string('name', 255);
            $table->text('path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_delivery_mails_files');
    }
}
