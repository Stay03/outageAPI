<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalWeatherFieldsToOutagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outages', function (Blueprint $table) {
            $table->integer('humidity')->nullable()->after('precipitation');
            $table->float('pressure', 8, 2)->nullable()->after('humidity');
            $table->integer('cloud')->nullable()->after('pressure');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outages', function (Blueprint $table) {
            $table->dropColumn(['humidity', 'pressure', 'cloud']);
        });
    }
}