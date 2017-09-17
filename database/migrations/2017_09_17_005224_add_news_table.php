<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vk_news', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('task_group_id');
            $table->integer('post_id');
            $table->integer('owner_id');
            $table->integer('task_id');
            $table->integer('reserved')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
