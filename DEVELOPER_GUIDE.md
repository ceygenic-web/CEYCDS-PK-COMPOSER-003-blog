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

## 3. Linking to Your User Model

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

## 4. Dual Storage System

The package implements a **dual storage system** that allows you to choose between two storage backends for your blog content:

1. **Database Driver (`db`)** - Traditional Laravel Eloquent storage (default)
2. **Sanity CMS Driver (`sanity`)** - Headless CMS integration for content management

This architecture allows you to switch storage backends without changing your application code, providing flexibility for different deployment scenarios.

### 4.1. Overview

**Key Concept:** The storage driver is separate from database connection configuration.

- **Storage Driver** (`BLOG_DRIVER`) - Determines **how** data is stored and retrieved (Eloquent vs Sanity CMS)
- **Database Connection** (covered in section 4.3.1) - Determines **which** database server/connection to use when the `db` driver is selected

**How It Works:**
- The package uses a **Repository Pattern** with interfaces
- Service Provider binds the appropriate repository implementations based on the configured driver
- All API endpoints and Facade methods work identically regardless of driver
- Only the underlying data access layer changes

### 4.2. Configuration

The driver is controlled by `config('blog.driver')` or the `.env` file:

```env
# Use Eloquent/database (default)
BLOG_DRIVER=db

# OR use Sanity CMS for read operations
BLOG_DRIVER=sanity
```

You can also set it programmatically in `config/blog.php`:

```php
'driver' => env('BLOG_DRIVER', 'db'),
```

### 4.3. Database Driver (`db`)

The **database driver** uses Laravel Eloquent ORM to store and retrieve blog data from your database.

#### Features

-  **Full CRUD Support** - Create, read, update, and delete operations
-  **All Post Management Features** - Publish, unpublish, schedule, duplicate, archive, restore
-  **Advanced Querying** - Complex filters, sorting, pagination
-  **Relationships** - Full Eloquent relationships between posts, categories, tags, authors
-  **Transactions** - Database transactions for data integrity
-  **Migrations** - Automatic migration loading for database schema

#### Models Used

- `Ceygenic\Blog\Models\Post`
- `Ceygenic\Blog\Models\Category`
- `Ceygenic\Blog\Models\Tag`
- `Ceygenic\Blog\Models\AuthorProfile`
- `Ceygenic\Blog\Models\Media`

#### Repositories

- `EloquentPostRepository` - Handles all post operations
- `EloquentCategoryRepository` - Manages categories
- `EloquentTagRepository` - Manages tags

#### Database Connection Configuration

When using the `db` driver, you can configure which database connection to use:

**Default Connection:**

By default, the package uses Laravel's default database connection (configured via `DB_CONNECTION` in your `.env`). All blog models will use this connection.

**Using a Separate Database Connection:**

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

#### Basic Configuration

When using the `db` driver, configure in your `.env`:

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

#### Use Cases

- Standard Laravel applications with MySQL/PostgreSQL/SQLite
- Full control over database schema and queries
- Production deployments requiring full CRUD capabilities
- Applications needing advanced querying and relationships



### 4.4. Sanity CMS Driver (`sanity`)

The **Sanity driver** integrates with Sanity CMS as a headless content management system.

#### Features

-  **Read Operations** - Fetch posts, categories, tags from Sanity via GROQ queries
-  **Content Management** - Content managed through Sanity Studio
-  **Write Operations** - Limited (most create/update/delete operations are stubbed)

#### Configuration

Set in `.env`:

```env
BLOG_DRIVER=sanity
SANITY_PROJECT_ID=your-project-id
SANITY_DATASET=production
SANITY_TOKEN=your-sanity-token
```

Or in `config/blog.php`:

```php
'sanity' => [
    'project_id' => env('SANITY_PROJECT_ID', ''),
    'dataset' => env('SANITY_DATASET', 'production'),
    'token' => env('SANITY_TOKEN', null),
],
```

#### Repositories

- `SanityPostRepository` - Fetches posts from Sanity using GROQ
- `SanityCategoryRepository` - Fetches categories from Sanity
- `SanityTagRepository` - Fetches tags from Sanity

#### Limitations

- **Read-Only for Most Operations** - Create/update/delete operations throw `RuntimeException` until implemented
- **No Eloquent Relationships** - Relationships must be resolved manually in GROQ queries
- **Content Management via Sanity Studio** - Content is managed through Sanity's admin interface, not Laravel
- **Requires Sanity Setup** - You need a Sanity project, schema, and proper GROQ queries configured

#### Use Cases

