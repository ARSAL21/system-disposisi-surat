<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letter_templates', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('organizational_unit_id')
                ->constrained('organizational_units', indexName: 'outgoing_templates_unit_fk')
                ->restrictOnDelete();
            $table->string('code', 80);
            $table->string('name', 150);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['organizational_unit_id', 'code'], 'outgoing_templates_unit_code_unique');
            $table->index(['organizational_unit_id', 'is_active'], 'outgoing_templates_unit_active_index');
        });

        Schema::create('outgoing_letter_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_template_id')
                ->constrained('outgoing_letter_templates', indexName: 'outgoing_template_versions_template_fk')
                ->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('replaces_version_id')
                ->nullable()
                ->constrained('outgoing_letter_template_versions', indexName: 'outgoing_template_versions_replaces_fk')
                ->restrictOnDelete();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->string('qr_page_mode', 20);
            $table->unsignedSmallInteger('qr_page_number')->nullable();
            $table->decimal('qr_x_ratio', 8, 6);
            $table->decimal('qr_y_ratio', 8, 6);
            $table->decimal('qr_width_ratio', 8, 6);
            $table->decimal('qr_height_ratio', 8, 6);
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users', indexName: 'outgoing_template_versions_uploader_user_fk')
                ->restrictOnDelete();
            $table->foreignId('uploaded_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_template_versions_uploader_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['outgoing_letter_template_id', 'version_number'], 'outgoing_template_versions_number_unique');
            $table->unique(['outgoing_letter_template_id', 'sha256'], 'outgoing_template_versions_hash_unique');
        });

        Schema::create('standalone_outgoing_drafts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('organizational_unit_id')
                ->constrained('organizational_units', indexName: 'standalone_outgoing_drafts_unit_fk')
                ->restrictOnDelete();
            $table->foreignId('outgoing_letter_template_version_id')
                ->constrained('outgoing_letter_template_versions', indexName: 'standalone_outgoing_drafts_template_version_fk')
                ->restrictOnDelete();
            $table->string('recipient_name', 150);
            $table->string('recipient_organization', 180)->nullable();
            $table->string('recipient_position', 150)->nullable();
            $table->text('recipient_address')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('subject', 500);
            $table->text('summary')->nullable();
            $table->string('status', 40)->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->foreignId('created_by_user_id')
                ->constrained('users', indexName: 'standalone_outgoing_drafts_creator_user_fk')
                ->restrictOnDelete();
            $table->foreignId('created_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'standalone_outgoing_drafts_creator_assignment_fk')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['organizational_unit_id', 'status'], 'standalone_outgoing_drafts_unit_status_index');
            $table->index(['created_by_user_id', 'status'], 'standalone_outgoing_drafts_creator_status_index');
        });

        Schema::create('standalone_outgoing_copy_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('standalone_outgoing_draft_id')
                ->constrained('standalone_outgoing_drafts', indexName: 'standalone_outgoing_copies_draft_fk')
                ->restrictOnDelete();
            $table->foreignId('position_id')
                ->constrained('positions', indexName: 'standalone_outgoing_copies_position_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['standalone_outgoing_draft_id', 'position_id'], 'standalone_outgoing_copies_unique');
        });

        Schema::create('standalone_outgoing_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('standalone_outgoing_draft_id')
                ->constrained('standalone_outgoing_drafts', indexName: 'standalone_outgoing_versions_draft_fk')
                ->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('replaces_version_id')
                ->nullable()
                ->constrained('standalone_outgoing_document_versions', indexName: 'standalone_outgoing_versions_replaces_fk')
                ->restrictOnDelete();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->text('revision_note')->nullable();
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users', indexName: 'standalone_outgoing_versions_uploader_user_fk')
                ->restrictOnDelete();
            $table->foreignId('uploaded_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'standalone_outgoing_versions_uploader_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['standalone_outgoing_draft_id', 'version_number'], 'standalone_outgoing_versions_number_unique');
            $table->unique(['standalone_outgoing_draft_id', 'sha256'], 'standalone_outgoing_versions_hash_unique');
        });

        Schema::create('standalone_outgoing_reviews', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('standalone_outgoing_draft_id')
                ->constrained('standalone_outgoing_drafts', indexName: 'standalone_outgoing_reviews_draft_fk')
                ->restrictOnDelete();
            $table->foreignId('standalone_outgoing_document_version_id')
                ->constrained('standalone_outgoing_document_versions', indexName: 'standalone_outgoing_reviews_version_fk')
                ->restrictOnDelete();
            $table->string('stage', 30);
            $table->string('decision', 30);
            $table->text('reason')->nullable();
            $table->foreignId('decided_by_user_id')
                ->constrained('users', indexName: 'standalone_outgoing_reviews_decider_user_fk')
                ->restrictOnDelete();
            $table->foreignId('decided_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'standalone_outgoing_reviews_decider_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['standalone_outgoing_document_version_id', 'stage'], 'standalone_outgoing_reviews_version_stage_unique');
            $table->index(['standalone_outgoing_draft_id', 'created_at'], 'standalone_outgoing_reviews_draft_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standalone_outgoing_reviews');
        Schema::dropIfExists('standalone_outgoing_document_versions');
        Schema::dropIfExists('standalone_outgoing_copy_recipients');
        Schema::dropIfExists('standalone_outgoing_drafts');
        Schema::dropIfExists('outgoing_letter_template_versions');
        Schema::dropIfExists('outgoing_letter_templates');
    }
};
