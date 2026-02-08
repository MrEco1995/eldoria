<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediafileAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mediafile_id',
        'attachable_type',
        'attachable_id',
        'role',
    ];

    public function mediafile(): BelongsTo
    {
        return $this->belongsTo(Mediafile::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
