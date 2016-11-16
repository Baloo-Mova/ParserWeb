<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts_data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('login', 255);
            $table->string('password');
            $table->integer('type_id');
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_address')->nullable();
            $table->integer('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts_data');
    }
}
