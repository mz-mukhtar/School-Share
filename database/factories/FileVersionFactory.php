<?php

namespace Database\Factories;

use App\Models\FileVersion;
use App\Models\ProjectFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FileVersion> */
class FileVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_file_id' => ProjectFile::factory(),
            'checkpoint_id' => null,
            'storage_path' => 'projects/factory/'.Str::uuid().'.txt',
            'size_bytes' => 0,
            'mime_type' => 'text/plain',
            'version_number' => 1,
            'created_at' => now(),
        ];
    }
}
