<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalFieldsToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function(Blueprint $table){
            $table->string('name')->nullable();
            $table->integer('actual_mark')->default(0);
            $table->integer('city_id')->nullable();
            $table->string('city_name')->nullable();
            $table->integer('task_group_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function(Blueprint $table){
            $table->dropColumn('name');
            $table->dropColumn('actual_mark');
            $table->dropColumn('city_id');
            $table->dropColumn('city_name');
        });
    }
}
