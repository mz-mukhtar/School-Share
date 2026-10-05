<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->makeProjectSlugsGloballyUnique();

        if (Schema::hasIndex('projects', ['user_id', 'slug'], 'unique')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropUnique(['user_id', 'slug']);
            });
        }
        if (! Schema::hasIndex('projects', ['slug'], 'unique')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->unique('slug');
            });
        }

        DB::table('users')->where('plan', 'student')->update(['plan' => 'free']);

        Schema::table('project_files', function (Blueprint $table): void {
            $table->unsignedBigInteger('folder_scope')->default(0)->after('folder_id');
        });
        Schema::table('project_folders', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_scope')->default(0)->after('parent_id');
        });
        Schema::table('file_versions', function (Blueprint $table): void {
            $table->string('mime_type')->nullable()->after('size_bytes');
        });

        $this->backfillFileScopesAndMimeTypes();
        $this->backfillFolderScopes();
        $this->deduplicateFileNames();
        $this->deduplicateFolderNames();

        Schema::table('project_files', function (Blueprint $table): void {
            $table->unique(['project_id', 'folder_scope', 'original_name'], 'project_files_folder_scope_unique');
        });
        Schema::table('project_folders', function (Blueprint $table): void {
            $table->unique(['project_id', 'parent_scope', 'name'], 'project_folders_parent_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table): void {
            $table->dropUnique('project_files_folder_scope_unique');
            $table->dropColumn('folder_scope');
        });
        Schema::table('project_folders', function (Blueprint $table): void {
            $table->dropUnique('project_folders_parent_scope_unique');
            $table->dropColumn('parent_scope');
        });
        Schema::table('file_versions', function (Blueprint $table): void {
            $table->dropColumn('mime_type');
        });
    }

    private function makeProjectSlugsGloballyUnique(): void
    {
        $used = [];
        DB::table('projects')->orderBy('id')->each(function (object $project) use (&$used): void {
            $base = Str::slug((string) $project->slug);
            $base = $base !== '' ? Str::limit($base, 240, '') : 'project';
            $slug = $base;
            $suffix = 1;
            while (isset($used[$slug])) {
                $slug = Str::limit($base, 240 - strlen((string) $suffix), '').'-'.$suffix;
                $suffix++;
            }
            $used[$slug] = true;

            if ($slug !== $project->slug) {
                DB::table('projects')->where('id', $project->id)->update(['slug' => $slug]);
            }
        });
    }

    private function backfillFileScopesAndMimeTypes(): void
    {
        DB::table('project_files')->orderBy('id')->each(function (object $file): void {
            DB::table('project_files')->where('id', $file->id)->update([
                'folder_scope' => $file->folder_id ?? 0,
            ]);
            DB::table('file_versions')
                ->where('project_file_id', $file->id)
                ->whereNull('mime_type')
                ->update(['mime_type' => $file->mime_type]);
        });
    }

    private function backfillFolderScopes(): void
    {
        DB::table('project_folders')->orderBy('id')->each(function (object $folder): void {
            DB::table('project_folders')->where('id', $folder->id)->update([
                'parent_scope' => $folder->parent_id ?? 0,
            ]);
        });
    }

    private function deduplicateFileNames(): void
    {
        $seen = [];
        DB::table('project_files')->orderBy('id')->each(function (object $file) use (&$seen): void {
            $key = $file->project_id.'|'.$file->folder_scope.'|'.$file->original_name;
            if (! isset($seen[$key])) {
                $seen[$key] = true;

                return;
            }
            $name = pathinfo($file->original_name, PATHINFO_FILENAME).'-'.$file->id;
            $extension = pathinfo($file->original_name, PATHINFO_EXTENSION);
            $name .= $extension === '' ? '' : '.'.$extension;
            DB::table('project_files')->where('id', $file->id)->update(['original_name' => $name]);
            $seen[$file->project_id.'|'.$file->folder_scope.'|'.$name] = true;
        });
    }

    private function deduplicateFolderNames(): void
    {
        $seen = [];
        DB::table('project_folders')->orderBy('id')->each(function (object $folder) use (&$seen): void {
            $key = $folder->project_id.'|'.$folder->parent_scope.'|'.$folder->name;
            if (! isset($seen[$key])) {
                $seen[$key] = true;

                return;
            }
            $name = Str::limit($folder->name, 240 - strlen((string) $folder->id), '').'-'.$folder->id;
            DB::table('project_folders')->where('id', $folder->id)->update(['name' => $name]);
            $seen[$folder->project_id.'|'.$folder->parent_scope.'|'.$name] = true;
        });
    }
};
