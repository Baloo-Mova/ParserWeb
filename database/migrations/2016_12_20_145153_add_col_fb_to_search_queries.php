<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColFbToSearchQueries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('search_queries', function ($table) {
            $table->string('fb_id',300)->nullable();
            $table->text('fb_name')->nullable();
            $table->integer('fb_sended')->default(0);
            $table->integer('fb_reserved')->default(0);
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
            $table->dropColumn('fb_id');
            $table->dropColumn('fb_name');
            $table->dropColumn('fb_reserved');
            $table->dropColumn('fb_sended');
        });
    }
}
