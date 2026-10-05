<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invalidate ephemeral plaintext codes rather than trusting legacy
        // codes that were never bound to the recipient email.
        DB::table('email_otp_tokens')->delete();

        Schema::table('email_otp_tokens', function (Blueprint $table): void {
            $table->dropColumn('otp');
            $table->string('email');
            $table->string('otp_hash', 64);
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->unsignedSmallInteger('send_count')->default(0);
            $table->timestamp('send_window_started_at');
            $table->string('delivery_status', 16)->default('pending');
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        DB::table('email_otp_tokens')->delete();

        Schema::table('email_otp_tokens', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
            $table->dropColumn(['email', 'otp_hash', 'failed_attempts', 'send_count', 'send_window_started_at', 'delivery_status']);
            $table->string('otp', 6);
        });
    }
};
