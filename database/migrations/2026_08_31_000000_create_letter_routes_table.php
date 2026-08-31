<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incoming_letter_id')
                ->unique()
                ->constrained('incoming_letters')
                ->restrictOnDelete();
            $table->foreignId('recipient_position_id')
                ->constrained('positions')
                ->restrictOnDelete();
            $table->foreignId('routed_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('routed_by_position_assignment_id')
                ->nullable()
                ->constrained('position_assignments')
                ->restrictOnDelete();
            $table->string('status', 40)->index();
            $table->timestamp('routed_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_position_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_routes');
    }
};
