<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upgrade_requests', function (Blueprint $table): void {
            $table->string('mail_delivery_status')->default('pending')->after('status');
            $table->text('mail_delivery_error')->nullable()->after('mail_delivery_status');
            $table->timestamp('mail_sent_at')->nullable()->after('mail_delivery_error');
        });
    }

    public function down(): void
    {
        Schema::table('upgrade_requests', function (Blueprint $table): void {
            $table->dropColumn(['mail_delivery_status', 'mail_delivery_error', 'mail_sent_at']);
        });
    }
};
