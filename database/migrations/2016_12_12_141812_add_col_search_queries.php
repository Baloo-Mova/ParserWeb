<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColSearchQueries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
      public function up()
    {
        Schema::table('search_queries', function ($table) {
     $table->string('vk_name',500)->after('skypes')->nullable();
     $table->string('vk_city',500)->after('skypes')->nullable();
     
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('search_queries', function ($table) {
    $table->dropColumn('vk_name');
    $table->dropColumn('vk_city');
    
});
    }
}
