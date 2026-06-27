<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');                       // e.g. "Customer assistant", "PC builder"
            $table->text('system_prompt');                // Georgian system prompt
            $table->jsonb('data_scope')->nullable();      // which sources/types this config may read
            $table->jsonb('enabled_tools')->nullable();   // which actions (function calls) are allowed
            $table->string('model_tier')->default('standard'); // fast | standard | max
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
};
