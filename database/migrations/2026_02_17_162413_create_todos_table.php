<?php

use App\Enums\Capybara;
use App\Enums\Priority;
use App\Models\User;
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
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignIdFor(User::class, 'author_id')->constrained('users');
            $table->enum('capybara', array_column(Capybara::cases(), 'value'));
            $table->date('deadline');
            $table->string('description')->nullable();
            $table->enum('priority', array_column(Priority::cases(), 'value'))->default(Priority::Medium->value);
            $table->dateTime('finished_at')->nullable();
            $table->string('image_path')->nullable();
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
