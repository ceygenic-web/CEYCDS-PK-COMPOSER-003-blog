<?php

namespace Ceygenic\Blog\Models;

use Ceygenic\Blog\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasSlug;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // Get the posts for the tag.
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    // Get the post count for this tag.
    // This is automatically calculated via the relationship.
    public function getPostCountAttribute(): int
    {
        return $this->posts()->count();
    }
}

