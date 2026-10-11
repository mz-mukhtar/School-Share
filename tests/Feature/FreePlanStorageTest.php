<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FreePlanStorageTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['free', 3_221_225_472, '3 GB'])]
    #[TestWith(['pro', 10_737_418_240, '10 GB'])]
    #[TestWith(['custom', 10_737_418_240, '10 GB'])]
    public function test_storage_capacity_matches_the_plan(string $plan, int $expectedBytes, string $expectedLabel): void
    {
        $user = User::factory()->make(['plan' => $plan, 'storage_used_bytes' => 0]);

        $this->assertSame($expectedBytes, $user->maxStorageBytes());
        $this->assertSame($expectedLabel, $user->maxStorageHuman());
    }

    public function test_dashboard_displays_three_gb_for_an_existing_free_user(): void
    {
        $user = User::factory()->create(['username' => 'student'])->refresh();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSeeText('Used / 3 GB');
        $this->assertSame('free', $user->refresh()->plan);
    }

    #[TestWith([1_073_741_824])]
    #[TestWith([3_221_225_467])]
    public function test_free_user_uploads_above_one_gb_up_to_the_three_gb_boundary(int $usedBytes): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['username' => 'student', 'storage_used_bytes' => $usedBytes])->refresh();
        $project = Project::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user)->post(route('projects.checkpoints.store', $project), [
            'title' => 'Upload',
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($usedBytes + 5, $user->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 1);
        $this->assertDatabaseCount('file_versions', 1);
        $this->assertDatabaseCount('checkpoints', 1);
        $this->assertSame('draft', Storage::disk('local')->get($project->files()->firstOrFail()->latestVersion->storage_path));
    }

    public function test_free_user_upload_above_three_gb_returns_422_without_writes(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['username' => 'student', 'storage_used_bytes' => 3_221_225_468])->refresh();
        $project = Project::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user)->postJson(route('projects.checkpoints.store', $project), [
            'title' => 'Upload',
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('files');
        $this->assertSame(3_221_225_468, $user->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 0);
        $this->assertDatabaseCount('file_versions', 0);
        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
