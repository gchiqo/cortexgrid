<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // The source system's own id (e.g. WooCommerce product id) — lets re-sync upsert instead of duplicate.
            $table->string('external_id')->nullable()->after('dataset_id');
            $table->index(['dataset_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['dataset_id', 'external_id']);
            $table->dropColumn('external_id');
        });
    }
};
