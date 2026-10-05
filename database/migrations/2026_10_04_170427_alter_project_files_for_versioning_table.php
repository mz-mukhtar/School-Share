<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $hasOldCols = Schema::hasColumns('project_files', ['storage_path', 'size_bytes', 'checkpoint_id']);

        Schema::table('project_files', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('project_id')->constrained('project_folders')->nullOnDelete();
            $table->unsignedBigInteger('latest_version_id')->nullable()->after('checkpoint_id');
            $table->unsignedBigInteger('version_count')->default(1)->after('latest_version_id');
        });

        if ($hasOldCols) {
            $this->copyLegacyFilesToVersions();

            Schema::table('project_files', function (Blueprint $table) {
                $table->dropIndex(['project_id', 'checkpoint_id']);
                $table->dropConstrainedForeignId('checkpoint_id');
                $table->dropColumn(['storage_path', 'size_bytes']);
            });
        }

        Schema::table('project_files', function (Blueprint $table) {
            $table->unique(['project_id', 'folder_id', 'original_name'], 'unique_file_per_folder');
        });
    }

    private function copyLegacyFilesToVersions(): void
    {
        \Illuminate\Support\Facades\DB::table('project_files')->orderBy('id')->each(function (object $file): void {
            $versionId = \Illuminate\Support\Facades\DB::table('file_versions')->insertGetId([
                'project_file_id' => $file->id,
                'checkpoint_id' => $file->checkpoint_id,
                'storage_path' => $file->storage_path,
                'size_bytes' => $file->size_bytes,
                'version_number' => 1,
                'created_at' => $file->created_at ?? now(),
            ]);

            \Illuminate\Support\Facades\DB::table('project_files')->where('id', $file->id)->update([
                'latest_version_id' => $versionId,
                'version_count' => 1,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropUnique('unique_file_per_folder');
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn(['latest_version_id', 'version_count']);
        });
    }
};
