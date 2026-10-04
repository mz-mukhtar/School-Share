<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64); // e.g. checkpoint_created, project_created, project_starred, user_followed, project_forked
            $table->string('subject_type', 64)->nullable(); // 'Project', 'Checkpoint', 'User'
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable(); // {title, slug, project_name, etc.}
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
