<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::create('checkpoint_folder_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checkpoint_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_folder_id')->nullable();
            $table->json('folder_path');
            $table->timestamps();
            $table->unique(['checkpoint_id', 'source_folder_id']);
        });

        Schema::create('checkpoint_file_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checkpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_file_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('file_version_id')->constrained('file_versions')->restrictOnDelete();
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->json('folder_path')->nullable();
            $table->timestamps();
            $table->unique(['checkpoint_id', 'project_file_id']);
            $table->unique(['checkpoint_id', 'file_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_file_snapshots');
        Schema::dropIfExists('checkpoint_folder_snapshots');
        Schema::table('project_files', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
