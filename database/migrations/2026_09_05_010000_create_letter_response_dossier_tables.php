<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('letter_response_dossiers')) {
            $this->repairPartiallyCreatedDossierTable();
        } else {
            Schema::create('letter_response_dossiers', function (Blueprint $table): void {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('incoming_letter_id')
                    ->unique()
                    ->constrained('incoming_letters', indexName: 'response_dossiers_incoming_letter_fk')
                    ->restrictOnDelete();
                $table->string('status', 30)->index();
                $table->timestamp('opened_at')->index();
                $table->timestamp('finalized_at')->nullable()->index();
                $table->foreignId('finalized_by_user_id')
                    ->nullable()
                    ->constrained('users', indexName: 'response_dossiers_finalizer_user_fk')
                    ->restrictOnDelete();
                $table->foreignId('finalized_by_position_assignment_id')
                    ->nullable()
                    ->constrained('position_assignments', indexName: 'response_dossiers_finalizer_assignment_fk')
                    ->restrictOnDelete();
                $table->timestamps();
            });
        }

        Schema::create('letter_response_documents', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('letter_response_dossier_id')
                ->constrained('letter_response_dossiers', indexName: 'response_documents_dossier_fk')
                ->restrictOnDelete();
            $table->string('kind', 50);
            $table->foreignId('owner_position_id')
                ->constrained('positions', indexName: 'response_documents_owner_position_fk')
                ->restrictOnDelete();
            $table->foreignId('source_recipient_id')
                ->nullable()
                ->constrained('disposition_recipients', indexName: 'response_documents_source_recipient_fk')
                ->restrictOnDelete();
            $table->foreignId('created_by_user_id')
                ->constrained('users', indexName: 'response_documents_creator_user_fk')
                ->restrictOnDelete();
            $table->foreignId('created_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'response_documents_creator_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['letter_response_dossier_id', 'kind', 'owner_position_id'],
                'response_documents_dossier_kind_owner_unique',
            );
            $table->index(['source_recipient_id', 'kind']);
        });

        Schema::create('letter_response_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('letter_response_document_id')
                ->constrained('letter_response_documents', indexName: 'response_versions_document_fk')
                ->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('replaces_version_id')
                ->nullable()
                ->constrained('letter_response_document_versions', indexName: 'response_versions_replaces_fk')
                ->restrictOnDelete();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->text('revision_note')->nullable();
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users', indexName: 'response_versions_uploader_user_fk')
                ->restrictOnDelete();
            $table->foreignId('uploaded_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'response_versions_uploader_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['letter_response_document_id', 'version_number'], 'response_versions_document_number_unique');
            $table->unique(['letter_response_document_id', 'sha256'], 'response_versions_document_hash_unique');
        });

        Schema::create('letter_response_document_sources', function (Blueprint $table): void {
            $table->foreignId('target_version_id')
                ->constrained('letter_response_document_versions', indexName: 'response_sources_target_version_fk')
                ->restrictOnDelete();
            $table->foreignId('source_version_id')
                ->constrained('letter_response_document_versions', indexName: 'response_sources_source_version_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['target_version_id', 'source_version_id'], 'response_document_sources_primary');
        });

        Schema::create('letter_response_reviews', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('letter_response_dossier_id')
                ->constrained('letter_response_dossiers', indexName: 'response_reviews_dossier_fk')
                ->restrictOnDelete();
            $table->foreignId('document_version_id')
                ->constrained('letter_response_document_versions', indexName: 'response_reviews_document_version_fk')
                ->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('reason');
            $table->foreignId('decided_by_user_id')
                ->constrained('users', indexName: 'response_reviews_decider_user_fk')
                ->restrictOnDelete();
            $table->foreignId('decided_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'response_reviews_decider_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['document_version_id', 'decision'], 'response_reviews_version_decision_unique');
            $table->index(
                ['letter_response_dossier_id', 'created_at'],
                'response_reviews_dossier_created_index',
            );
        });

        Schema::create('outgoing_letters', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('incoming_letter_id')
                ->nullable()
                ->constrained('incoming_letters', indexName: 'outgoing_letters_incoming_fk')
                ->restrictOnDelete();
            $table->foreignId('letter_response_dossier_id')
                ->nullable()
                ->constrained('letter_response_dossiers', indexName: 'outgoing_letters_dossier_fk')
                ->restrictOnDelete();
            $table->foreignId('source_document_version_id')
                ->constrained('letter_response_document_versions', indexName: 'outgoing_letters_source_version_fk')
                ->restrictOnDelete();
            $table->foreignId('signatory_position_id')
                ->constrained('positions', indexName: 'outgoing_letters_signatory_position_fk')
                ->restrictOnDelete();
            $table->string('subject');
            $table->string('status', 40)->index();
            $table->foreignId('authorized_by_user_id')
                ->constrained('users', indexName: 'outgoing_letters_authorizer_user_fk')
                ->restrictOnDelete();
            $table->foreignId('authorized_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_letters_authorizer_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('authorized_at')->index();
            $table->timestamps();

            $table->index(['letter_response_dossier_id', 'status']);
            $table->index(['incoming_letter_id', 'authorized_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
        Schema::dropIfExists('letter_response_reviews');
        Schema::dropIfExists('letter_response_document_sources');
        Schema::dropIfExists('letter_response_document_versions');
        Schema::dropIfExists('letter_response_documents');
        Schema::dropIfExists('letter_response_dossiers');
    }

    /**
     * Recover the exact partial state left by MySQL when this migration failed
     * while creating an overlong foreign-key identifier.
     */
    private function repairPartiallyCreatedDossierTable(): void
    {
        $indexes = collect(Schema::getIndexes('letter_response_dossiers'));
        $foreignKeys = collect(Schema::getForeignKeys('letter_response_dossiers'));

        $hasIndex = static fn (array $columns, bool $unique = false): bool => $indexes->contains(
            static fn (array $index): bool => $index['columns'] === $columns
                && (! $unique || $index['unique'] === true),
        );
        $hasFinalizerAssignmentForeignKey = $foreignKeys->contains(
            static fn (array $foreignKey): bool => $foreignKey['columns'] === ['finalized_by_position_assignment_id'],
        );

        Schema::table('letter_response_dossiers', function (Blueprint $table) use ($hasIndex, $hasFinalizerAssignmentForeignKey): void {
            if (! $hasIndex(['public_id'], true)) {
                $table->unique('public_id');
            }

            if (! $hasIndex(['incoming_letter_id'], true)) {
                $table->unique('incoming_letter_id');
            }

            if (! $hasIndex(['status'])) {
                $table->index('status');
            }

            if (! $hasIndex(['opened_at'])) {
                $table->index('opened_at');
            }

            if (! $hasIndex(['finalized_at'])) {
                $table->index('finalized_at');
            }

            if (! $hasFinalizerAssignmentForeignKey) {
                $table->foreign(
                    'finalized_by_position_assignment_id',
                    'response_dossiers_finalizer_assignment_fk',
                )->references('id')->on('position_assignments')->restrictOnDelete();
            }
        });
    }
};
