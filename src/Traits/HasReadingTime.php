<?php

namespace Ceygenic\Blog\Traits;

trait HasReadingTime
{
    /**
     * Boot the trait.
     */
    protected static function bootHasReadingTime(): void
    {
        static::saving(function ($model) {
            if (isset($model->content) && $model->isDirty('content')) {
                $model->reading_time = static::calculateReadingTime($model->content);
            }
        });
    }

    /**
     * Calculate reading time in minutes based on content.
     *
     * @param string $content
     * @param int $wordsPerMinute
     * @return int
     */
    public static function calculateReadingTime(string $content, int $wordsPerMinute = 200): int
    {
        // Strip HTML tags
        $text = strip_tags($content);
        
        // Count words
        $wordCount = str_word_count($text);
        
        // Calculate reading time (always round up, minimum 1 minute)
        $readingTime = max(1, (int) ceil($wordCount / $wordsPerMinute));
        
        return $readingTime;
    }

    /**
     * Set the reading time attribute.
     * Auto-calculates from content if not set.
     *
     * @param int|null $value
     */
    public function setReadingTimeAttribute(?int $value): void
    {
        if ($value === null && isset($this->attributes['content'])) {
            $this->attributes['reading_time'] = static::calculateReadingTime($this->attributes['content']);
        } else {
            $this->attributes['reading_time'] = $value;
        }
    }

    /**
     * Get the words per minute setting from config.
     *
     * @return int
     */
    protected function getWordsPerMinute(): int
    {
        return config('blog.reading_time.words_per_minute', 200);
    }
}

