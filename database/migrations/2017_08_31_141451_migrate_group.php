<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('active_type')->default(1);
            $table->boolean('need_send')->default(0);
            $table->string('name', 100);
            $table->timestamps();
        });
        Schema::table('tasks', function (Blueprint $table){
            $table->dropColumn('active_type');
            $table->dropColumn('need_send');
            $table->dropColumn('ins_offset');
            $table->dropColumn('tw_offset');
            $table->dropColumn('fb_reserved');
            $table->dropColumn('fb_complete');
            $table->dropColumn('yandex_ua_reserved');
            $table->renameColumn('google_ru', 'google_ru_reserved');
            $table->integer('task_group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_groups');
    }
}
