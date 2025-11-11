<?php

namespace Ceygenic\__PackageStudly\Tests;

use Ceygenic\__PackageStudly\__PackageStudly__ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            __PackageStudly__ServiceProvider::class,
        ];
    }
}


