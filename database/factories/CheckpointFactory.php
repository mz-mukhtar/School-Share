<?php

namespace Database\Factories;

use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Checkpoint> */
class CheckpointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'total_size_bytes' => 0,
        ];
    }
}
