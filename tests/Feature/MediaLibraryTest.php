<?php

namespace Ceygenic\Blog\Tests\Feature;

use Ceygenic\Blog\Models\Media;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_can_create_media_record(): void
    {
        $media = Media::create([
            'file_name' => 'test-image.jpg',
            'file_path' => 'blog/media/test-image.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'alt_text' => 'Test image',
            'caption' => 'This is a test image',
            'disk' => 'public',
        ]);

        $this->assertNotNull($media);
        $this->assertEquals('test-image.jpg', $media->file_name);
        $this->assertEquals('Test image', $media->alt_text);
        $this->assertEquals('This is a test image', $media->caption);
    }

    public function test_media_has_url_attribute(): void
    {
        Storage::disk('public')->put('blog/media/test.jpg', 'fake content');

        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 'public',
        ]);

        $this->assertNotEmpty($media->url);
        $this->assertStringContainsString('blog/media/test.jpg', $media->url);
    }

    public function test_media_is_image_check(): void
    {
        $imageMedia = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 'public',
        ]);

        $pdfMedia = Media::create([
            'file_name' => 'test.pdf',
            'file_path' => 'blog/media/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'disk' => 'public',
        ]);

        $this->assertTrue($imageMedia->isImage());
        $this->assertFalse($pdfMedia->isImage());
    }

    public function test_media_has_human_readable_size(): void
    {
        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024, // 1 KB
            'disk' => 'public',
        ]);

        $this->assertStringContainsString('KB', $media->human_readable_size);

        $largeMedia = Media::create([
            'file_name' => 'large.jpg',
            'file_path' => 'blog/media/large.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1048576, // 1 MB
            'disk' => 'public',
        ]);

        $this->assertStringContainsString('MB', $largeMedia->human_readable_size);
    }

    public function test_media_can_have_null_alt_text_and_caption(): void
    {
        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 'public',
        ]);

        $this->assertNull($media->alt_text);
        $this->assertNull($media->caption);
    }

    public function test_media_uses_configured_disk(): void
    {
        $this->app['config']->set('blog.media.disk', 's3');

        $media = Media::create([
            'file_name' => 'test.jpg',
            'file_path' => 'blog/media/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'disk' => 's3',
        ]);

        $this->assertEquals('s3', $media->disk);
    }
}

