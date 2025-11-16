<?php

namespace Ceygenic\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'media',
            'id' => (string) $this->id,
            'attributes' => [
                'file_name' => $this->file_name ?? '',
                'file_path' => $this->file_path ?? '',
                'url' => $this->url ?? '',
                'mime_type' => $this->mime_type ?? '',
                'file_size' => $this->file_size ?? 0,
                'human_readable_size' => $this->human_readable_size ?? '0 B',
                'alt_text' => $this->alt_text ?? null,
                'caption' => $this->caption ?? null,
                'disk' => $this->disk ?? 'public',
                'is_image' => $this->isImage(),
                'created_at' => isset($this->created_at) && $this->created_at
                    ? (is_string($this->created_at) ? $this->created_at : $this->created_at->toIso8601String())
                    : null,
                'updated_at' => isset($this->updated_at) && $this->updated_at
                    ? (is_string($this->updated_at) ? $this->updated_at : $this->updated_at->toIso8601String())
                    : null,
            ],
            'links' => [
                'self' => url("/api/blog/admin/media/{$this->id}"),
                'url' => $this->url ?? '',
            ],
        ];
    }
}

