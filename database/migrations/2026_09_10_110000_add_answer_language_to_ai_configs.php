<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            // ka | en | auto — the language the agent answers in. Defaults to
            // Georgian so existing agents keep behaving exactly as before.
            $table->string('answer_language', 8)->default('ka')->after('model_tier');
        });
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn('answer_language');
        });
    }
};