- Content teams already using Sanity CMS
- Decoupled architecture where content management is separate from application
- Read-only blog displays powered by Sanity content
- Multi-channel content distribution (Sanity content consumed by multiple applications)



### 4.5. Repository Pattern Architecture

The dual storage system is built on a **repository pattern** with clear interfaces:

#### Interfaces

Located in `src/Contracts/Repositories/`:
- `PostRepositoryInterface` - Defines contract for post operations
- `CategoryRepositoryInterface` - Defines contract for category operations
- `TagRepositoryInterface` - Defines contract for tag operations

#### Implementations

**Eloquent Implementations** (`src/Repositories/Eloquent/`):
- `EloquentPostRepository`
- `EloquentCategoryRepository`
- `EloquentTagRepository`

**Sanity Implementations** (`src/Repositories/Sanity/`):
- `SanityPostRepository`
- `SanityCategoryRepository`
- `SanityTagRepository`

#### Service Provider Binding

The `BlogServiceProvider` automatically binds the correct implementations based on the driver:

```php
protected function registerRepositories(): void
{
    $driver = config('blog.driver', 'db');

    if ($driver === 'sanity') {
        $this->app->bind(PostRepositoryInterface::class, SanityPostRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, SanityCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, SanityTagRepository::class);
    } else {
        $this->app->bind(PostRepositoryInterface::class, EloquentPostRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
    }
}
```

### 4.6. Switching Between Drivers

You can switch drivers at any time by changing the `BLOG_DRIVER` environment variable:

```env
# Switch to database driver
BLOG_DRIVER=db

# Switch to Sanity driver
BLOG_DRIVER=sanity
```

**Important Notes:**
- The switch happens at **runtime** - no code changes needed
- Both drivers must have their respective configurations set up
- When switching from `db` to `sanity`, ensure Sanity project is configured
- When switching from `sanity` to `db`, ensure database migrations have been run

### 4.7. Driver Selection Guide

#### Choose Database Driver (`db`) When:

- You need full CRUD capabilities
- Your team prefers managing content through Laravel
- You want direct database access and querying
- You're building a traditional Laravel application
- You need advanced querying, relationships, and transactions

#### Choose Sanity Driver (`sanity`) When:

- Your content team already uses Sanity CMS
- You want decoupled content management (content managed separately from code)
- You're building read-only blog displays
- You need multi-channel content distribution
- You want non-technical team members to manage content via Sanity Studio

### 4.8. Verifying Dual Storage

The package includes an Artisan command to verify dual storage configuration:

```bash
php artisan blog:verify-dual-storage
```

This command:
- Verifies that both drivers can be configured
- Checks repository bindings
- Validates Sanity configuration (if driver is set to `sanity`)
- Tests database connectivity (if driver is set to `db`)

### 4.9. Extending the Storage System

If you need to add a custom storage driver (e.g., MongoDB, Firebase):

1. **Create Repository Interface Implementation:**
   ```php
   namespace App\Repositories\Custom;
   
   use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
   
   class CustomPostRepository implements PostRepositoryInterface
   {
       // Implement all interface methods
   }
   ```

2. **Bind in Service Provider:**
   ```php
   public function register()
   {
       if (config('blog.driver') === 'custom') {
           $this->app->bind(PostRepositoryInterface::class, CustomPostRepository::class);
       }
   }
   ```

3. **Update Configuration:**
   Add your custom driver option to `config/blog.php` and update `BlogServiceProvider` logic.



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

## 6. RESTful API

The package provides a complete RESTful API under `/api/blog`. All endpoints follow JSON:API specification and support filtering, sorting, and pagination.

### 6.1. API Response Format

All API responses follow the **JSON:API** specification format:

```json
{
  "data": {
    "type": "posts",
    "id": "1",
    "attributes": {
      "title": "My Post Title",
      "slug": "my-post-title",
      "content": "Post content here...",
      "excerpt": "Short excerpt",
      "status": "published",
      "published_at": "2024-01-15T10:00:00.000000Z",
      "reading_time": 5
    },
    "relationships": {
      "category": {
        "data": {
          "type": "categories",
          "id": "1",
          "attributes": {
            "name": "Technology",
            "slug": "technology"
          }
        }
      },
      "tags": {
        "data": [
          {
            "type": "tags",
            "id": "1",
            "attributes": {
              "name": "Laravel",
              "slug": "laravel"
            }
          }
        ]
      },
      "author": {
        "data": {
          "type": "authors",
          "id": "1",
          "attributes": {
            "name": "John Doe",
            "email": "john@example.com"
          }
        }
      }
    }
  },
  "links": {
    "self": "http://example.com/api/blog/posts/my-post-title"
  }
}
```

