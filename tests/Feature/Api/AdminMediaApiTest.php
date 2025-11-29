<?php

namespace Ceygenic\Blog\Tests\Feature\Api;

use Ceygenic\Blog\Models\Media;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminMediaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we use 'public' disk for tests
        $this->app['config']->set('blog.media.disk', 'public');
        
        // Fake the public disk
        Storage::fake('public');
        
        // Ensure filesystem config doesn't try to use S3
        $this->app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ]);
        
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_upload_media_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->postJson('/api/blog/admin/media/upload', [
            'file' => $file,
            'alt_text' => 'Test image',
            'caption' => 'This is a test',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'file_name',
                        'file_path',
                        'url',
                        'mime_type',
                        'file_size',
                        'alt_text',
                        'caption',
                        'is_image',
                    ],
                ],
            ]);

        $mediaTable = config('blog.tables.media', 'media');

        $this->assertDatabaseHas($mediaTable, [
            'file_name' => 'test.jpg',
            'alt_text' => 'Test image',
            'caption' => 'This is a test',
        ]);
    }

    public function test_can_upload_media_without_alt_text_and_caption(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/blog/admin/media/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $mediaTable = config('blog.tables.media', 'media');

        $this->assertDatabaseHas($mediaTable, [
            'file_name' => 'test.jpg',
        ]);
    }

    public function test_upload_validates_file_requirement(): void
    {
        $response = $this->postJson('/api/blog/admin/media/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_validates_file_size(): void
    {
        $this->app['config']->set('blog.media.max_file_size', 1024); // 1KB

        $file = UploadedFile::fake()->image('large.jpg')->size(2048); // 2KB

        $response = $this->postJson('/api/blog/admin/media/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_can_list_media(): void
    {
        Media::create([
            'file_name' => 'image1.jpg',
            'file_path' => 'blog/media/image1.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 'public',
        ]);

        Media::create([
            'file_name' => 'image2.jpg',
            'file_path' => 'blog/media/image2.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'disk' => 'public',
        ]);

        $response = $this->getJson('/api/blog/admin/media');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes' => [
                            'file_name',
                            'url',
                            'mime_type',
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_get_single_media(): void
    {
        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'alt_text' => 'Test image',
            'caption' => 'Test caption',
            'disk' => 'public',
        ]);

        $response = $this->getJson("/api/blog/admin/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'file_name',
                        'url',
                        'alt_text',
                        'caption',
                        'is_image',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('test.jpg', $data['attributes']['file_name']);
        $this->assertEquals('Test image', $data['attributes']['alt_text']);
    }

    public function test_can_update_media_metadata(): void
    {
        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 'public',
        ]);

        $response = $this->putJson("/api/blog/admin/media/{$media->id}", [
            'alt_text' => 'Updated alt text',
            'caption' => 'Updated caption',
        ]);

        $response->assertStatus(200);
        
        $media->refresh();
        $this->assertEquals('Updated alt text', $media->alt_text);
        $this->assertEquals('Updated caption', $media->caption);
    }

    public function test_can_delete_media(): void
    {
        Storage::disk('public')->put('blog/media/test.jpg', 'fake content');

        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 'public',
        ]);

        $response = $this->deleteJson("/api/blog/admin/media/{$media->id}");

        $response->assertStatus(204);
        $mediaTable = config('blog.tables.media', 'media');

        $this->assertDatabaseMissing($mediaTable, ['id' => $media->id]);
        $this->assertFalse(Storage::disk('public')->exists('blog/media/test.jpg'));
    }

    public function test_returns_404_for_nonexistent_media(): void
    {
        $response = $this->getJson('/api/blog/admin/media/999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'status',
                        'title',
                        'detail',
                    ],
                ],
            ]);
    }

    public function test_media_resource_includes_all_fields(): void
    {
        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'alt_text' => 'Alt text',
            'caption' => 'Caption',
            'disk' => 'public',
        ]);

        $response = $this->getJson("/api/blog/admin/media/{$media->id}");

        $response->assertStatus(200);
        $data = $response->json('data.attributes');
        
        $this->assertEquals('test.jpg', $data['file_name']);
        $this->assertEquals('image/jpeg', $data['mime_type']);
        $this->assertEquals(1024, $data['file_size']);
        $this->assertEquals('Alt text', $data['alt_text']);
        $this->assertEquals('Caption', $data['caption']);
        $this->assertTrue($data['is_image']);
        $this->assertNotEmpty($data['human_readable_size']);
    }
}

