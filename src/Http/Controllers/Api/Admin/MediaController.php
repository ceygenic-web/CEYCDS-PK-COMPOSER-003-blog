<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Admin;

use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\MediaResource;
use Ceygenic\Blog\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    // Upload media file
    public function upload(Request $request): MediaResource|JsonResponse
    {
        $disk = config('blog.media.disk', 'public');
        $directory = config('blog.media.directory', 'blog/media');
        $maxSize = config('blog.media.max_file_size', 10485760);
        $allowedMimeTypes = config('blog.media.allowed_mime_types', []);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:' . ($maxSize / 1024), // Convert bytes to KB
                function ($attribute, $value, $fail) use ($allowedMimeTypes) {
                    if (!empty($allowedMimeTypes) && !in_array($value->getMimeType(), $allowedMimeTypes)) {
                        $fail('The file type is not allowed.');
                    }
                },
            ],
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;
        
        // Store file using configured disk
        $filePath = $file->storeAs($directory, $fileName, $disk);

        // Create media record
        $media = Media::create([
            'file_name' => $originalName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
            'caption' => $request->input('caption'),
            'disk' => $disk,
        ]);

        return (new MediaResource($media))->response()->setStatusCode(201);
    }

    // List all media
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $media = Media::orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return MediaResource::collection($media);
    }

    // Get single media
    public function show(int $id): MediaResource|JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Media not found',
                    ]
                ]
            ], 404);
        }

        return new MediaResource($media);
    }

    // Update media (alt_text, caption)
    public function update(Request $request, int $id): MediaResource|JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Media not found',
                    ]
                ]
            ], 404);
        }

        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
        ]);

        $media->update($validated);

        return new MediaResource($media);
    }

    // Delete media
    public function destroy(int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Media not found',
                    ]
                ]
            ], 404);
        }

        // Delete file from storage
        Storage::disk($media->disk)->delete($media->file_path);

        // Delete media record
        $media->delete();

        return response()->json(null, 204);
    }
}
