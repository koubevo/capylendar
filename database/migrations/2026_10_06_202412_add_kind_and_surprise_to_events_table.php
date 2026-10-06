<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('kind')->default('standard');
            $table->string('surprise_image_path')->nullable();
            $table->string('surprise_password')->nullable();
            $table->string('surprise_hint')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['kind', 'surprise_image_path', 'surprise_password', 'surprise_hint']);
        });
    }
};
