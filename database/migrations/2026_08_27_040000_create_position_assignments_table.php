<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('position_id');
            $table->timestamp('started_at', precision: 6);
            $table->timestamp('ended_at', precision: 6)->nullable();
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->timestamps(precision: 6);

            $table->index(['position_id', 'ended_at']);
            $table->index(['user_id', 'ended_at']);
            $table->index('assigned_by_user_id');
        });

        Schema::table('position_assignments', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->restrictOnDelete();
            $table->foreign('assigned_by_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE position_assignments
                ADD CONSTRAINT position_assignments_valid_period_check
                CHECK (ended_at IS NULL OR ended_at > started_at)
            SQL);
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index('actor_position_assignment_id');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreign('actor_position_assignment_id')
                ->references('id')
                ->on('position_assignments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['actor_position_assignment_id']);
            $table->dropIndex(['actor_position_assignment_id']);
        });

        Schema::dropIfExists('position_assignments');
    }
};
