<?php

namespace Ceygenic\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'authors',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name ?? $this->email ?? 'Unknown',
                'email' => $this->email ?? null,
                'created_at' => $this->created_at?->toIso8601String(),
            ],
            'relationships' => [
                'posts' => [
                    'data' => $this->posts ? collect($this->posts)->map(function ($post) {
                        return [
                            'type' => 'posts',
                            'id' => (string) $post->id,
                        ];
                    })->toArray() : [],
                ],
            ],
        ];
    }
}

