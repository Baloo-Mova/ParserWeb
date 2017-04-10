<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DelPropertiesUniqeProxyProxytempGoodproxies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proxy_temp', function ($table) {
            $table->dropUnique('proxy_temp_proxy_unique');
    
        });
        Schema::table('good_proxies', function ($table) {
           $table->dropUnique('good_proxies_proxy_unique');
    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
     
         Schema::table('proxy_temp', function ($table) {
            $table->string('proxy', 50)->unique()->change();
    
        });
        Schema::table('good_proxies', function ($table) {
            $table->string('proxy', 50)->unique()->change();
    
        });
    }
}
