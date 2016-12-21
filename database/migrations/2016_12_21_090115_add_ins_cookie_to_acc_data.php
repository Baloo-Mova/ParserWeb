<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInsCookieToAccData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_data', function ($table) {
            $table->text('ins_cookie')->nullable();
            $table->string('ins_tkn', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts_data', function ($table) {
            $table->text('ins_cookie')->nullable();
            $table->string('ins_tkn', 255)->nullable();
        });
    }
}
