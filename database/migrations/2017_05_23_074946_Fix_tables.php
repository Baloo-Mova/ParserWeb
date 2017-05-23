<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('proxy_temp');
        Schema::dropIfExists('good_proxies');
        Schema::dropIfExists('proxy');
        Schema::create('proxy', function (Blueprint $table) {
            $table->increments('id');
            $table->string('proxy');
            $table->string('login');
            $table->string('password');
            $table->smallInteger('google')->default(0);
            $table->boolean('google_reserved')->default(0);
            $table->smallInteger('yandex_ru')->default(0);
            $table->boolean('yandex_ru_reserved')->default(0);
            $table->smallInteger('fb')->default(0);
            $table->boolean('fb_reserved')->default(0);
            $table->smallInteger('vk')->default(0);
            $table->boolean('vk_reserved')->default(0);
            $table->smallInteger('ok')->default(0);
            $table->boolean('ok_reserved')->default(0);
            $table->smallInteger('skype')->default(0);
            $table->boolean('skype_reserved')->default(0);
            $table->smallInteger('wh')->default(0);
            $table->boolean('wh_reserved')->default(0);
            $table->smallInteger('viber')->default(0);
            $table->boolean('viber_reserved')->default(0);
            $table->smallInteger('twitter')->default(0);
            $table->boolean('twitter_reserved')->default(0);
            $table->smallInteger('instagram')->default(0);
            $table->boolean('instagram_reserved')->default(0);
            $table->boolean('valid')->default(1);
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
    }
}
