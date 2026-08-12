<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationship_milestone_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relationship_settings_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('milestone_key');
            $table->date('milestone_on');
            $table->timestamp('delivered_at');
            $table->timestamps();

            $table->unique(['relationship_settings_id', 'user_id', 'milestone_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_milestone_deliveries');
    }
};
