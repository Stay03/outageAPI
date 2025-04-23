<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First drop the index on the end_time column
        Schema::table('outages', function (Blueprint $table) {
            $table->dropIndex('outages_end_time_index');
        });
        
        // Then drop the column and add it back as nullable
        Schema::table('outages', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
        
        Schema::table('outages', function (Blueprint $table) {
            $table->dateTime('end_time')->nullable()->after('start_time');
        });
        
        // Recreate the index on the end_time column
        Schema::table('outages', function (Blueprint $table) {
            $table->index('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the index first
        Schema::table('outages', function (Blueprint $table) {
            $table->dropIndex('outages_end_time_index');
        });
        
        // Drop and recreate the column without nullable
        Schema::table('outages', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
        
        Schema::table('outages', function (Blueprint $table) {
            $table->dateTime('end_time')->after('start_time');
        });
        
        // Recreate the index
        Schema::table('outages', function (Blueprint $table) {
            $table->index('end_time');
        });
    }
};