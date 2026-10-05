<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectFile> */
class ProjectFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'original_name' => fake()->unique()->word().'.txt',
            'mime_type' => 'text/plain',
            'version_count' => 0,
        ];
    }
}
