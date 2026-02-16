<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('storms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active'); // active, dissipated
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('max_wind_speed'); // km/h
            $table->float('min_pressure'); // hPa
            $table->json('path_history')->nullable(); // Previous coordinates
            $table->json('predicted_path')->nullable(); // Predicted future coordinates
            $table->timestamp('last_updated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storms');
    }
};
