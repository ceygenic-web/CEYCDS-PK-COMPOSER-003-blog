<?php

namespace Ceygenic\Blog\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $sourceField = $model->title ?? $model->name ?? null;
                if ($sourceField) {
                    $model->slug = static::generateUniqueSlug($sourceField);
                }
            }
        });

        static::updating(function ($model) {
            // If title or name changed and slug wasn't manually set, regenerate slug
            if (empty($model->slug) || ($model->isDirty('title') || $model->isDirty('name')) && !$model->isDirty('slug')) {
                $sourceField = $model->title ?? $model->name ?? null;
                if ($sourceField) {
                    $model->slug = static::generateUniqueSlug($sourceField, $model->id);
                }
            }
        });
    }

    /**
     * Generate a unique slug from the given string.
     *
     * @param string $string
     * @param int|null $excludeId
     * @return string
     */
    public static function generateUniqueSlug(string $string, ?int $excludeId = null): string
    {
        $slug = Str::slug($string);
        $originalSlug = $slug;
        $counter = 1;

        // Get the table name from the model
        $table = (new static())->getTable();

        while (static::slugExists($slug, $excludeId, $table)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists in the database.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @param string $table
     * @return bool
     */
    protected static function slugExists(string $slug, ?int $excludeId = null, string $table): bool
    {
        $query = \Illuminate\Support\Facades\DB::table($table)->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Set the slug attribute.
     * If empty, generate from title.
     *
     * @param string|null $value
     */
    public function setSlugAttribute(?string $value): void
    {
        if (empty($value)) {
            $sourceField = $this->attributes['title'] ?? $this->attributes['name'] ?? null;
            if ($sourceField) {
                $this->attributes['slug'] = static::generateUniqueSlug($sourceField, $this->id);
            } else {
                $this->attributes['slug'] = $value;
            }
        } else {
            $this->attributes['slug'] = $value;
        }
    }
}

