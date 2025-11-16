<?php

namespace Ceygenic\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'categories',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name ?? '',
                'slug' => $this->slug ?? '',
                'description' => $this->description ?? '',
                'post_count' => $this->post_count ?? 0,
                'order' => $this->order ?? 0,
                'created_at' => isset($this->created_at) && $this->created_at
                    ? (is_string($this->created_at) ? $this->created_at : $this->created_at->toIso8601String())
                    : null,
                'updated_at' => isset($this->updated_at) && $this->updated_at
                    ? (is_string($this->updated_at) ? $this->updated_at : $this->updated_at->toIso8601String())
                    : null,
            ],
            'links' => [
                'self' => url("/api/blog/categories/{$this->slug}"),
            ],
        ];
    }
}

