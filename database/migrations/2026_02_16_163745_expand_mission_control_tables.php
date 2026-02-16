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
        Schema::table('satellites', function (Blueprint $table) {
            $table->json('api_config')->nullable()->after('status');
            $table->string('data_source')->nullable()->after('api_config');
            $table->string('source_url')->nullable()->after('data_source');
            $table->string('dataset_name')->nullable()->after('source_url');
            $table->integer('priority')->default(0)->after('dataset_name');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->integer('rate_limit')->default(60)->after('is_active'); // rpm
            $table->integer('monthly_quota')->default(10000)->after('rate_limit');
            $table->integer('usage_count')->default(0)->after('monthly_quota');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->after('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satellites', function (Blueprint $table) {
            $table->dropColumn(['api_config', 'data_source', 'source_url', 'dataset_name', 'priority']);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['rate_limit', 'monthly_quota', 'usage_count', 'user_id']);
        });
    }
};
