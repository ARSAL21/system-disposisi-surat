<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposition_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('disposition_recipient_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('created_by_position_assignment_id')
                ->constrained('position_assignments')
                ->restrictOnDelete();
            $table->text('note');
            $table->timestamp('created_at');

            $table->index(
                ['disposition_recipient_id', 'created_at', 'id'],
                'disposition_follow_ups_recipient_time_index',
            );
            $table->index(
                ['created_by_user_id', 'created_at'],
                'disposition_follow_ups_creator_time_index',
            );
            $table->index(
                ['created_by_position_assignment_id', 'created_at'],
                'disposition_follow_ups_assignment_time_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposition_follow_ups');
    }
};
