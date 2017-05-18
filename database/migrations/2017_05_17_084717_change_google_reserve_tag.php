<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeGoogleReserveTag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function(Blueprint $table){
            $table->renameColumn('reserved', 'google_ru');
            $table->renameColumn('google_offset', 'google_ru_offset');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function(Blueprint $table){
            $table->renameColumn('google_ru', 'reserved');
            $table->renameColumn('google_ru_offset', 'google_offset');
        });
    }
}
