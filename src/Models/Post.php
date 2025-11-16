<?php

namespace Ceygenic\Blog\Models;

use Ceygenic\Blog\Traits\HasReadingTime;
use Ceygenic\Blog\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasSlug, HasReadingTime;

    protected $table = 'posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'category_id',
        'author_id',
        'status',
        'published_at',
        'reading_time',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'reading_time' => 'integer',
    ];

    // Get the category that owns the post.
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Get the author that owns the post.
    public function author(): BelongsTo
    {
        // Use the User model from the host application
        // This will be resolved at runtime, so the linter warning is expected
        $userClass = config('auth.providers.users.model', 'App\\Models\\User');
        return $this->belongsTo($userClass, 'author_id');
    }

    // Get the tags for the post.

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // Check if the post is published.
    public function isPublished(): bool
    {
        return $this->status === 'published' 
            && $this->published_at !== null 
            && $this->published_at->isPast();
    }

    // Check if the post is a draft.
   
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    //Check if the post is archived.

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    //Check if the post is scheduled for future publication.

    public function isScheduled(): bool
    {
        return $this->status === 'published' 
            && $this->published_at !== null 
            && $this->published_at->isFuture();
    }

    // Publish the post.

    public function publish(?\DateTime $publishedAt = null): bool
    {
        $this->status = 'published';
        $this->published_at = $publishedAt ?? now();
        
        return $this->save();
    }

    // Unpublish the post (set to draft).

    public function unpublish(): bool
    {
        $this->status = 'draft';
        $this->published_at = null;
        
        return $this->save();
    }

    // Toggle the post status between draft and published.

    public function toggleStatus(): bool
    {
        if ($this->isPublished() || $this->isScheduled()) {
            return $this->unpublish();
        }
        
        return $this->publish();
    }

    /**
     * Schedule the post for future publication.
     *
     * @param \DateTime $date
     * @return bool
     */
    public function schedule(\DateTime $date): bool
    {
        $this->status = 'published';
        $this->published_at = $date;
        
        return $this->save();
    }

    /**
     * Archive the post.
     *
     * @return bool
     */
    public function archive(): bool
    {
        $this->status = 'archived';
        
        return $this->save();
    }

    /**
     * Restore the post from archive (set to draft).
     *
     * @return bool
     */
    public function restore(): bool
    {
        $this->status = 'draft';
        
        return $this->save();
    }

    /**
     * Duplicate the post.
     *
     * @param string|null $newTitle
     * @return Post
     */
    public function duplicate(?string $newTitle = null): Post
    {
        $newPost = $this->replicate();
        $newPost->title = $newTitle ?? $this->title . ' (Copy)';
        $newPost->slug = null; // Will be auto-generated
        $newPost->status = 'draft';
        $newPost->published_at = null;
        $newPost->save();

        // Copy tags
        if ($this->tags()->exists()) {
            $newPost->tags()->sync($this->tags->pluck('id')->toArray());
        }

        return $newPost->load(['category', 'tags', 'author']);
    }
}

