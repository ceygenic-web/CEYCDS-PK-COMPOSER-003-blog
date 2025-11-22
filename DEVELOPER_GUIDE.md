# Developer Guide: Using ceygenic/blog-core in Your Laravel Projects

This guide is for developers who want to install, configure, extend, and understand the ceygenic/blog-core package. It assumes basic Laravel knowledge but explains everything step by step.

## 1. What This Package Gives You

- **Headless blog engine** (no UI, only backend logic and API)
- **RESTful API** for:
  - Public blog (posts, categories, tags, authors)
  - Admin panel (CRUD, media upload)
- **Dual storage drivers:**
  - `db` → Laravel Eloquent (default)
  - `sanity` → Sanity CMS (read-side, mutations mostly stubbed)
- **Core blog features:**
  - Post management (draft/publish/schedule/archive/duplicate)
  - Categories (order control, post counts)
  - Tags (search/auto-complete, popular tags)
  - Authors (linked to your app's User model)
  - Media library (file upload via Laravel Storage)
  - Search & filtering
  - Caching & performance

You are responsible for frontend/admin UI in your app; this package provides the engine + API.

## 2. Installing the Package Into Another Project

### 2.1. Require via Composer

```bash
composer require ceygenic/blog-core
```

This will:
- Install the package under `vendor/`
- Auto-register the `BlogServiceProvider`
- Auto-register the `Blog` facade

### 2.2. Publish Config (Optional but Recommended)

```bash
php artisan vendor:publish --tag=blog-config
```

This creates `config/blog.php` in your app, where you can change:
- Driver: `db` or `sanity`
- Reading time settings
- Author user model
- Model overrides
- Media & cache settings

### 2.3. Publish/Use Migrations

By default, the package auto-loads its migrations:
- `categories`
- `tags`
- `posts`
- `post_tag`
- `author_profiles`
- `media`
- `users` (only created if it does not already exist)

To customize migrations in your app:

```bash
php artisan vendor:publish --tag=blog-migrations
```

Then run:

```bash
php artisan migrate
```

**Note:** The users migration in the package checks `Schema::hasTable('users')` before creating, so it will not overwrite your existing users table.

For detailed installation steps, see the [Installation Guide](INSTALLATION.md).

## 3. Database Connection Configuration

### 3.1. Default Connection

By default, the package uses Laravel's default database connection (configured via `DB_CONNECTION` in your `.env`). All blog models will use this connection.

### 3.2. Using a Separate Database Connection

If you want to store blog data in a separate database, you can:

**Option 1:** Configure a custom connection in `config/database.php`:

```php
'connections' => [
    'blog' => [
        'driver' => 'mysql',
        'host' => env('BLOG_DB_HOST', '127.0.0.1'),
        'port' => env('BLOG_DB_PORT', '3306'),
        'database' => env('BLOG_DB_DATABASE', 'blog_db'),
        'username' => env('BLOG_DB_USERNAME', 'root'),
        'password' => env('BLOG_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
],
```

**Option 2:** Override models to use a different connection:

Create extended models in your app and set the `$connection` property:

```php
namespace App\Models;

use Ceygenic\Blog\Models\Post as BasePost;

class Post extends BasePost
{
    protected $connection = 'blog';
}
```

Then override the model in `config/blog.php`:

```php
'models' => [
    'post' => \App\Models\Post::class,
    // ... other models
],
```

**Option 3:** Set connection via config (if supported in future versions):

This would require adding connection configuration to `config/blog.php` and updating models accordingly.

### 3.3. Basic Configuration (DB Driver)

The default driver is `db` (Eloquent). In your `.env`:

```env
BLOG_ENABLED=true
BLOG_DRIVER=db

# Reading time
BLOG_READING_TIME_WPM=200

# Media
BLOG_MEDIA_DISK=public
BLOG_MEDIA_DIRECTORY=blog/media
BLOG_MEDIA_MAX_SIZE=10485760

# Cache
BLOG_CACHE_ENABLED=true
BLOG_CACHE_TTL=3600
BLOG_CACHE_PREFIX=blog
```

### 3.4. Linking to Your User Model

By default, the Post model needs an author (`author_id`). You can tell the package which User model to use:

```env
BLOG_USER_MODEL=App\Models\User
```

In `config/blog.php`:

```php
'author' => [
    'user_model' => env('BLOG_USER_MODEL', config('auth.providers.users.model', 'App\\Models\\User')),
],
```

This means:
- `Post::author()` will use your app's User model.
- `AuthorProfile` references your users table.

If you want blog-specific author helpers (bio, avatar, etc.), add the `BlogAuthor` trait to your User model:

```php
use Ceygenic\Blog\Traits\BlogAuthor;

class User extends Authenticatable
{
    use BlogAuthor;
}
```

This gives you:
- `$user->authorProfile`
- `$user->blogPosts`
- `$user->bio`, `$user->avatar`, `$user->social_links`

## 4. Switching Between db and sanity Drivers

**Note:** Database connection configuration (see section 3) is separate from the storage driver. The `BLOG_DRIVER` setting determines whether to use Eloquent (`db`) or Sanity CMS (`sanity`) for data access, while database connection settings determine which database server/connection to use when the `db` driver is selected.

The driver is controlled by `config('blog.driver')` or `.env`:

```env
# Use Eloquent/database
BLOG_DRIVER=db

# OR use Sanity CMS for read operations
BLOG_DRIVER=sanity
```

### 4.1. DB Driver (db)

Uses Eloquent models:
- `Ceygenic\Blog\Models\Post`
- `Category`, `Tag`, `AuthorProfile`, `Media`

All CRUD operations & advanced features work.

Repositories:
- `EloquentPostRepository`
- `EloquentCategoryRepository`
- `EloquentTagRepository`

### 4.2. Sanity Driver (sanity)

Configure in `.env`:

```env
BLOG_DRIVER=sanity
SANITY_PROJECT_ID=your-project-id
SANITY_DATASET=production
SANITY_TOKEN=your-sanity-token
```

- Read data from Sanity via GROQ queries.
- Repositories:
  - `SanityPostRepository`
  - `SanityCategoryRepository`
  - `SanityTagRepository`

Most mutating operations (create/update/delete) are stubs and will throw a `RuntimeException` until you implement Sanity mutations.

**Typical pattern:**
- Use `db` driver in production for full CRUD.
- Use `sanity` driver if your content team already lives in Sanity and you only need read-side blog API.

## 5. Using the Facade in Your App

The package provides a `Blog` facade:

```php
use Ceygenic\Blog\Facades\Blog;

// Get published posts (collection)
$posts = Blog::posts()->getPublished();

// Create a draft post
$post = Blog::createDraft([
    'title' => 'My Draft Post',
    'content' => 'Lorem ipsum...',
    'category_id' => 1,
    'author_id' => auth()->id(),
]);

// Publish a post
Blog::publishPost($post->id);

// Categories
$categories = Blog::getCategoriesOrdered();

// Tags
$tags = Blog::tags()->all();
$popular = Blog::getPopularTags();
```

Behind the scenes, `Blog` uses the repositories that match your configured driver.

### 5.1. Using Dependency Injection

Instead of using the facade, you can inject the Blog service:

```php
use Ceygenic\Blog\Blog;

class YourController extends Controller
{
    public function __construct(
        private Blog $blog
    ) {}

    public function index()
    {
        $posts = $this->blog->posts()->all();
        return view('posts.index', compact('posts'));
    }
}
```

### 5.2. Using the Service Container

```php
$blog = app('blog');
$posts = $blog->posts()->all();
```

## 6. REST API Overview (How to Call from Your Frontend)

Once installed, the package registers API routes under `/api/blog`.

### 6.1. Public Endpoints (No Auth Required)

Rate-limited to **120 requests per minute**.

#### Posts

- **GET** `/api/blog/posts` - List all posts (with pagination, filtering, sorting)
- **GET** `/api/blog/posts/{slug}` - Get single post by slug
- **GET** `/api/blog/posts/search?q={query}` - Search posts

**Query Parameters:**
- `filter[title]` - Filter by title
- `filter[status]` - Filter by status (draft, published, archived)
- `filter[category_id]` - Filter by category ID
- `sort` - Sort by field (e.g., `-published_at` for descending)
- `per_page` - Items per page (default: 15)
- `page` - Page number

**Example:**
```bash
GET /api/blog/posts?filter[status]=published&sort=-published_at&per_page=10
```

#### Categories

- **GET** `/api/blog/categories` - List all categories
- **GET** `/api/blog/categories/{slug}/posts` - Get posts by category

#### Tags

- **GET** `/api/blog/tags` - List all tags
- **GET** `/api/blog/tags/popular` - Popular tags
- **GET** `/api/blog/tags/{slug}/posts` - Get posts by tag

#### Authors

- **GET** `/api/blog/authors/{id}` - Get author with posts
- **GET** `/api/blog/authors/{id}/posts` - Get posts by author

You can consume these from any frontend (Blade, Vue, React, mobile, etc.).

### 6.2. Admin Endpoints (Protected)

Rate-limited to **60 requests per minute**. Requires Sanctum authentication.

**Include token in header:**
```
Authorization: Bearer {your-token}
```

#### Posts CRUD

- **GET** `/api/blog/admin/posts` - List all posts
- **POST** `/api/blog/admin/posts` - Create post
- **GET** `/api/blog/admin/posts/{id}` - Get post
- **PUT/PATCH** `/api/blog/admin/posts/{id}` - Update post
- **DELETE** `/api/blog/admin/posts/{id}` - Delete post

**Post Management Actions:**

- **POST** `/api/blog/admin/posts/{id}/publish` - Publish a post
- **POST** `/api/blog/admin/posts/{id}/unpublish` - Unpublish a post
- **POST** `/api/blog/admin/posts/{id}/toggle-status` - Toggle post status
- **POST** `/api/blog/admin/posts/{id}/schedule` - Schedule a post
- **POST** `/api/blog/admin/posts/{id}/duplicate` - Duplicate a post
- **POST** `/api/blog/admin/posts/{id}/archive` - Archive a post
- **POST** `/api/blog/admin/posts/{id}/restore` - Restore an archived post

#### Categories CRUD

- **GET** `/api/blog/admin/categories` - List all categories
- **POST** `/api/blog/admin/categories` - Create category
- **GET** `/api/blog/admin/categories/{id}` - Get category
- **PUT/PATCH** `/api/blog/admin/categories/{id}` - Update category
- **DELETE** `/api/blog/admin/categories/{id}` - Delete category

#### Media

- **POST** `/api/blog/admin/media/upload` - Upload media file

**Request:**
- Content-Type: `multipart/form-data`
- Field: `file` (image file, max 10MB)

To secure these, ensure Sanctum is installed and configured in your host app. See the [Installation Guide](INSTALLATION.md) for Sanctum setup.

## 7. How the Package Is Built (For Developers Extending It)

This section gives you a mental model of the internal architecture.

### 7.1. High-Level Architecture

- **Service Provider:** `BlogServiceProvider`
  - Merges config, binds repositories, loads migrations & routes
- **Facade:** `Ceygenic\Blog\Facades\Blog`
  - Provides a clean API: `Blog::posts()`, `Blog::categories()`, etc.
- **Core Class:** `Ceygenic\Blog\Blog`
  - Holds repository instances
  - Exposes convenience methods (`createDraft`, `publishPost`, etc.)
- **Repositories:**
  - Interfaces in `src/Contracts/Repositories`
  - Eloquent implementations in `src/Repositories/Eloquent`
  - Sanity implementations in `src/Repositories/Sanity`
- **Models:** `Post`, `Category`, `Tag`, `AuthorProfile`, `Media`
- **Traits:**
  - `HasSlug` — auto slug generation
  - `HasReadingTime` — auto reading time calculation
  - `BlogAuthor` — extend User model with author info
  - `HasCache` — small caching helper for repositories

### 7.2. Repository Pattern

Example: `PostRepositoryInterface`:

```php
all(), paginate()
find(), findBySlug()
create(), update(), delete()

Post management methods:
createDraft, publish, unpublish, toggleStatus, schedule, duplicate, archive, restore

Query helpers:
getPublished(), getDrafts(), getScheduled(), getArchived()
```

Then implemented by:
- `EloquentPostRepository` (database/Eloquent)
- `SanityPostRepository` (Sanity CMS)

The service provider chooses which to bind based on:

```php
$driver = config('blog.driver', 'db');
```

### 7.3. Traits and Observers

**HasSlug:**
- Listens to `creating` / `updating`
- Generates unique slug from title or name

**HasReadingTime:**
- Listens to `saving`
- Calculates reading time from content using `BLOG_READING_TIME_WPM`

**BlogAuthor:**
- Adds `authorProfile` (hasOne)
- Adds `blogPosts` (hasMany)
- Adds accessors (`bio`, `avatar`, `social links`)

### 7.4. Media Library

- **Model:** `Media`
- **Migration:** `media` table with `file_path`, `disk`, etc.
- **Controller:** `Admin\MediaController`
- Uses Laravel `Storage` facade
- Respects `config('blog.media.*')`

You can change the underlying disk (`local`, `s3`, `cloudinary`) via `config/filesystems.php` and the `BLOG_MEDIA_DISK` env variable.

## 8. Customizing and Extending in Your Project

### 8.1. Overriding Models

In `config/blog.php` you can override the default models:

```php
'models' => [
    'post' => \Ceygenic\Blog\Models\Post::class,
    'category' => \Ceygenic\Blog\Models\Category::class,
    'tag' => \Ceygenic\Blog\Models\Tag::class,
    'author_profile' => \Ceygenic\Blog\Models\AuthorProfile::class,
    'media' => \Ceygenic\Blog\Models\Media::class,
],
```

You could point these to your own extended models if needed.

### 8.2. Overriding Repositories

If you want to customize data access logic:

Create your own repository class in your app, e.g. `App\Repositories\CustomPostRepository`.

Bind it in a service provider after `BlogServiceProvider` is loaded:

```php
use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
use App\Repositories\CustomPostRepository;

public function register()
{
    $this->app->bind(PostRepositoryInterface::class, CustomPostRepository::class);
}
```

### 8.3. Adding Your Own Routes

You can add routes in your app that re-use the Blog facade or models, for example:

```php
Route::get('/my-custom-blog-feed', function () {
    $posts = Ceygenic\Blog\Facades\Blog::posts()->getPublished();
    return view('blog.feed', compact('posts'));
});
```

## 9. How to Use This Package Step-by-Step

**Step 1:** Install the package
```bash
composer require ceygenic/blog-core
```

**Step 2:** Publish config (optional)
```bash
php artisan vendor:publish --tag=blog-config
```

**Step 3:** Configure `.env`
```env
BLOG_DRIVER=db
BLOG_USER_MODEL=App\\Models\\User
BLOG_MEDIA_DISK=public
BLOG_CACHE_ENABLED=true
```

**Step 4:** Run migrations
```bash
php artisan migrate
```

**Step 5:** (Optional) Add `BlogAuthor` trait to your User model

```php
use Ceygenic\Blog\Traits\BlogAuthor;

class User extends Authenticatable
{
    use BlogAuthor;
}
```

**Step 6:** Test public API quickly
```bash
php artisan serve
```

In browser or Postman:
```
GET http://localhost:8000/api/blog/posts
```

**Step 7:** Protect admin routes with Sanctum

Install Sanctum in your app (if not already)
Configure `auth:sanctum` middleware
Use API tokens or SPA auth to call:
- `POST /api/blog/admin/posts`
- `POST /api/blog/admin/media/upload`

**Step 8:** Build your frontend/admin UI

- Use the documented endpoints to power your Vue/React/Blade admin
- Use `Blog` facade in controllers or services for server-side rendering

**Step 9:** (Optional) Switch to Sanity driver

In `.env`:
```env
BLOG_DRIVER=sanity
SANITY_PROJECT_ID=...
SANITY_DATASET=...
SANITY_TOKEN=...
```

Now the same public endpoints will read from Sanity instead of your local DB (where implemented).

## 10. Artisan Commands

The package includes an Artisan command for verifying dual storage:

```bash
php artisan blog:verify-dual-storage
```

This command verifies that data is synchronized between database and Sanity (if both are configured).

## 11. Where to Look in the Code (For Learning/Debugging)

- `src/BlogServiceProvider.php` — package registration & bindings
- `src/Blog.php` — core facade-backed class
- `src/Contracts/Repositories/*` — abstraction for data access
- `src/Repositories/Eloquent/*` — Eloquent implementations
- `src/Repositories/Sanity/*` — Sanity implementations
- `src/Models/*` — Eloquent models (check `$connection` property if using custom connections)
- `src/Http/Controllers/Api/*` — API controllers
- `src/Http/Resources/*` — JSON:API resources
- `src/Traits/*` — reusable logic (slugs, reading time, authors, cache)
- `tests/Feature/*` — best place to see examples of how everything works end-to-end

**Related Documentation:**

- See [Installation Guide](INSTALLATION.md) for installation and basic setup

## 12. Summary

- You can install this package into any Laravel 10, 11, or 12 project.
- It gives you a complete backend blog engine + API.
- It is designed to be driver-based (DB or Sanity), configurable, and extendable.
- Frontend/admin UI is up to your application — this package focuses on backend and API.
- If you follow the steps above, you can reuse this blog package across many projects with minimal setup.

