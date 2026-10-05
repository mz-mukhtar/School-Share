<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    public $timestamps = false; // only use created_at

    protected $fillable = [
        'user_id',
        'type',
        'subject_type',
        'subject_id',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $accessibleProject = fn (Builder $projects): Builder => $projects->accessibleTo($user);

        return $query->where(function (Builder $activities) use ($accessibleProject): void {
            $activities->whereHasMorph('subject', [Project::class], $accessibleProject)
                ->orWhereHasMorph('subject', [Checkpoint::class], fn (Builder $checkpoints): Builder => $checkpoints->whereHas('project', $accessibleProject))
                ->orWhereHasMorph('subject', [Comment::class], fn (Builder $comments): Builder => $comments->whereHas('checkpoint.project', $accessibleProject))
                ->orWhere(function (Builder $follows): void {
                    $follows->where('type', 'follow')->whereHasMorph('subject', [User::class]);
                });
        });
    }

    public static function log(string $type, User $user, $subject = null, ?array $meta = null)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
