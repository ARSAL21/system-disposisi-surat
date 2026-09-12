<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('name');
            $table->string('normalized_email')->index();
            $table->string('account_type', 20);
            $table->string('phone_number', 50)->nullable();
            $table->string('token_hash', 64)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('invited_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['normalized_email', 'accepted_at', 'revoked_at'], 'idx_user_invitations_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
