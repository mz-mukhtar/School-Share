<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use ZipArchive;

class ResourceBoundsSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['.'])]
    #[TestWith(['..'])]
    #[TestWith(['../escape'])]
    #[TestWith(['..\\escape'])]
    #[TestWith(['C:escape'])]
    #[TestWith(['NUL.txt'])]
    #[TestWith(['trailing.'])]
    public function test_folder_creation_rejects_unsafe_archive_segments(string $name): void
    {
        [$user, $project] = $this->project();

        $this->actingAs($user)->postJson(route('projects.folders.store', $project), ['name' => $name])->assertUnprocessable();

        $this->assertDatabaseCount('project_folders', 0);
    }

    #[TestWith(['.'])]
    #[TestWith(['..'])]
    #[TestWith(['../../escape'])]
    #[TestWith(['C:\\escape'])]
    #[TestWith(["bad\0name"])]
    public function test_export_rejects_unsafe_legacy_folder_rows_without_producing_an_archive(string $name): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $project->folders()->create(['name' => $name]);

        $this->actingAs($user)->getJson(route('projects.download-zip', $project))->assertUnprocessable();

        $this->assertSame([], Storage::disk('local')->allFiles('exports'));
    }

    #[TestWith(['../escape.txt'])]
    #[TestWith(['/absolute.txt'])]
    #[TestWith(['C:escape.txt'])]
    #[TestWith(['dir\\escape.txt'])]
    public function test_export_rejects_unsafe_legacy_file_names(string $name): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $this->file($project, $name, 'safe');

        $this->actingAs($user)->getJson(route('projects.download-zip', $project))->assertUnprocessable();

        $this->assertSame([], Storage::disk('local')->allFiles('exports'));
    }

    public function test_export_contains_safe_nested_names_and_real_file_contents(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $root = $project->folders()->create(['name' => 'notes']);
        $child = $project->folders()->create(['name' => 'week-1', 'parent_id' => $root->id]);
        $file = $this->file($project, 'hello.txt', 'hello');
        $file->update(['folder_id' => $child->id]);

        $response = $this->actingAs($user)->get(route('projects.download-zip', $project));

        $response->assertDownload('resource-project.zip');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $this->assertSame(3, $zip->numFiles);
        $this->assertSame('hello', $zip->getFromName('notes/week-1/hello.txt'));
        $zip->close();
    }

    #[TestWith(['cycle'])]
    #[TestWith(['foreign'])]
    public function test_export_rejects_invalid_legacy_parent_links_without_recursing_forever(string $kind): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $folder = $project->folders()->create(['name' => 'notes']);
        if ($kind === 'cycle') {
            $folder->update(['parent_id' => $folder->id]);
        } else {
            $other = $user->projects()->create(['name' => 'Other project', 'visibility' => 'private']);
            $foreign = $other->folders()->create(['name' => 'foreign']);
            $folder->update(['parent_id' => $foreign->id]);
        }

        $this->actingAs($user)->getJson(route('projects.download-zip', $project))->assertUnprocessable();

        $this->assertSame([], Storage::disk('local')->allFiles('exports'));
    }

    #[TestWith(['entries'])]
    #[TestWith(['bytes'])]
    #[TestWith(['depth'])]
    public function test_export_returns_413_for_projects_exceeding_the_work_budget(string $limit): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $root = $project->folders()->create(['name' => 'notes']);
        $child = $project->folders()->create(['name' => 'child', 'parent_id' => $root->id]);
        $file = $this->file($project, 'hello.txt', 'hello');
        $file->update(['folder_id' => $child->id]);
        config(['schoolshare.operations.'.match ($limit) {
            'entries' => 'max_project_entries', 'bytes' => 'max_archive_bytes', 'depth' => 'max_folder_depth',
        } => 1]);

        $this->actingAs($user)->getJson(route('projects.download-zip', $project))->assertStatus(413);

        $this->assertSame([], Storage::disk('local')->allFiles('exports'));
    }

    public function test_export_reports_missing_files_instead_of_silently_omitting_them(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->file($project, 'hello.txt', 'hello');
        Storage::disk('local')->delete($file->latestVersion->storage_path);

        $this->actingAs($user)->getJson(route('projects.download-zip', $project))->assertConflict();

        $this->assertSame([], Storage::disk('local')->allFiles('exports'));
    }

    public function test_export_rejects_case_insensitive_name_collisions(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $project->folders()->create(['name' => 'NOTES']);
        $this->file($project, 'notes', 'hello');

        $this->actingAs($user)->getJson(route('projects.download-zip', $project))->assertUnprocessable();

        $this->assertSame([], Storage::disk('local')->allFiles('exports'));
    }

    public function test_oversized_readme_is_not_rendered_even_if_its_recorded_size_is_wrong(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->file($project, 'README.md', str_repeat('large', 10));
        $file->latestVersion->update(['size_bytes' => 1]);
        config(['schoolshare.operations.max_readme_bytes' => 8]);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertOk();
        $this->assertNull($response->viewData('readmeHtml'));
    }

    #[TestWith(['bytes'])]
    #[TestWith(['lines'])]
    #[TestWith(['office'])]
    public function test_diff_rejects_excessive_inputs_and_office_parsing(string $kind): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->file($project, 'notes.txt', "one\ntwo\nthree");
        $version = $file->latestVersion;
        if ($kind === 'bytes') {
            config(['schoolshare.operations.max_diff_bytes' => 4]);
        } elseif ($kind === 'lines') {
            config(['schoolshare.operations.max_diff_lines' => 2]);
        } else {
            $file->update(['mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        }

        $this->actingAs($user)->getJson(route('projects.files.diff', [$project, $file, 'from' => $version->id, 'to' => $version->id]))->assertUnprocessable();
    }

    public function test_bounded_text_diff_renders_changes_as_escaped_text(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->file($project, 'notes.txt', 'old');
        $from = $file->latestVersion;
        $path = 'diff/new.txt';
        Storage::disk('local')->put($path, '<script>changed</script>');
        $to = $file->versions()->create(['checkpoint_id' => $from->checkpoint_id, 'storage_path' => $path, 'size_bytes' => 24, 'version_number' => 2]);

        $response = $this->actingAs($user)->get(route('projects.files.diff', [$project, $file, 'from' => $from->id, 'to' => $to->id]));

        $response->assertSee('changed')->assertDontSee('<script>changed</script>', false);
    }

    public function test_editor_enforces_a_byte_limit_for_multibyte_text_without_writes(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->file($project, 'notes.txt', 'old');
        config(['schoolshare.operations.max_editor_bytes' => 8]);

        $this->actingAs($user)->putJson(route('projects.files.update', [$project, $file]), ['content' => str_repeat('é', 6)])->assertStatus(413);

        $this->assertDatabaseCount('file_versions', 1);
        $this->assertDatabaseCount('checkpoints', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    #[TestWith(['count'])]
    #[TestWith(['bytes'])]
    public function test_upload_batch_limits_are_checked_before_checkpoint_or_blob_writes(string $kind): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        config(['schoolshare.operations.max_upload_files' => 1, 'schoolshare.operations.max_upload_bytes' => 1]);
        $files = [UploadedFile::fake()->createWithContent('notes.txt', 'draft')];
        if ($kind === 'count') {
            $files[] = UploadedFile::fake()->createWithContent('extra.txt', 'draft');
        }

        $this->actingAs($user)->postJson(route('projects.checkpoints.store', $project), ['title' => 'Too much', 'files' => $files])->assertStatus($kind === 'count' ? 422 : 413);

        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertDatabaseCount('stored_blobs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_folder_entry_limit_prevents_unbounded_project_trees(): void
    {
        [$user, $project] = $this->project();
        $project->folders()->create(['name' => 'notes']);
        config(['schoolshare.operations.max_project_entries' => 1]);

        $this->actingAs($user)->postJson(route('projects.folders.store', $project), ['name' => 'extra'])->assertStatus(413);

        $this->assertDatabaseCount('project_folders', 1);
    }

    public function test_version_history_limit_prevents_unbounded_zero_byte_edits(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->file($project, 'notes.txt', 'old');
        $this->artisan('schoolshare:recalculate-storage')->assertSuccessful();
        config(['schoolshare.operations.max_project_versions' => 1]);

        $this->actingAs($user)->putJson(route('projects.files.update', [$project, $file]), ['content' => ''])->assertStatus(413);

        $this->assertDatabaseCount('file_versions', 1);
        $this->assertDatabaseCount('stored_blobs', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    #[TestWith(['projects.download-zip', 'schoolshare.rate_limits.download_per_minute'])]
    #[TestWith(['projects.files.diff', 'schoolshare.rate_limits.diff_per_minute'])]
    #[TestWith(['projects.files.update', 'schoolshare.rate_limits.editor_per_minute'])]
    public function test_expensive_endpoints_return_429_at_the_configured_limit(string $name, string $setting): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->file($project, 'notes.txt', 'old');
        config([$setting => 1]);
        $parameters = $name === 'projects.download-zip' ? [$project] : [$project, $file];
        $url = route($name, array_merge($parameters, ['from' => $file->latest_version_id, 'to' => $file->latest_version_id]));
        $method = $name === 'projects.files.update' ? 'PUT' : 'GET';
        $first = $this->actingAs($user)->json($method, $url, ['original_name' => 'notes.txt']);
        if ($method === 'PUT') {
            $first->assertRedirect();
        } else {
            $first->assertSuccessful();
        }

        $this->json($method, $url, ['original_name' => 'notes.txt'])->assertTooManyRequests();
    }

    private function project(): array
    {
        $user = User::factory()->create(['username' => 'owner'])->refresh();

        return [$user, $user->projects()->create(['name' => 'Resource project', 'visibility' => 'private'])];
    }

    private function file(Project $project, string $name, string $content): ProjectFile
    {
        $checkpoint = $project->checkpoints()->create(['user_id' => $project->user_id, 'title' => 'Initial']);
        $file = $project->files()->create(['original_name' => $name, 'mime_type' => 'text/plain', 'version_count' => 1]);
        $path = 'fixtures/'.$file->id;
        Storage::disk('local')->put($path, $content);
        $version = $file->versions()->create(['checkpoint_id' => $checkpoint->id, 'storage_path' => $path, 'size_bytes' => strlen($content), 'version_number' => 1]);
        $file->update(['latest_version_id' => $version->id]);

        return $file->refresh();
    }
}
