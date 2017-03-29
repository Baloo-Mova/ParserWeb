<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('tasks', function ($table) {
            $table->integer('google_ua_reserved')->default(0);
            $table->integer('yandex_ua_reserved')->default(0);
            $table->integer('yandex_ru_reserved')->default(0);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('tasks', function ($table) {
            $table->dropColumn('google_ua_reserved');
            $table->dropColumn('yandex_ua_reserved');
            $table->dropColumn('yandex_ru_reserved');
        });
    }
}
