<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationship_settings', function (Blueprint $table) {
            $table->id();
            $table->date('started_on')->nullable();
            $table->string('name')->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TRIGGER relationship_settings_singleton_id_check
                BEFORE INSERT ON relationship_settings
                WHEN NEW.id <> 1
                BEGIN
                    SELECT RAISE(ABORT, 'relationship_settings id must be 1');
                END
                SQL);

            return;
        }

        DB::statement(
            'ALTER TABLE relationship_settings ADD CONSTRAINT relationship_settings_singleton_id_check CHECK (id = 1)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_settings');
    }
};
