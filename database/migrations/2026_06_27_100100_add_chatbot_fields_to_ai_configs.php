<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->string('public_key')->nullable()->unique()->after('id');
            $table->jsonb('allowed_domains')->nullable();      // empty/null = any domain
            $table->boolean('widget_enabled')->default(true);
        });

        // Backfill public keys for existing configs (chatbots).
        foreach (DB::table('ai_configs')->whereNull('public_key')->pluck('id') as $id) {
            DB::table('ai_configs')->where('id', $id)->update(['public_key' => 'pk_gtuh_'.Str::random(32)]);
        }
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn(['public_key', 'allowed_domains', 'widget_enabled']);
        });
    }
};
