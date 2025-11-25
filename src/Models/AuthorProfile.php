<?php

namespace Ceygenic\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorProfile extends Model
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = config('blog.tables.author_profiles', 'author_profiles');
        parent::__construct($attributes);
    }

    protected $fillable = [
        'user_id',
        'bio',
        'avatar',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    // Get the user that owns this profile.
    public function user(): BelongsTo
    {
        $userClass = config('auth.providers.users.model', 'App\\Models\\User');
        return $this->belongsTo($userClass, 'user_id');
    }
}

