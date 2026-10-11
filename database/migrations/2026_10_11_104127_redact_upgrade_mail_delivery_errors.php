<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('upgrade_requests')->whereNotNull('mail_delivery_error')->update([
            'mail_delivery_error' => 'Mail delivery failed. Retry delivery after checking the mail configuration.',
        ]);
    }

    /**
     * Redaction is irreversible; rollback never restores sensitive transport details.
     */
    public function down(): void {}
};
