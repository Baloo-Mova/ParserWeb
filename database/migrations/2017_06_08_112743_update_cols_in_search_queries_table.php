<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColsInSearchQueriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->dropColumn('vk_city');
            $table->dropColumn('vk_name');
            $table->dropColumn('fb_name');

            $table->string('name',500)->nullable();
            $table->string('city',500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->string('vk_name',500);
            $table->string('vk_city',500);
            $table->text('fb_name')->nullable();

            $table->dropColumn('name');
            $table->dropColumn('city');
        });

    }
}
