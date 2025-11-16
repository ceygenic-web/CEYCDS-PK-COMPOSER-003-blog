<?php

namespace Ceygenic\Blog\Tests\Feature;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategorySystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_slug_is_auto_generated_from_name(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Technology Category',
        ]);

        $this->assertEquals('technology-category', $category->slug);
    }

    public function test_category_slug_is_unique_when_duplicate_exists(): void
    {
        Blog::categories()->create([
            'name' => 'Tech',
        ]);

        $category2 = Blog::categories()->create([
            'name' => 'Tech',
        ]);

        $this->assertEquals('tech-1', $category2->slug);
    }

    public function test_category_has_post_count_attribute(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        // Initially no posts
        $this->assertEquals(0, $category->post_count);

        // Create posts
        Blog::posts()->create([
            'title' => 'Post 1',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        Blog::posts()->create([
            'title' => 'Post 2',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        $category->refresh();
        $this->assertEquals(2, $category->post_count);
    }

    public function test_categories_are_ordered_by_default(): void
    {
        Blog::categories()->create([
            'name' => 'Category C',
            'order' => 3,
        ]);

        Blog::categories()->create([
            'name' => 'Category A',
            'order' => 1,
        ]);

        Blog::categories()->create([
            'name' => 'Category B',
            'order' => 2,
        ]);

        $categories = Blog::categories()->all();

        $this->assertEquals('Category A', $categories->first()->name);
        $this->assertEquals('Category C', $categories->last()->name);
    }

    public function test_can_move_category_up(): void
    {
        $cat1 = Blog::categories()->create([
            'name' => 'Category 1',
            'order' => 1,
        ]);

        $cat2 = Blog::categories()->create([
            'name' => 'Category 2',
            'order' => 2,
        ]);

        Blog::moveCategoryUp($cat2->id);

        $cat1->refresh();
        $cat2->refresh();

        $this->assertEquals(2, $cat1->order);
        $this->assertEquals(1, $cat2->order);
    }

    public function test_can_move_category_down(): void
    {
        $cat1 = Blog::categories()->create([
            'name' => 'Category 1',
            'order' => 1,
        ]);

        $cat2 = Blog::categories()->create([
            'name' => 'Category 2',
            'order' => 2,
        ]);

        Blog::moveCategoryDown($cat1->id);

        $cat1->refresh();
        $cat2->refresh();

        $this->assertEquals(2, $cat1->order);
        $this->assertEquals(1, $cat2->order);
    }

    public function test_can_set_category_order(): void
    {
        $cat1 = Blog::categories()->create([
            'name' => 'Category 1',
            'order' => 1,
        ]);

        $cat2 = Blog::categories()->create([
            'name' => 'Category 2',
            'order' => 2,
        ]);

        $cat3 = Blog::categories()->create([
            'name' => 'Category 3',
            'order' => 3,
        ]);

        // Move cat3 to position 1
        Blog::setCategoryOrder($cat3->id, 1);

        $cat1->refresh();
        $cat2->refresh();
        $cat3->refresh();

        $this->assertEquals(2, $cat1->order);
        $this->assertEquals(3, $cat2->order);
        $this->assertEquals(1, $cat3->order);
    }

    public function test_move_up_returns_false_when_already_first(): void
    {
        $cat1 = Blog::categories()->create([
            'name' => 'Category 1',
            'order' => 0,
        ]);

        $result = Blog::moveCategoryUp($cat1->id);
        $this->assertFalse($result);
    }

    public function test_move_down_returns_false_when_already_last(): void
    {
        $cat1 = Blog::categories()->create([
            'name' => 'Category 1',
            'order' => 1,
        ]);

        $result = Blog::moveCategoryDown($cat1->id);
        $this->assertFalse($result);
    }

    public function test_post_count_updates_when_posts_are_deleted(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post1 = Blog::posts()->create([
            'title' => 'Post 1',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        $post2 = Blog::posts()->create([
            'title' => 'Post 2',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        $category->refresh();
        $this->assertEquals(2, $category->post_count);

        Blog::posts()->delete($post1->id);

        $category->refresh();
        $this->assertEquals(1, $category->post_count);
    }

    public function test_categories_with_same_order_are_sorted_by_name(): void
    {
        Blog::categories()->create([
            'name' => 'Category C',
            'order' => 1,
        ]);

        Blog::categories()->create([
            'name' => 'Category A',
            'order' => 1,
        ]);

        Blog::categories()->create([
            'name' => 'Category B',
            'order' => 1,
        ]);

        $categories = Blog::categories()->all();

        $this->assertEquals('Category A', $categories->first()->name);
        $this->assertEquals('Category C', $categories->last()->name);
    }
}

