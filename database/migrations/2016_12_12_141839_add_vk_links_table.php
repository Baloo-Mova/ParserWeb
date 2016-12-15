<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVkLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vk_links', function (Blueprint $table) {
            $table->increments('id');
            $table->text('link')->nullable();
            $table->string('vkuser_id',200)->nullable();
            $table->integer('task_id')->index();
            $table->integer('reserved')->default(0);
            $table->integer('getusers_reserved')->default(0);
            $table->integer('getusers_status')->default(0);
            $table->integer('parsed')->default(0);
            $table->integer('type')->nullable();
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
        Schema::dropIfExists('vk_links');
    }
}
