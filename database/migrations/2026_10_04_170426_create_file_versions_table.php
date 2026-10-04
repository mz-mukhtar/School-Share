<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('storage_path', 512);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->index('project_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_versions');
    }
};
