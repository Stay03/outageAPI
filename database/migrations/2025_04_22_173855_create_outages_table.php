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
        Schema::create('outages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->string('weather_condition');
            $table->float('temperature'); // Celsius
            $table->float('wind_speed'); // km/h
            $table->float('precipitation'); // mm
            $table->integer('day_of_week'); // 0-6, where 0 = Sunday
            $table->boolean('is_holiday');
            $table->timestamps();
            
            // Add indexes for frequently queried fields
            $table->index('start_time');
            $table->index('end_time');
            $table->index('day_of_week');
            $table->index('weather_condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outages');
    }
};