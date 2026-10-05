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
        Schema::create('stored_blobs', function (Blueprint $table) {
            $table->id();
            $table->string('storage_path', 512)->unique();
            $table->foreignId('billing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
        Schema::table('file_versions', function (Blueprint $table) {
            $table->index('storage_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_versions', function (Blueprint $table) {
            $table->dropIndex(['storage_path']);
        });
        Schema::dropIfExists('stored_blobs');
    }
};
