<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollsTempgoodproxy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up() {
        Schema::table('proxy_temp', function ($table) {
            $table->string('country',300)->nullable();
    
        });
        Schema::table('good_proxies', function ($table) {
            $table->string('country',300)->nullable();
    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
     
         Schema::table('proxy_temp', function ($table) {
            $table->dropColumn('country',300)->nullable();
    
        });
        Schema::table('good_proxies', function ($table) {
            $table->dropColumn('country',300)->nullable();
    
        });
    }
}
