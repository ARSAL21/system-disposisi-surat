<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standalone_outgoing_drafts', function (Blueprint $table): void {
            $table->foreignId('corrects_outgoing_letter_id')
                ->nullable()
                ->after('created_by_position_assignment_id')
                ->constrained('outgoing_letters', indexName: 'standalone_drafts_corrects_letter_fk')
                ->restrictOnDelete();
            $table->text('correction_reason')->nullable()->after('corrects_outgoing_letter_id');
        });

        Schema::create('outgoing_letter_delivery_links', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('outgoing_letter_delivery_id')
                ->constrained('outgoing_letter_deliveries', indexName: 'outgoing_delivery_links_delivery_fk')
                ->restrictOnDelete();
            $table->char('token_hash', 64);
            $table->string('recipient_email', 255);
            $table->timestamp('sent_at')->index();
            $table->timestamp('expires_at')->index();
            $table->foreignId('created_by_user_id')
                ->constrained('users', indexName: 'outgoing_delivery_links_actor_user_fk')
                ->restrictOnDelete();
            $table->foreignId('created_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_delivery_links_actor_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('token_hash', 'outgoing_delivery_links_token_unique');
            $table->index(['outgoing_letter_delivery_id', 'expires_at'], 'outgoing_delivery_links_delivery_expiry_index');
        });

        Schema::create('outgoing_letter_delivery_link_revocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outgoing_letter_delivery_link_id')
                ->constrained('outgoing_letter_delivery_links', indexName: 'outgoing_link_revocations_link_fk')
                ->restrictOnDelete();
            $table->string('reason', 40);
            $table->foreignId('revoked_by_user_id')
                ->constrained('users', indexName: 'outgoing_link_revocations_actor_user_fk')
                ->restrictOnDelete();
            $table->foreignId('revoked_by_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_link_revocations_actor_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('revoked_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('outgoing_letter_delivery_link_id', 'outgoing_link_revocations_link_unique');
        });

        Schema::create('outgoing_letter_internal_copy_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outgoing_letter_delivery_id')
                ->constrained('outgoing_letter_deliveries', indexName: 'outgoing_copy_notices_delivery_fk')
                ->restrictOnDelete();
            $table->foreignId('position_id')
                ->constrained('positions', indexName: 'outgoing_copy_notices_position_fk')
                ->restrictOnDelete();
            $table->foreignId('recipient_user_id')
                ->constrained('users', indexName: 'outgoing_copy_notices_user_fk')
                ->restrictOnDelete();
            $table->foreignId('recipient_position_assignment_id')
                ->constrained('position_assignments', indexName: 'outgoing_copy_notices_assignment_fk')
                ->restrictOnDelete();
            $table->timestamp('notified_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['outgoing_letter_delivery_id', 'position_id'], 'outgoing_copy_notices_delivery_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letter_internal_copy_notifications');
        Schema::dropIfExists('outgoing_letter_delivery_link_revocations');
        Schema::dropIfExists('outgoing_letter_delivery_links');

        Schema::table('standalone_outgoing_drafts', function (Blueprint $table): void {
            $table->dropForeign('standalone_drafts_corrects_letter_fk');
            $table->dropColumn(['corrects_outgoing_letter_id', 'correction_reason']);
        });
    }
};
