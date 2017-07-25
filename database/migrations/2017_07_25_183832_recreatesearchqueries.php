<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Recreatesearchqueries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('search_queries');
        Schema::create('search_queries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('link', 255);
            $table->string('name')->default('');
            $table->string('city')->default('');
            $table->longText('contact_data')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
