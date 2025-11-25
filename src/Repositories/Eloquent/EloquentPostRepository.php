<?php

namespace Ceygenic\Blog\Repositories\Eloquent;

use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
use Ceygenic\Blog\Models\Post;
use Ceygenic\Blog\Traits\HasCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentPostRepository implements PostRepositoryInterface
{
    use HasCache;
    public function all(): Collection
    {
        return Post::with(['category', 'tags', 'author.authorProfile'])->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['category', 'tags', 'author.authorProfile'])->paginate($perPage);
    }

    public function find(int $id)
    {
        return $this->remember("posts:{$id}", function () use ($id) {
            return Post::with(['category', 'tags', 'author.authorProfile'])->find($id);
        }, 'posts');
    }

    public function findBySlug(string $slug)
    {
        return $this->remember("posts:slug:{$slug}", function () use ($slug) {
            return Post::with(['category', 'tags', 'author.authorProfile'])->where('slug', $slug)->first();
        }, 'posts');
    }

    public function create(array $data)
    {
        $post = Post::create($data);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        // Clear cache when new post is created
        $this->clearPostCache();

        $post->load(['category', 'tags', 'author.authorProfile']);

        // Dispatch event
        if (config('blog.features.events', true)) {
            event(new \Ceygenic\Blog\Events\PostCreated($post));
            
            // Dispatch publish event if status is published
            if ($post->status === 'published' && $post->published_at) {
                event(new \Ceygenic\Blog\Events\PostPublished($post));
            }
        }

        return $post;
    }

    public function update(int $id, array $data): bool
    {
        $post = Post::findOrFail($id);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $post->tags()->sync($data['tags']);
            unset($data['tags']);
        }

        $oldStatus = $post->status;
        $result = $post->update($data);
        $post->refresh();

        // Clear cache when post is updated
        if ($result) {
            $this->clearPostCache();
            $this->forgetCache("posts:{$id}");
            $this->forgetCache("posts:slug:{$post->slug}");
            // Reload relationships after update
            $post->load(['category', 'tags', 'author.authorProfile']);
            
            // Dispatch events
            if (config('blog.features.events', true)) {
                event(new \Ceygenic\Blog\Events\PostUpdated($post));
                
                // Dispatch publish event if status changed to published
                if ($oldStatus !== 'published' && $post->status === 'published' && $post->published_at) {
                    event(new \Ceygenic\Blog\Events\PostPublished($post));
                }
            }
        }

        return $result;
    }

    public function delete(int $id): bool
    {
        $post = Post::findOrFail($id);
        $slug = $post->slug;
        
        // Dispatch event before deletion
        if (config('blog.features.events', true)) {
            event(new \Ceygenic\Blog\Events\PostDeleted($post));
        }
        
        $result = $post->delete();

        // Clear cache when post is deleted
        if ($result) {
            $this->clearPostCache();
            $this->forgetCache("posts:{$id}");
            $this->forgetCache("posts:slug:{$slug}");
        }

        return $result;
    }

    //Clear all post-related cache.
    protected function clearPostCache(): void
    {
        $this->forgetCache('posts:published');
        $this->forgetCache('posts:all');
        $this->clearCacheByPattern('posts:*');
    }

    public function getPublished(): Collection
    {
        return $this->remember('posts:published', function () {
            return Post::with(['category', 'tags', 'author.authorProfile'])
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderBy('published_at', 'desc')
                ->get();
        }, 'posts');
    }

    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator
    {
        // Pagination can't be cached easily, so we skip caching for paginated results
        // The underlying query still benefits from eager loading
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function createDraft(array $data)
    {
        $data['status'] = 'draft';
        $data['published_at'] = null;

        return $this->create($data);
    }

    public function publish(int $id, ?\DateTime $publishedAt = null)
    {
        $post = Post::findOrFail($id);
        $post->publish($publishedAt);
        
        // Clear cache
        $this->clearPostCache();
        $this->forgetCache("posts:{$id}");
        $this->forgetCache("posts:slug:{$post->slug}");
        
        $post->load(['category', 'tags', 'author.authorProfile']);
        
        // Dispatch event
        if (config('blog.features.events', true)) {
            event(new \Ceygenic\Blog\Events\PostPublished($post));
        }

        return $post;
    }

    public function unpublish(int $id)
    {
        $post = Post::findOrFail($id);
        $post->unpublish();
        
        // Clear cache
        $this->clearPostCache();
        $this->forgetCache("posts:{$id}");
        $this->forgetCache("posts:slug:{$post->slug}");

        return $post->load(['category', 'tags', 'author.authorProfile']);
    }

    public function toggleStatus(int $id)
    {
        $post = Post::findOrFail($id);
        $post->toggleStatus();

        return $post->load(['category', 'tags', 'author.authorProfile']);
    }

    public function schedule(int $id, \DateTime $date)
    {
        $post = Post::findOrFail($id);
        $post->schedule($date);

        return $post->load(['category', 'tags', 'author.authorProfile']);
    }

    public function duplicate(int $id, ?string $newTitle = null)
    {
        $post = Post::findOrFail($id);

        return $post->duplicate($newTitle);
    }

    public function archive(int $id)
    {
        $post = Post::findOrFail($id);
        $post->archive();

        return $post->load(['category', 'tags', 'author.authorProfile']);
    }

    public function restore(int $id)
    {
        $post = Post::findOrFail($id);
        $post->restore();

        return $post->load(['category', 'tags', 'author.authorProfile']);
    }

    public function getDrafts(): Collection
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getScheduled(): Collection
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->orderBy('published_at', 'asc')
            ->get();
    }

    public function getArchived(): Collection
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'archived')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        if (empty(trim($query))) {
            return $this->getPublishedPaginated($perPage);
        }

        $searchTerm = trim($query);
        $connection = \Illuminate\Support\Facades\DB::connection();
        $driver = $connection->getDriverName();

        $baseQuery = Post::with(['category', 'tags', 'author'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        // Use full-text search for MySQL/MariaDB if available, otherwise use LIKE
        if (in_array($driver, ['mysql', 'mariadb'])) {
            // Check if fulltext index exists (we'll use LIKE as fallback for compatibility)
            // For better performance, users should add fulltext indexes manually
            $baseQuery->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%")
                  ->orWhere('excerpt', 'like', "%{$searchTerm}%");
            });

            // Calculate relevance score: title matches get higher weight
            $searchPattern = "%{$searchTerm}%";
            $baseQuery->selectRaw('posts.*, 
                (
                    CASE 
                        WHEN title LIKE ? THEN 3
                        WHEN content LIKE ? THEN 1
                        WHEN excerpt LIKE ? THEN 1
                        ELSE 0
                    END
                ) as relevance', 
                [$searchPattern, $searchPattern, $searchPattern]
            );
        } else {
            // For PostgreSQL, SQLite, etc., use LIKE with relevance calculation
            $baseQuery->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%")
                  ->orWhere('excerpt', 'like', "%{$searchTerm}%");
            });

            // Calculate relevance score
            $searchPattern = "%{$searchTerm}%";
            $baseQuery->selectRaw('posts.*, 
                (
                    CASE 
                        WHEN title LIKE ? THEN 3
                        WHEN content LIKE ? THEN 1
                        WHEN excerpt LIKE ? THEN 1
                        ELSE 0
                    END
                ) as relevance',
                [$searchPattern, $searchPattern, $searchPattern]
            );
        }

        return $baseQuery
            ->orderBy('relevance', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }
}