### 6.2. Public Endpoints

**Base URL:** `/api/blog`  
**Rate Limit:** 120 requests/minute  
**Authentication:** Not required

**Posts:**
- `GET /api/blog/posts` - List posts (paginated, filterable, sortable)
- `GET /api/blog/posts/{slug}` - Get single post by slug
- `GET /api/blog/posts/search?q={query}` - Search posts

**Categories:**
- `GET /api/blog/categories` - List categories
- `GET /api/blog/categories/{slug}/posts` - Posts by category

**Tags:**
- `GET /api/blog/tags` - List tags
- `GET /api/blog/tags/popular?limit=10` - Popular tags
- `GET /api/blog/tags/{slug}/posts` - Posts by tag

**Authors:**
- `GET /api/blog/authors/{id}` - Get author with profile
- `GET /api/blog/authors/{id}/posts` - Posts by author

### 6.3. Admin Endpoints

**Base URL:** `/api/blog/admin`  
**Rate Limit:** 60 requests/minute  
**Authentication:** Required (Sanctum token)

**Header:** `Authorization: Bearer {token}`

**Posts:**
- `GET /api/blog/admin/posts` - List all posts
- `POST /api/blog/admin/posts` - Create post
- `GET /api/blog/admin/posts/{id}` - Get post
- `PUT/PATCH /api/blog/admin/posts/{id}` - Update post
- `DELETE /api/blog/admin/posts/{id}` - Delete post
- `POST /api/blog/admin/posts/{id}/publish` - Publish
- `POST /api/blog/admin/posts/{id}/unpublish` - Unpublish
- `POST /api/blog/admin/posts/{id}/toggle-status` - Toggle status
- `POST /api/blog/admin/posts/{id}/schedule` - Schedule
- `POST /api/blog/admin/posts/{id}/duplicate` - Duplicate
- `POST /api/blog/admin/posts/{id}/archive` - Archive
- `POST /api/blog/admin/posts/{id}/restore` - Restore

**Categories:**
- `GET /api/blog/admin/categories` - List
- `POST /api/blog/admin/categories` - Create
- `GET /api/blog/admin/categories/{id}` - Get
- `PUT/PATCH /api/blog/admin/categories/{id}` - Update
- `DELETE /api/blog/admin/categories/{id}` - Delete

**Media:**
- `POST /api/blog/admin/media/upload` - Upload (multipart/form-data, max 10MB)
- `GET /api/blog/admin/media` - List
- `GET /api/blog/admin/media/{id}` - Get
- `PUT/PATCH /api/blog/admin/media/{id}` - Update

### 6.4. Query Parameters

**Pagination:**
- `per_page` - Items per page (default: 15, max: 100)
- `page` - Page number (default: 1)

**Filtering:**
- `filter[status]` - draft, published, archived
- `filter[category_id]` - Filter by category
- `filter[author_id]` - Filter by author
- `filter[title]` - Partial match on title
- `tag_id` - Filter by tag ID

**Sorting:**
- `sort=field` - Ascending (e.g., `sort=published_at`)
- `sort=-field` - Descending (e.g., `sort=-published_at`)
- `sort=field1,field2` - Multiple fields



## 7. Post Management

The package provides comprehensive post management features including CRUD operations, status management, scheduling, and more.

### 7.1. Post Fields

Each post supports the following fields:

- **`title`** (required) - Post title
- **`slug`** (optional) - URL-friendly slug (auto-generated from title if not provided)
- **`content`** (required) - Full post content
- **`excerpt`** (optional) - Short description or summary
- **`featured_image`** (optional) - Featured image URL/path
- **`category_id`** (optional) - Category assignment (single category)
- **`author_id`** (optional) - Author assignment (single author)
- **`status`** (optional) - Post status: `draft`, `published`, or `archived` (default: `draft`)
- **`published_at`** (optional) - Publication date/time (supports scheduling)
- **`reading_time`** (auto-calculated) - Estimated reading time in minutes
- **`index`** (optional) - Custom ordering index

### 7.2. Post Status Management

Posts can have three statuses:

#### Draft Status
- Status: `draft`
- Not visible to public
- Can be edited and published later

#### Published Status
- Status: `published`
- Visible to public (if `published_at` is in the past)
- Appears in public API endpoints

#### Archived Status
- Status: `archived`
- Hidden from public view
- Can be restored to draft status


### 7.3. Post Actions

**Available:** publish, unpublish, toggle-status, schedule, duplicate, archive, restore

