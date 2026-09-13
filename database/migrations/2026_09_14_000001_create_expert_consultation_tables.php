<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_position_id')->constrained('positions', indexName: 'pos_rel_source_fk')->restrictOnDelete();
            $table->foreignId('target_position_id')->constrained('positions', indexName: 'pos_rel_target_fk')->restrictOnDelete();
            $table->string('relationship_type', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['source_position_id', 'relationship_type'], 'pos_rel_source_type_uq');
            $table->index(['target_position_id', 'relationship_type'], 'pos_rel_target_type_ix');
        });

        Schema::create('expert_consultations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incoming_letter_id')->constrained('incoming_letters', indexName: 'expert_consult_letter_fk')->restrictOnDelete();
            $table->foreignId('letter_route_id')->constrained('letter_routes', indexName: 'expert_consult_route_fk')->restrictOnDelete();
            $table->foreignId('expert_position_id')->constrained('positions', indexName: 'expert_consult_position_fk')->restrictOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users', indexName: 'expert_consult_requester_fk')->restrictOnDelete();
            $table->foreignId('requested_by_position_assignment_id')->constrained('position_assignments', indexName: 'expert_consult_requester_assignment_fk')->restrictOnDelete();
            $table->string('status', 20)->index();
            $table->text('request_note')->nullable();
            $table->timestamp('requested_at')->index();
            $table->timestamp('reported_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users', indexName: 'expert_consult_canceller_fk')->restrictOnDelete();
            $table->foreignId('cancelled_by_position_assignment_id')->nullable()->constrained('position_assignments', indexName: 'expert_consult_canceller_assignment_fk')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['letter_route_id', 'expert_position_id', 'status'], 'expert_consult_route_position_ix');
            $table->index(['expert_position_id', 'status', 'requested_at'], 'expert_consult_expert_status_ix');
        });

        Schema::create('expert_consultation_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expert_consultation_id')->unique('expert_report_consultation_uq')->constrained('expert_consultations', indexName: 'expert_report_consultation_fk')->restrictOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users', indexName: 'expert_report_author_fk')->restrictOnDelete();
            $table->foreignId('reported_by_position_assignment_id')->constrained('position_assignments', indexName: 'expert_report_assignment_fk')->restrictOnDelete();
            $table->text('summary');
            $table->text('recommendation');
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('expert_consultation_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expert_consultation_id')->constrained('expert_consultations', indexName: 'expert_doc_consultation_fk')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('replaces_document_id')->nullable()->constrained('expert_consultation_documents', indexName: 'expert_doc_replaces_fk')->restrictOnDelete();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by_user_id')->constrained('users', indexName: 'expert_doc_uploader_fk')->restrictOnDelete();
            $table->foreignId('uploaded_by_position_assignment_id')->constrained('position_assignments', indexName: 'expert_doc_uploader_assignment_fk')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->unique(['expert_consultation_id', 'version_number'], 'expert_doc_consultation_number_uq');
            $table->unique(['expert_consultation_id', 'sha256'], 'expert_doc_consultation_hash_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_consultation_documents');
        Schema::dropIfExists('expert_consultation_reports');
        Schema::dropIfExists('expert_consultations');
        Schema::dropIfExists('position_relationships');
    }
};
