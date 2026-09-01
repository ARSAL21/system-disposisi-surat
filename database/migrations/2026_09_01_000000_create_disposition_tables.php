<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createInstructionLabelsTable();
        $this->createDispositionsTable();
        $this->createDispositionRecipientsTable();
        $this->ensureDispositionIndexesAndForeignKeys();
        $this->createDispositionInstructionLabelTable();

        $now = now();

        DB::table('instruction_labels')->insertOrIgnore([
            ['code' => 'FOR_INFORMATION', 'name' => 'Untuk diketahui', 'description' => 'Disampaikan sebagai informasi.', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'FOLLOW_UP', 'name' => 'Tindak lanjuti', 'description' => 'Memerlukan tindak lanjut sesuai kewenangan.', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'REVIEW', 'name' => 'Pelajari dan telaah', 'description' => 'Memerlukan kajian atau telaah lebih lanjut.', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'COORDINATE', 'name' => 'Koordinasikan', 'description' => 'Memerlukan koordinasi dengan unit terkait.', 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ATTEND', 'name' => 'Hadiri', 'description' => 'Memerlukan kehadiran atau perwakilan.', 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'PREPARE_RESPONSE', 'name' => 'Siapkan jawaban', 'description' => 'Memerlukan penyusunan bahan jawaban.', 'is_active' => true, 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'URGENT', 'name' => 'Segera', 'description' => 'Memerlukan penanganan dengan prioritas tinggi.', 'is_active' => true, 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function createInstructionLabelsTable(): void
    {
        if (Schema::hasTable('instruction_labels')) {
            return;
        }

        Schema::create('instruction_labels', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    private function createDispositionsTable(): void
    {
        if (Schema::hasTable('dispositions')) {
            return;
        }

        Schema::create('dispositions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incoming_letter_id')
                ->constrained('incoming_letters')
                ->restrictOnDelete();
            $table->foreignId('source_route_id')
                ->nullable()
                ->unique()
                ->constrained('letter_routes')
                ->restrictOnDelete();
            $table->unsignedBigInteger('parent_recipient_id')->nullable();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('created_by_position_assignment_id')
                ->constrained('position_assignments')
                ->restrictOnDelete();
            $table->text('instruction_note')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['incoming_letter_id', 'created_at']);
            $table->index('parent_recipient_id');
            $table->index('created_by_user_id');
            $table->index('created_by_position_assignment_id');
        });
    }

    private function createDispositionRecipientsTable(): void
    {
        if (Schema::hasTable('disposition_recipients')) {
            return;
        }

        Schema::create('disposition_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('disposition_id')
                ->constrained('dispositions')
                ->restrictOnDelete();
            $table->foreignId('recipient_position_id')
                ->constrained('positions')
                ->restrictOnDelete();
            $table->string('status', 40)->index();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('completed_by_position_assignment_id')
                ->nullable()
                ->constrained('position_assignments', indexName: 'disp_recipients_completed_by_position_fk')
                ->restrictOnDelete();
            $table->text('completion_note')->nullable();
            $table->timestamps();

            $table->unique(
                ['disposition_id', 'recipient_position_id'],
                'disp_recipients_disposition_recipient_unique'
            );
            $table->index(
                ['recipient_position_id', 'status'],
                'disp_recipients_recipient_status_index'
            );
            $table->index('completed_at', 'disp_recipients_completed_at_index');
        });
    }

    private function ensureDispositionIndexesAndForeignKeys(): void
    {
        if (! Schema::hasIndex('disposition_recipients', 'disp_recipients_disposition_recipient_unique')) {
            Schema::table('disposition_recipients', function (Blueprint $table): void {
                $table->unique(
                    ['disposition_id', 'recipient_position_id'],
                    'disp_recipients_disposition_recipient_unique'
                );
            });
        }

        if (! Schema::hasIndex('disposition_recipients', 'disp_recipients_recipient_status_index')) {
            Schema::table('disposition_recipients', function (Blueprint $table): void {
                $table->index(
                    ['recipient_position_id', 'status'],
                    'disp_recipients_recipient_status_index'
                );
            });
        }

        if (! Schema::hasIndex('disposition_recipients', 'disp_recipients_completed_at_index')) {
            Schema::table('disposition_recipients', function (Blueprint $table): void {
                $table->index('completed_at', 'disp_recipients_completed_at_index');
            });
        }

        if (! $this->hasForeignKey('disposition_recipients', 'disp_recipients_completed_by_position_fk')) {
            Schema::table('disposition_recipients', function (Blueprint $table): void {
                $table->foreign('completed_by_position_assignment_id', 'disp_recipients_completed_by_position_fk')
                    ->references('id')
                    ->on('position_assignments')
                    ->restrictOnDelete();
            });
        }

        if (! $this->hasForeignKey('dispositions', 'dispositions_parent_recipient_fk')) {
            Schema::table('dispositions', function (Blueprint $table): void {
                $table->foreign('parent_recipient_id', 'dispositions_parent_recipient_fk')
                    ->references('id')
                    ->on('disposition_recipients')
                    ->restrictOnDelete();
            });
        }
    }

    private function createDispositionInstructionLabelTable(): void
    {
        if (Schema::hasTable('disposition_instruction_label')) {
            return;
        }

        Schema::create('disposition_instruction_label', function (Blueprint $table): void {
            $table->foreignId('disposition_id')
                ->constrained('dispositions')
                ->restrictOnDelete();
            $table->foreignId('instruction_label_id')
                ->constrained('instruction_labels')
                ->restrictOnDelete();

            $table->primary(['disposition_id', 'instruction_label_id']);
        });
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (($foreignKey['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    public function down(): void
    {
        Schema::dropIfExists('disposition_instruction_label');

        Schema::table('dispositions', function (Blueprint $table): void {
            $table->dropForeign('dispositions_parent_recipient_fk');
        });

        Schema::dropIfExists('disposition_recipients');
        Schema::dropIfExists('dispositions');
        Schema::dropIfExists('instruction_labels');
    }
};
