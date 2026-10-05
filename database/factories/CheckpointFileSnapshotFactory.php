<?php

namespace Database\Factories;

use App\Models\Checkpoint;
use App\Models\CheckpointFileSnapshot;
use App\Models\FileVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CheckpointFileSnapshot> */
class CheckpointFileSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'checkpoint_id' => Checkpoint::factory(),
            'file_version_id' => FileVersion::factory(),
            'original_name' => fake()->word().'.txt',
            'mime_type' => 'text/plain',
            'folder_path' => [],
        ];
    }
}
