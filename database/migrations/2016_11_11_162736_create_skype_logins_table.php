<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSkypeLoginsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skype_logins', function (Blueprint $table) {
            $table->increments('id');
            $table->string('login', 255);
            $table->string('password', 255);
            $table->text('skypeToken')->nullable();
            $table->text('registrationToken')->nullable();
            $table->string('expiry', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('skype_logins');
    }
}
