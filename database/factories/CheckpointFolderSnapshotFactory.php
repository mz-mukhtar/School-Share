<?php

namespace Database\Factories;

use App\Models\Checkpoint;
use App\Models\CheckpointFolderSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CheckpointFolderSnapshot> */
class CheckpointFolderSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'checkpoint_id' => Checkpoint::factory(),
            'source_folder_id' => null,
            'folder_path' => [fake()->word()],
        ];
    }
}
