<?php

namespace Ceygenic\Blog\Models;

use Ceygenic\Blog\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasSlug;

    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = config('blog.tables.categories', 'categories');
        parent::__construct($attributes);
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // Get the posts for the category.
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // Get the post count for this category.
    // This is automatically calculated via the relationship.
    public function getPostCountAttribute(): int
    {
        return $this->posts()->count();
    }

    // Scope to order categories by order field.
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // Move category up in order.
    public function moveUp(): bool
    {
        $previous = static::where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previous) {
            $tempOrder = $this->order;
            $this->order = $previous->order;
            $previous->order = $tempOrder;
            
            $this->save();
            $previous->save();
            
            return true;
        }

        return false;
    }

    // Move category down in order.
    public function moveDown(): bool
    {
        $next = static::where('order', '>', $this->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($next) {
            $tempOrder = $this->order;
            $this->order = $next->order;
            $next->order = $tempOrder;
            
            $this->save();
            $next->save();
            
            return true;
        }

        return false;
    }

    // Set the order position.
    public function setOrder(int $order): bool
    {
        // Shift other categories if needed
        if ($this->order !== $order) {
            if ($this->order < $order) {
                // Moving down - shift categories up
                static::where('order', '>', $this->order)
                    ->where('order', '<=', $order)
                    ->where('id', '!=', $this->id)
                    ->decrement('order');
            } else {
                // Moving up - shift categories down
                static::where('order', '>=', $order)
                    ->where('order', '<', $this->order)
                    ->where('id', '!=', $this->id)
                    ->increment('order');
            }
        }

        $this->order = $order;
        return $this->save();
    }
}