**Via Facade:**
```php
Blog::publishPost($id);
Blog::unpublishPost($id);
Blog::togglePostStatus($id);
Blog::schedulePost($id, new \DateTime('2024-12-25 10:00:00'));
Blog::duplicatePost($id, 'New Title');
Blog::archivePost($id);
Blog::restorePost($id);
```

**Via API:** `POST /api/blog/admin/posts/{id}/{action}`

### 7.4. Querying Posts

**Methods:**
- `all()` - Get all posts
- `getPublished()` - Published posts only
- `getDrafts()` - Draft posts
- `getScheduled()` - Scheduled posts
- `getArchived()` - Archived posts
- `find($id)` - Find by ID
- `findBySlug($slug)` - Find by slug
- `paginate($perPage)` - Paginated results



### 7.5. Automatic Features

- **Auto Slug Generation** - Generated from title if not provided
- **Auto Reading Time** - Calculated from content (uses `BLOG_READING_TIME_WPM`, default: 200 WPM)


## 8. Category System

The package provides a simple, flat category system (no hierarchy) with ordering capabilities.

### 8.1. Category Fields

Each category supports:
- **`name`** (required) - Category name
- **`slug`** (optional) - URL-friendly slug (auto-generated from name)
- **`description`** (optional) - Category description
- **`order`** (optional) - Display order (integer, default: 0)

### 8.2. Category Operations

**Create:**
```php
$category = Blog::categories()->create([
    'name' => 'Technology',
    'description' => 'Tech-related posts',
    'order' => 1,
]);
```

**Order Management:**
- `moveUp()` - Move category up
- `moveDown()` - Move category down
- `setOrder($order)` - Set specific order

**Get Categories:**
```php
$categories = Blog::getCategoriesOrdered();  // Sorted by order
$category->post_count;  // Auto-calculated
$category->posts;  // Get posts in category
```

**Auto Features:**
- Slug auto-generated from name
- Post count auto-calculated

## 9. Tag System

The package provides a free-form tagging system with popular tags support.

### 9.1. Tag Fields

Each tag supports:
- **`name`** (required) - Tag name
- **`slug`** (optional) - URL-friendly slug (auto-generated from name)
- **`description`** (optional) - Tag description

### 9.2. Tag Operations

**Create:**
```php
$tag = Blog::tags()->create(['name' => 'Laravel']);
```

**Get Tags:**
```php
$tags = Blog::tags()->all();
$popular = Blog::getPopularTags(10);
$tag->post_count;  // Auto-calculated
$tag->posts;  // Get posts with tag
```

**Auto-Complete:**
Use search filter: `GET /api/blog/tags?filter[name]=lar`

**Auto Features:**
- Slug auto-generated from name
- Post count auto-calculated

## 10. Author Management

The package links blog posts to Laravel's User model, allowing you to track authors and their profiles.

### 10.1. Author Profile

Authors are linked to your Laravel User model via the `AuthorProfile` model:

**Author Profile Fields:**
- **`user_id`** (required) - Foreign key to users table
- **`bio`** (optional) - Author biography
- **`avatar`** (optional) - Avatar image path/URL
- **`social_links`** (optional) - JSON array of social media links

### 10.2. Setting Up Authors

#### Step 1: Link User Model

The package automatically uses your Laravel User model (configured in `config/blog.php`):

```php
'author' => [
    'user_model' => env('BLOG_USER_MODEL', config('auth.providers.users.model', 'App\\Models\\User')),
],
```

#### Step 2: Add BlogAuthor Trait (Optional but Recommended)

Add the `BlogAuthor` trait to your User model for convenience methods:

```php
namespace App\Models;

use Ceygenic\Blog\Traits\BlogAuthor;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use BlogAuthor;
    
    // ... rest of your User model
}
```

This trait provides:
- `$user->authorProfile` - Relationship to AuthorProfile
- `$user->blogPosts` - Relationship to blog posts
- `$user->bio` - Accessor for bio
- `$user->avatar` - Accessor for avatar
- `$user->social_links` - Accessor for social links
- `$user->updateAuthorProfile()` - Method to update/create profile


## 11. Media Library

The package includes a media library system for managing uploaded images and files.

### 11.1. Media Fields

Each media item supports:
- **`file_name`** - Original file name
- **`file_path`** - Storage path
- **`mime_type`** - File MIME type
- **`file_size`** - File size in bytes
- **`alt_text`** (optional) - Alternative text for images
- **`caption`** (optional) - Image caption
- **`disk`** - Storage disk name (local, s3, etc.)

