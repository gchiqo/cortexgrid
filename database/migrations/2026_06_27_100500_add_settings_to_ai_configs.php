<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            // { "widget": {color,position,greeting,title,launcher}, "rerank": bool }
            $table->jsonb('settings')->nullable()->after('data_scope');
        });
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
