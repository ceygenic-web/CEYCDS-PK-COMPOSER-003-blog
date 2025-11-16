<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->json('social_links')->nullable()->comment('JSON object with social media links');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_profiles');
    }
};

