<?php

namespace Ceygenic\Blog\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ceygenic\Blog\Blog
 */
class Blog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'blog';
    }
}



