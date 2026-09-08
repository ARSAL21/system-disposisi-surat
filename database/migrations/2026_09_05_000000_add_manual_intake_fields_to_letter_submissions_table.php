<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_submissions', function (Blueprint $table): void {
            $table->string('contact_email')->nullable()->change();
            $table->timestamp('received_at')->nullable()->after('summary')->index();
        });
    }

    public function down(): void
    {
        DB::table('letter_submissions')
            ->whereNull('contact_email')
            ->update(['contact_email' => '']);

        Schema::table('letter_submissions', function (Blueprint $table): void {
            $table->dropIndex(['received_at']);
            $table->dropColumn('received_at');
            $table->string('contact_email')->nullable(false)->change();
        });
    }
};
