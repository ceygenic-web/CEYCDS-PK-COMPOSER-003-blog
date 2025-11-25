<?php

namespace Ceygenic\Blog\Events;

use Ceygenic\Blog\Models\Category;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoryCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Category $category
    ) {
    }
}

