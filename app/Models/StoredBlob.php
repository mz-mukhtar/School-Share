<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoredBlob extends Model
{
    protected $fillable = ['storage_path', 'billing_user_id', 'size_bytes'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }
}
