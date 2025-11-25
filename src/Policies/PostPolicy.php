<?php

namespace Ceygenic\Blog\Policies;

use Illuminate\Foundation\Auth\User;

/**
 * Post Policy
 * 
 * This is a base policy class. Users can extend this class
 * in their App\Policies\PostPolicy to customize authorization.
 * 
 * The package does not register this policy by default to allow
 * full customization. Users can register their own policies in
 * AuthServiceProvider.
 */
class PostPolicy
{
    /**
     * Determine if the user can view any posts.
     */
    public function viewAny(?User $user): bool
    {
        // Public posts are viewable by anyone
        return true;
    }

    /**
     * Determine if the user can view the post.
     */
    public function view(?User $user, $post): bool
    {
        // Published posts are viewable by anyone
        if ($post->status === 'published') {
            return true;
        }

        // Draft/archived posts only viewable by authenticated users
        return $user !== null;
    }

    /**
     * Determine if the user can create posts.
     */
    public function create(?User $user): bool
    {
        // Require authentication for creating posts
        return $user !== null;
    }

    /**
     * Determine if the user can update the post.
     */
    public function update(?User $user, $post): bool
    {
        // Only authenticated users can update
        if ($user === null) {
            return false;
        }

        // Default: Allow if user is the author
        // Override this in your App\Policies\PostPolicy for custom logic
        return $post->author_id === $user->id;
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(?User $user, $post): bool
    {
        // Only authenticated users can delete
        if ($user === null) {
            return false;
        }

        // Default: Allow if user is the author
        // Override this in your App\Policies\PostPolicy for custom logic
        return $post->author_id === $user->id;
    }

    /**
     * Determine if the user can publish the post.
     */
    public function publish(?User $user, $post): bool
    {
        // Only authenticated users can publish
        if ($user === null) {
            return false;
        }

        // Default: Allow if user is the author
        // Override this in your App\Policies\PostPolicy for custom logic
        return $post->author_id === $user->id;
    }
}

