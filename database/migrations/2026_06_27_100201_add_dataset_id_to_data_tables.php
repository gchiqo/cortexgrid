<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sources', 'documents', 'chunks', 'ai_configs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('dataset_id')->nullable()->after('tenant_id')
                    ->constrained()->nullOnDelete();
            });
        }

        Schema::table('chunks', function (Blueprint $table) {
            $table->index('dataset_id');
        });

        // Backfill: give each tenant a default dataset and assign existing rows to it.
        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            $datasetId = DB::table('datasets')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => 'ჩემი მონაცემები',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (['sources', 'documents', 'chunks', 'ai_configs'] as $tableName) {
                DB::table($tableName)
                    ->where('tenant_id', $tenantId)
                    ->whereNull('dataset_id')
                    ->update(['dataset_id' => $datasetId]);
            }
        }
    }

    public function down(): void
    {
        foreach (['sources', 'documents', 'chunks', 'ai_configs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('dataset_id');
            });
        }
    }
};
