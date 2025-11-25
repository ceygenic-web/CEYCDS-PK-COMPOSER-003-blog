<?php

namespace Ceygenic\Blog\Events;

use Ceygenic\Blog\Models\Tag;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TagCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Tag $tag
    ) {
    }
}

