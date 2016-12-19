<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTwLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tw_links', function (Blueprint $table) {
            $table->increments('id');
            $table->text('url');
            $table->integer('task_id');
            $table->boolean('reserved')->default(0);
            $table->integer('type');
            $table->integer('offset')->default(1);
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
        Schema::dropIfExists('tw_links');
    }
}
