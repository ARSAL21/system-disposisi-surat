<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_submissions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('source', 20)->index();
            $table->string('status', 30)->index();
            $table->foreignId('submitted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('sender_organization_name', 200);
            $table->string('contact_name', 150);
            $table->string('contact_email');
            $table->string('contact_phone', 30)->nullable();
            $table->string('external_letter_number', 100)->nullable();
            $table->date('external_letter_date')->nullable();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();

            $table->index(['submitted_by_user_id', 'status']);
            $table->index(['submitted_by_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_submissions');
    }
};
