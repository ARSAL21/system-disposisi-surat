<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_submission_id')
                ->unique()
                ->constrained('letter_submissions')
                ->restrictOnDelete();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500)->unique();
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_documents');
    }
};
