<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            if (! Schema::hasColumn('outgoing_letters', 'origin')) {
                $table->string('origin', 20)->default('RESPONSE')->after('public_id')->index();
            }
            if (! Schema::hasColumn('outgoing_letters', 'standalone_outgoing_draft_id')) {
                $table->foreignId('standalone_outgoing_draft_id')
                    ->nullable()
                    ->after('letter_response_dossier_id')
                    ->constrained('standalone_outgoing_drafts', indexName: 'outgoing_letters_standalone_draft_fk')
                    ->restrictOnDelete();
            }
            if (! Schema::hasIndex('outgoing_letters', 'outgoing_letters_standalone_draft_unique')) {
                $table->unique('standalone_outgoing_draft_id', 'outgoing_letters_standalone_draft_unique');
            }
            $table->foreignId('source_document_version_id')->nullable()->change();
        });

        $createElectronicApprovals = function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_id')
                ->constrained('outgoing_letters', indexName: 'outgoing_electronic_approvals_letter_fk')
                ->restrictOnDelete();
            $table->index(['outgoing_letter_id', 'approved_at'], 'outgoing_electronic_approvals_letter_approved_index');
            $table->string('method', 30);
            $table->char('source_document_sha256', 64);
            $table->foreignId('final_document_version_id')
                ->nullable()
                ->constrained('outgoing_letter_document_versions', indexName: 'outgoing_electronic_approvals_final_version_fk')
                ->restrictOnDelete();
            $table->char('verification_token_hash', 64)->nullable();
            $table->foreignId('approved_by_user_id')
                ->constrained('users', indexName: 'outgoing_electronic_approvals_actor_user_fk')
                ->restrictOnDelete();
            $table->foreignId('approved_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_electronic_approvals_actor_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('approved_at')->index();
            $table->timestamp('created_at')->useCurrent();

            // MySQL membatasi panjang nama identifier index menjadi 64 karakter.
            $table->unique('verification_token_hash', 'outgoing_approval_token_unique');
        };
        if (! Schema::hasTable('outgoing_letter_electronic_approvals')) {
            Schema::create('outgoing_letter_electronic_approvals', $createElectronicApprovals);
        } elseif (! Schema::hasIndex('outgoing_letter_electronic_approvals', 'outgoing_approval_token_unique')) {
            Schema::table('outgoing_letter_electronic_approvals', function (Blueprint $table): void {
                $table->unique('verification_token_hash', 'outgoing_approval_token_unique');
            });
        }

        $createSekdaDecisions = function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_id')
                ->constrained('outgoing_letters', indexName: 'standalone_sekda_decisions_letter_fk')
                ->restrictOnDelete();
            $table->string('decision', 40);
            $table->text('note')->nullable();
            $table->foreignId('decided_by_user_id')
                ->constrained('users', indexName: 'standalone_sekda_decisions_actor_user_fk')
                ->restrictOnDelete();
            $table->foreignId('decided_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'standalone_sekda_decisions_actor_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['outgoing_letter_id', 'created_at'], 'standalone_sekda_decisions_letter_created_index');
        };
        if (! Schema::hasTable('standalone_outgoing_sekda_decisions')) {
            Schema::create('standalone_outgoing_sekda_decisions', $createSekdaDecisions);
        }

        $createManualSignatureReviews = function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_document_version_id')
                ->constrained('outgoing_letter_document_versions', indexName: 'outgoing_manual_reviews_version_fk')
                ->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('note')->nullable();
            $table->foreignId('reviewed_by_user_id')
                ->constrained('users', indexName: 'outgoing_manual_reviews_actor_user_fk')
                ->restrictOnDelete();
            $table->foreignId('reviewed_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_manual_reviews_actor_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->unique('outgoing_letter_document_version_id', 'outgoing_manual_review_version_unique');
        };
        if (! Schema::hasTable('outgoing_letter_manual_signature_reviews')) {
            Schema::create('outgoing_letter_manual_signature_reviews', $createManualSignatureReviews);
        } elseif (! Schema::hasIndex('outgoing_letter_manual_signature_reviews', 'outgoing_manual_review_version_unique')) {
            Schema::table('outgoing_letter_manual_signature_reviews', function (Blueprint $table): void {
                $table->unique('outgoing_letter_document_version_id', 'outgoing_manual_review_version_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letter_manual_signature_reviews');
        Schema::dropIfExists('standalone_outgoing_sekda_decisions');
        Schema::dropIfExists('outgoing_letter_electronic_approvals');

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropUnique('outgoing_letters_standalone_draft_unique');
            $table->dropForeign('outgoing_letters_standalone_draft_fk');
            $table->dropIndex(['origin']);
            $table->dropColumn(['standalone_outgoing_draft_id', 'origin']);
            $table->foreignId('source_document_version_id')->nullable(false)->change();
        });
    }
};
