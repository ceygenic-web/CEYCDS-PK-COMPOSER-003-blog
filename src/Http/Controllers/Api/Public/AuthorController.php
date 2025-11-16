<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Public;

use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\AuthorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{

    public function show(string $id, Request $request): AuthorResource|JsonResponse
    {
        // For now, this is a placeholder
        // In a real implementation, you would:
        // 1. Have an Author model or use User model
        // 2. Load author with posts relationship
        // 3. Return AuthorResource
        
        return response()->json([
            'errors' => [
                [
                    'status' => '501',
                    'title' => 'Not Implemented',
                    'detail' => 'Author endpoint is not yet implemented. This requires an Author model or User model extension.',
                ]
            ]
        ], 501);
    }
}

