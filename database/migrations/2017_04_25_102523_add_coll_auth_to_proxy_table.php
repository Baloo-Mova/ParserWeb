<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollAuthToProxyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   
    public function up() {
        Schema::table('proxy', function ($table) {
            $table->integer('valid')->default(1)->after('proxy');
            
            $table->string('country',300)->after('proxy')->nullable();
            $table->integer('ins')->default(0)->after('proxy');
            $table->integer('twitter')->default(0)->after('proxy');
            $table->integer('viber')->default(0)->after('proxy');
            $table->integer('wh')->default(0)->after('proxy');
            $table->integer('ok')->default(0)->after('proxy');
            $table->integer('vk')->default(0)->after('proxy');
            $table->integer('fb')->default(0)->after('proxy');
            $table->integer('yandex_ru')->default(0)->after('proxy');
            $table->integer('google')->default(0)->after('proxy');
            
            $table->string('password',300)->after('proxy');
            $table->string('login',300)->after('proxy');
    
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
     
         Schema::table('proxy', function ($table) {
            $table->dropColumn('viber');
            $table->dropColumn('wh');
            $table->dropColumn('country');
            $table->dropColumn('ins');
            $table->dropColumn('twitter');
            $table->dropColumn('ok');
            $table->dropColumn('vk');
            $table->dropColumn('fb');
            $table->dropColumn('yandex_ru');
            $table->dropColumn('google');
            $table->dropColumn('password');
            $table->dropColumn('login');
    
        });
        
    }
}
