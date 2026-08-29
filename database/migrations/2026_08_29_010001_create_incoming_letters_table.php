<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_letters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_submission_id')
                ->unique()
                ->constrained('letter_submissions')
                ->restrictOnDelete();
            $table->string('agenda_number', 50);
            $table->smallInteger('agenda_year');
            $table->foreignId('sender_organization_id')
                ->constrained('sender_organizations')
                ->restrictOnDelete();
            $table->string('external_letter_number', 100)->nullable()->index();
            $table->date('external_letter_date')->nullable();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->timestamp('received_at')->index();
            $table->string('status', 40)->index();
            $table->foreignId('registered_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('registered_by_position_assignment_id')
                ->nullable()
                ->constrained('position_assignments')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['agenda_year', 'agenda_number']);
            $table->index('sender_organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_letters');
    }
};
