<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSkypeTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skype_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->text('login');
            $table->text('skypeToken');
            $table->text('registrationToken');
            $table->string('expiry', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('skype_tokens');
    }
}
