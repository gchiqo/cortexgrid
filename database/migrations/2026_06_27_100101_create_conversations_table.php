<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_config_id')->constrained('ai_configs')->cascadeOnDelete();
            $table->string('visitor_id')->nullable();  // anonymous site visitor (from localStorage)
            $table->string('title')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'ai_config_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
