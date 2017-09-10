<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGroupOkVk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vk_links', function (Blueprint $table) {
            $table->integer('task_group_id');
        });
        Schema::table('ok_groups', function (Blueprint $table) {
            $table->integer('task_group_id');
        });
        Schema::table('site_links', function (Blueprint $table) {
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
        //
    }
}
