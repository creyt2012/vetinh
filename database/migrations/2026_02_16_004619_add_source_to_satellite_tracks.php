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
        Schema::table('satellite_tracks', function (Blueprint $table) {
            $table->string('source')->nullable()->after('velocity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satellite_tracks', function (Blueprint $table) {
            //
        });
    }
};
