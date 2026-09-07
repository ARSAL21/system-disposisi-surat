<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_response_documents', function (Blueprint $table): void {
            $table->dropUnique('response_documents_dossier_kind_owner_unique');
            $table->unique(
                ['letter_response_dossier_id', 'kind', 'source_recipient_id'],
                'response_documents_dossier_kind_recipient_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('letter_response_documents', function (Blueprint $table): void {
            $table->dropUnique('response_documents_dossier_kind_recipient_unique');
            $table->unique(
                ['letter_response_dossier_id', 'kind', 'owner_position_id'],
                'response_documents_dossier_kind_owner_unique',
            );
        });
    }
};
