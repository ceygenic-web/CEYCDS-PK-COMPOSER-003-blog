<?php

namespace Ceygenic\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = config('blog.tables.media', 'media');
        parent::__construct($attributes);
    }

    protected $fillable = [
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'alt_text',
        'caption',
        'disk',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // Get the full URL to the media file.
    public function getUrlAttribute(): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = \Illuminate\Support\Facades\Storage::disk($this->disk);
        return $storage->url($this->file_path);
    }

    // Get the full path to the media file.
    public function getFullPathAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk($this->disk)->path($this->file_path);
    }

    // Check if the media is an image.
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    // Get file size in human readable format.
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

