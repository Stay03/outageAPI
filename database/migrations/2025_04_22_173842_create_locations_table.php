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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('locality')->nullable();  // Added locality field
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->double('latitude', 10, 7);  // Precision for latitude
            $table->double('longitude', 10, 7); // Precision for longitude
            $table->timestamps();
            
            // Add indexes
            $table->index('name');
            $table->index('city');
            $table->index('locality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};