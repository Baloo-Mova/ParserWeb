<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColFbToAccountData extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('accounts_data', function ($table) {
            $table->string('fb_user_id',300)->nullable();
            $table->text('fb_access_token')->nullable();
            $table->text('fb_cookie')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('accounts_data', function ($table) {
            $table->dropColumn('fb_user_id');
            $table->dropColumn('fb_access_token');
            $table->dropColumn('fb_cookie');
        });
    }

}
