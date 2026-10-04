<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Step 1: Migrate existing project_files data → file_versions
        $hasOldCols = Schema::hasColumns('project_files', ['storage_path', 'size_bytes', 'checkpoint_id']);

        if ($hasOldCols) {
            $files = DB::table('project_files')->get();
            foreach ($files as $file) {
                $versionId = DB::table('file_versions')->insertGetId([
                    'project_file_id' => $file->id,
                    'checkpoint_id'   => $file->checkpoint_id,
                    'storage_path'    => $file->storage_path,
                    'size_bytes'      => $file->size_bytes,
                    'version_number'  => 1,
                    'created_at'      => $file->created_at ?? now(),
                ]);

                // Temporarily store the version id for later linking
                DB::table('project_files')->where('id', $file->id)
                    ->update(['_tmp_version_id' => $versionId]);
            }
        }

        // Step 2: Add new columns (except latest_version_id which needs a temp helper first)
        Schema::table('project_files', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('project_id')->constrained('project_folders')->nullOnDelete();
            $table->unsignedBigInteger('latest_version_id')->nullable()->after('checkpoint_id');
            $table->unsignedBigInteger('version_count')->default(1)->after('latest_version_id');
        });

        // Step 3: Copy latest_version_id from temp column if we migrated data
        if ($hasOldCols) {
            // We stored version IDs in DB already via the loop above, now fill latest_version_id
            $versions = DB::table('file_versions')->get();
            foreach ($versions as $v) {
                DB::table('project_files')
                    ->where('id', $v->project_file_id)
                    ->update(['latest_version_id' => $v->id]);
            }
        }

        // Step 4: Recreate the table without old columns (SQLite-safe approach)
        // We create a new table, copy data, drop old, rename
        $this->recreateWithoutOldColumns($hasOldCols);

        // Step 5: Add unique constraint on new table
        Schema::table('project_files', function (Blueprint $table) {
            $table->unique(['project_id', 'folder_id', 'original_name'], 'unique_file_per_folder');
        });
    }

    protected function recreateWithoutOldColumns(bool $hasOldCols): void
    {
        if (!$hasOldCols) return;

        // Create temp table
        Schema::create('project_files_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('project_folders')->nullOnDelete();
            $table->unsignedBigInteger('latest_version_id')->nullable();
            $table->unsignedBigInteger('version_count')->default(1);
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        // Copy data
        DB::statement('INSERT INTO project_files_new (id, project_id, folder_id, latest_version_id, version_count, original_name, mime_type, created_at, updated_at)
            SELECT id, project_id, folder_id, latest_version_id, version_count, original_name, mime_type, created_at, updated_at
            FROM project_files');

        // Drop old, rename new
        Schema::drop('project_files');
        Schema::rename('project_files_new', 'project_files');
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropUnique('unique_file_per_folder');
            $table->dropColumn(['folder_id', 'latest_version_id', 'version_count']);
            $table->string('storage_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('checkpoint_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
