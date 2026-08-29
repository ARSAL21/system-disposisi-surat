<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('letter_submission_id')
                ->constrained('letter_submissions')
                ->restrictOnDelete();
            $table->string('outcome', 40)->index();
            $table->json('checklist');
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('created_by_position_assignment_id')
                ->constrained('position_assignments')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['letter_submission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_reviews');
    }
};
