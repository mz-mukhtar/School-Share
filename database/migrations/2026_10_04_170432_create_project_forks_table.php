<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_forks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('forked_project_id')->constrained('projects')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('forked_project_id'); // a project can only be a fork of one source
            $table->index('original_project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_forks');
    }
};
