<?php

namespace Ceygenic\__PackageStudly\Tests;

use Ceygenic\__PackageStudly\__PackageStudly__;


class ExampleTest extends TestCase
{
    public function testVersionReturnsString(): void
    {
        $pkg = new __PackageStudly__();
        $this->assertIsString($pkg->version());
    }
}