### 11.2. Media Operations

**Upload:**
```bash
POST /api/blog/admin/media/upload
Content-Type: multipart/form-data

file: [binary]
alt_text: "Alt text"
caption: "Caption"
```

**Supported:** JPEG, PNG, GIF, WebP, SVG, PDF, MP4, WebM  
**Max Size:** 10MB (configurable)

**List/Get/Update/Delete:**
- `GET /api/blog/admin/media` - List (paginated)
- `GET /api/blog/admin/media/{id}` - Get single
- `PUT /api/blog/admin/media/{id}` - Update (alt_text, caption)
- `DELETE /api/blog/admin/media/{id}` - Delete (removes file and record)

### 11.7. Storage Configuration

Media files can be stored on different disks configured in `config/filesystems.php`:

**Local Storage (Default):**
```env
BLOG_MEDIA_DISK=public
BLOG_MEDIA_DIRECTORY=blog/media
```

**Amazon S3:**
```env
BLOG_MEDIA_DISK=s3
BLOG_MEDIA_DIRECTORY=blog/media
```

**Cloudinary:**
```env
BLOG_MEDIA_DISK=cloudinary
BLOG_MEDIA_DIRECTORY=blog/media
```

Configure disks in `config/filesystems.php`:

```php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
],
```

### 11.8. Using Media in Posts

Once uploaded, you can reference media in your posts:

```php
$post = Blog::posts()->create([
    'title' => 'My Post',
    'content' => '...',
    'featured_image' => '/blog/media/image-1234567890.jpg',
]);
```

## 12. Search & Filtering

The package provides comprehensive search and filtering capabilities for posts.

### 12.1. Full-Text Search

Search posts by title, content, and excerpt:

**Via Facade:**
```php
$results = Blog::posts()->search('Laravel tutorial', 15);
```

**Via API:**
```bash
GET /api/blog/posts/search?q=Laravel+tutorial
```

**Search Features:**
- Searches in `title`, `content`, and `excerpt` fields
- Case-insensitive matching
- Relevance sorting (title matches prioritized over content matches)
- Returns paginated results

### 12.2. Filtering

**Available Filters:**
- `filter[status]` - draft, published, archived
- `filter[category_id]` - By category
- `filter[author_id]` - By author
- `filter[title]` - Partial match
- `tag_id` - By tag
- `start_date` / `end_date` - Date range

**Via Model Scopes:**
```php
Post::byCategory($id)->get();
Post::byTag($id)->get();
Post::byAuthor($id)->get();
Post::byDateRange($start, $end)->get();
Post::byStatus('published')->get();
```

**Combine Filters:**
```bash
GET /api/blog/posts?filter[status]=published&filter[category_id]=1&sort=-published_at
```

### 12.3. Search with Filters

```bash
GET /api/blog/posts/search?q=Laravel&filter[status]=published&sort=-published_at
```

Search automatically ranks title matches higher than content matches.


## 14. Customizing and Extending

### 14.1. Overriding Models

Override models in `config/blog.php`:

```php
'models' => [
    'post' => \App\Models\Post::class,  // Your custom Post model
    'category' => \App\Models\Category::class,
    // ... other models
],
```

### 14.2. Overriding Repositories

Create custom repository and bind in your service provider:

```php
use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
use App\Repositories\CustomPostRepository;

public function register()
{
    $this->app->bind(PostRepositoryInterface::class, CustomPostRepository::class);
}
```

### 14.3. Adding Custom Routes

```php
Route::get('/my-custom-blog-feed', function () {
    $posts = Ceygenic\Blog\Facades\Blog::posts()->getPublished();
    return view('blog.feed', compact('posts'));
});
```

## 15. Artisan Commands

```bash
php artisan blog:verify-dual-storage
```

Verifies dual storage configuration and repository bindings.

## 16. Code Structure

Key files and directories for understanding the package:

- `src/BlogServiceProvider.php` - Package registration & repository bindings
- `src/Blog.php` - Core facade-backed class
- `src/Contracts/Repositories/*` - Repository interfaces
- `src/Repositories/Eloquent/*` - Database implementations
- `src/Repositories/Sanity/*` - Sanity CMS implementations
- `src/Models/*` - Eloquent models
- `src/Http/Controllers/Api/*` - API controllers
- `src/Http/Resources/*` - JSON:API resources
- `src/Traits/*` - Reusable traits (HasSlug, HasReadingTime, BlogAuthor, HasCache)
- `tests/Feature/*` - Feature tests and examples

For detailed installation instructions, see the [Installation Guide](INSTALLATION.md).

