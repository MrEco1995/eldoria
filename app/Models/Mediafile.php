<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Mediafile extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'path',
        'filename',
        'mime',
        'size',
        'width',
        'height',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function attachments(): HasMany
    {
        return $this->hasMany(MediafileAttachment::class);
    }

    public function characters(): MorphToMany
    {
        return $this->morphedByMany(PartyCharacter::class, 'attachable', 'mediafile_attachments');
    }
}
