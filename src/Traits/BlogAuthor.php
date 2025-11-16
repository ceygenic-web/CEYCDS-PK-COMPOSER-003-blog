<?php

namespace Ceygenic\Blog\Traits;

use Ceygenic\Blog\Models\AuthorProfile;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait BlogAuthor
{
    //Get the author profile for the user.
    public function authorProfile(): HasOne
    {
        return $this->hasOne(AuthorProfile::class, 'user_id');
    }

    // Get the posts authored by this user.
    public function blogPosts(): HasMany
    {
        $postClass = config('blog.models.post', 'Ceygenic\\Blog\\Models\\Post');
        return $this->hasMany($postClass, 'author_id');
    }

    // Get the author's bio.
    public function getBioAttribute(): ?string
    {
        return $this->authorProfile?->bio;
    }

    // Get the author's avatar.
    public function getAvatarAttribute(): ?string
    {
        return $this->authorProfile?->avatar;
    }

    // Get the author's social links.
    public function getSocialLinksAttribute(): ?array
    {
        return $this->authorProfile?->social_links;
    }

    // Create or update the author profile.
    public function updateAuthorProfile(array $data): AuthorProfile
    {
        return $this->authorProfile()->updateOrCreate(
            ['user_id' => $this->id],
            $data
        );
    }
}

