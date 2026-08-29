<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incoming_letter_id')
                ->constrained('incoming_letters')
                ->restrictOnDelete();
            $table->foreignId('source_submission_document_id')
                ->nullable()
                ->unique()
                ->constrained('submission_documents')
                ->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('replaces_document_id')
                ->nullable()
                ->constrained('letter_documents')
                ->restrictOnDelete();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->text('correction_reason')->nullable();
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['incoming_letter_id', 'version_number']);
            $table->unique(['incoming_letter_id', 'sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_documents');
    }
};
