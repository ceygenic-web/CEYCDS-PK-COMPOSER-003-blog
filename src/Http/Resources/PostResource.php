<?php

namespace Ceygenic\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'posts',
            'id' => (string) $this->id,
            'attributes' => [
                'title' => $this->title ?? '',
                'slug' => $this->slug ?? '',
                'excerpt' => $this->excerpt ?? '',
                'content' => $this->content ?? '',
                'featured_image' => $this->featured_image ?? null,
                'status' => $this->status ?? 'draft',
                'published_at' => isset($this->published_at) && $this->published_at 
                    ? (is_string($this->published_at) ? $this->published_at : $this->published_at->toIso8601String())
                    : null,
                'reading_time' => $this->reading_time ?? null,
                'created_at' => isset($this->created_at) && $this->created_at
                    ? (is_string($this->created_at) ? $this->created_at : $this->created_at->toIso8601String())
                    : null,
                'updated_at' => isset($this->updated_at) && $this->updated_at
                    ? (is_string($this->updated_at) ? $this->updated_at : $this->updated_at->toIso8601String())
                    : null,
            ],
            'relationships' => [
                'category' => [
                    'data' => $this->category ? [
                        'type' => 'categories',
                        'id' => (string) $this->category->id,
                        'attributes' => [
                            'name' => $this->category->name ?? '',
                            'slug' => $this->category->slug ?? '',
                            'description' => $this->category->description ?? null,
                            'order' => $this->category->order ?? 0,
                        ],
                    ] : null,
                ],
                'author' => [
                    'data' => $this->author ? [
                        'type' => 'authors',
                        'id' => (string) $this->author->id,
                        'attributes' => [
                            'name' => $this->author->name ?? $this->author->email ?? 'Unknown',
                            'email' => $this->author->email ?? null,
                            'bio' => $this->author->bio ?? null,
                            'avatar' => $this->author->avatar ?? null,
                            'social_links' => $this->author->social_links ?? null,
                        ],
                    ] : null,
                ],
                'tags' => [
                    'data' => $this->tags ? collect($this->tags)->map(function ($tag) {
                        return [
                            'type' => 'tags',
                            'id' => (string) (is_object($tag) ? $tag->id : $tag),
                            'attributes' => is_object($tag) ? [
                                'name' => $tag->name ?? '',
                                'slug' => $tag->slug ?? '',
                                'description' => $tag->description ?? null,
                            ] : [],
                        ];
                    })->toArray() : [],
                ],
            ],
            'links' => [
                'self' => url("/api/blog/posts/{$this->slug}"),
            ],
        ];
    }
}

