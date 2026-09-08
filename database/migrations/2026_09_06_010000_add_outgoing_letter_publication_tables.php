<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_response_dossiers', function (Blueprint $table): void {
            $table->timestamp('fulfilled_at')->nullable()->after('finalized_at')->index();
            $table->foreignId('fulfilled_by_user_id')
                ->nullable()
                ->after('fulfilled_at')
                ->constrained('users', indexName: 'response_dossiers_fulfiller_user_fk')
                ->restrictOnDelete();
            $table->foreignId('fulfilled_by_position_assignment_id')
                ->nullable()
                ->after('fulfilled_by_user_id')
                ->constrained('position_assignments', indexName: 'response_dossiers_fulfiller_assignment_fk')
                ->restrictOnDelete();
        });

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->string('outgoing_number', 100)->nullable()->after('status');
            $table->unsignedSmallInteger('agenda_year')->nullable()->after('outgoing_number');
            $table->date('letter_date')->nullable()->after('agenda_year');
            $table->foreignId('numbered_by_user_id')
                ->nullable()
                ->after('letter_date')
                ->constrained('users', indexName: 'outgoing_letters_numberer_user_fk')
                ->restrictOnDelete();
            $table->foreignId('numbered_by_position_assignment_id')
                ->nullable()
                ->after('numbered_by_user_id')
                ->constrained('position_assignments', indexName: 'outgoing_letters_numberer_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('numbered_at')->nullable()->after('numbered_by_position_assignment_id')->index();
            $table->text('withdrawal_reason')->nullable()->after('numbered_at');
            $table->foreignId('withdrawn_by_user_id')
                ->nullable()
                ->after('withdrawal_reason')
                ->constrained('users', indexName: 'outgoing_letters_withdrawer_user_fk')
                ->restrictOnDelete();
            $table->foreignId('withdrawn_by_position_assignment_id')
                ->nullable()
                ->after('withdrawn_by_user_id')
                ->constrained('position_assignments', indexName: 'outgoing_letters_withdrawer_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('withdrawn_at')->nullable()->after('withdrawn_by_position_assignment_id')->index();
            $table->foreignId('corrects_outgoing_letter_id')
                ->nullable()
                ->after('withdrawn_at')
                ->constrained('outgoing_letters', indexName: 'outgoing_letters_correction_fk')
                ->restrictOnDelete();

            $table->unique(['agenda_year', 'outgoing_number'], 'outgoing_letters_year_number_unique');
        });

        Schema::create('outgoing_letter_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_id')
                ->constrained('outgoing_letters', indexName: 'outgoing_versions_letter_fk')
                ->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('replaces_version_id')
                ->nullable()
                ->constrained('outgoing_letter_document_versions', indexName: 'outgoing_versions_replaces_fk')
                ->restrictOnDelete();
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->text('upload_note');
            $table->foreignId('uploaded_by_user_id')
                ->constrained('users', indexName: 'outgoing_versions_uploader_user_fk')
                ->restrictOnDelete();
            $table->foreignId('uploaded_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_versions_uploader_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['outgoing_letter_id', 'version_number'], 'outgoing_versions_letter_number_unique');
            $table->unique(['outgoing_letter_id', 'sha256'], 'outgoing_versions_letter_hash_unique');
        });

        Schema::create('outgoing_letter_document_reviews', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_document_version_id')
                ->constrained('outgoing_letter_document_versions', indexName: 'outgoing_reviews_version_fk')
                ->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('note')->nullable();
            $table->foreignId('decided_by_user_id')
                ->constrained('users', indexName: 'outgoing_reviews_decider_user_fk')
                ->restrictOnDelete();
            $table->foreignId('decided_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_reviews_decider_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique('outgoing_letter_document_version_id', 'outgoing_reviews_version_unique');
        });

        Schema::create('outgoing_letter_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_id')
                ->unique()
                ->constrained('outgoing_letters', indexName: 'outgoing_deliveries_letter_fk')
                ->restrictOnDelete();
            $table->string('method', 30);
            $table->string('recipient_name', 150)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('delivered_by_user_id')
                ->constrained('users', indexName: 'outgoing_deliveries_actor_user_fk')
                ->restrictOnDelete();
            $table->foreignId('delivered_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_deliveries_actor_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('delivered_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letter_deliveries');
        Schema::dropIfExists('outgoing_letter_document_reviews');
        Schema::dropIfExists('outgoing_letter_document_versions');

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropUnique('outgoing_letters_year_number_unique');
            $table->dropForeign('outgoing_letters_numberer_user_fk');
            $table->dropForeign('outgoing_letters_numberer_assignment_fk');
            $table->dropForeign('outgoing_letters_withdrawer_user_fk');
            $table->dropForeign('outgoing_letters_withdrawer_assignment_fk');
            $table->dropForeign('outgoing_letters_correction_fk');
            $table->dropColumn([
                'outgoing_number',
                'agenda_year',
                'letter_date',
                'numbered_by_user_id',
                'numbered_by_position_assignment_id',
                'numbered_at',
                'withdrawal_reason',
                'withdrawn_by_user_id',
                'withdrawn_by_position_assignment_id',
                'withdrawn_at',
                'corrects_outgoing_letter_id',
            ]);
        });

        Schema::table('letter_response_dossiers', function (Blueprint $table): void {
            $table->dropForeign('response_dossiers_fulfiller_user_fk');
            $table->dropForeign('response_dossiers_fulfiller_assignment_fk');
            $table->dropColumn([
                'fulfilled_at',
                'fulfilled_by_user_id',
                'fulfilled_by_position_assignment_id',
            ]);
        });
    }
};
