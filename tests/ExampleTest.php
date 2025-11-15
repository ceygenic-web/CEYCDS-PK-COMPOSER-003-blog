<?php

namespace Ceygenic\Blog\Tests;

use Ceygenic\Blog\Facades\Blog;

class ExampleTest extends TestCase
{
    public function testVersionReturnsString(): void
    {
        $version = Blog::version();
        $this->assertIsString($version);
    }
}


