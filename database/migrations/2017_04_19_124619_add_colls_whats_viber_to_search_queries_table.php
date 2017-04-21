<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollsWhatsViberToSearchQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
      public function up() {
        Schema::table('search_queries', function ($table) {
            $table->integer('phones_reserved_viber')->default(0)->after('phones');
            $table->integer('phones_reserved_wh')->default(0)->after('phones');
    
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
     
         Schema::table('search_queries', function ($table) {
            $table->dropColumn('phones_reserved_viber');
            $table->dropColumn('phones_reserved_wh');
    
        });
        
    }
}
