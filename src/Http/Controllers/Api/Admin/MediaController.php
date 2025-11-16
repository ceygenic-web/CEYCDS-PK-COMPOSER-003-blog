<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Admin;

use Ceygenic\Blog\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    // Upload media file
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|image|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('blog/media', 'public');

        return response()->json([
            'data' => [
                'type' => 'media',
                'id' => basename($path),
                'attributes' => [
                    'url' => Storage::url($path),
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]
            ]
        ], 201);
    }
}

