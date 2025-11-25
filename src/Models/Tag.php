<?php

namespace Ceygenic\Blog\Models;

use Ceygenic\Blog\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasSlug;

    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = config('blog.tables.tags', 'tags');
        parent::__construct($attributes);
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // Get the posts for the tag.
    public function posts(): BelongsToMany
    {
        $pivotTable = config('blog.tables.post_tag', 'post_tag');
        return $this->belongsToMany(Post::class, $pivotTable);
    }

    // Get the post count for this tag.
    // This is automatically calculated via the relationship.
    public function getPostCountAttribute(): int
    {
        return $this->posts()->count();
    }
}

