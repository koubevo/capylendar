<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_pairings', function (Blueprint $table): void {
            $table->text('claimed_token')->nullable()->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('watch_pairings', function (Blueprint $table): void {
            $table->dropColumn('claimed_token');
        });
    }
};
