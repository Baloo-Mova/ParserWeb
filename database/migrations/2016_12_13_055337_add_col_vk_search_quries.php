<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColVkSearchQuries extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('search_queries', function ($table) {
            $table->string('vk_id',300)->after('skypes')->nullable();
            $table->integer('vk_reserved')->after('vk_name')->default(0);
            $table->integer('vk_sended')->after('vk_name')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('search_queries', function ($table) {
            $table->dropColumn('vk_id');
            $table->dropColumn('vk_reserved');
            $table->dropColumn('vk_sended');
        });
    }

}
