<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodProxiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_proxies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('proxy', 50)->unique();
            $table->tinyInteger('mail');
            $table->tinyInteger('yandex');
            $table->tinyInteger('google');
            $table->tinyInteger('mailru');
            $table->tinyInteger('twitter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('good_proxies');
    }
}
