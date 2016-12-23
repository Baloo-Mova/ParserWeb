<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColFbTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('tasks', function ($table) {

            $table->integer('fb_reserved')->default(0);
            $table->integer('fb_complete')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('tasks', function ($table) {
            $table->dropColumn('fb_reserved');
            $table->dropColumn('fb_complete');
        });
    }
}
