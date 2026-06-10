<?php

namespace App\Models;

use Database\Factories\UploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'disk', 'path', 'visibility', 'collection', 'original_name', 'mime_type', 'size'])]
class Upload extends Model
{
    /** @use HasFactory<UploadFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): string
    {
        if ($this->visibility === 'public' && config('filesystems.disks.'.$this->disk.'.url')) {
            return Storage::disk($this->disk)->url($this->path);
        }

        $expiry = $this->visibility === 'public' ? now()->addHours(8) : now()->addMinutes(15);

        return Storage::disk($this->disk)->temporaryUrl($this->path, $expiry);
    }

    /** @param  Builder<Upload>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @param  Builder<Upload>  $query */
    public function scopeInCollection(Builder $query, string $collection): void
    {
        $query->where('collection', $collection);
    }
}
