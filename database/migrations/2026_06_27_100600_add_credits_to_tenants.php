<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Free tier — deliberately huge so demos/testing never run out.
    private const FREE_CREDITS = 100000000;

    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->bigInteger('credits')->default(self::FREE_CREDITS)->after('name');
        });

        DB::table('tenants')->update(['credits' => self::FREE_CREDITS]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('credits');
        });
    }
};
