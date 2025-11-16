# Blog Package Installation Guide

This guide provides step-by-step instructions for installing and configuring the Blog Package in your Laravel application.

## 📋 Prerequisites

Before installing this package, ensure you have:

- **PHP** >= 8.2
- **Laravel** >= 10.0 or >= 11.0
- **Composer** installed
- **Database** configured (MySQL, PostgreSQL, SQLite, etc.)
- **Laravel Sanctum** installed (for admin API authentication)

---

## 🚀 Step 1: Install the Package

### Option A: Install from Packagist (Recommended)

If the package is published on Packagist:

```bash
composer require ceygenic/blog-core
```

### Option B: Install from Local/Private Repository

If installing from a local path or private repository:

```bash
composer require ceygenic/blog-core:dev-main
```

Or add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../CEYCDS-PK-COMPOSER-003-blog"
        }
    ],
    "require": {
        "ceygenic/blog-core": "@dev"
    }
}
```

Then run:

```bash
composer update
```

---

## ⚙️ Step 2: Publish Configuration

Publish the configuration file to customize package settings:

```bash
php artisan vendor:publish --tag=blog-config
```

This creates a `config/blog.php` file in your Laravel application's `config` directory.

---

## 🔧 Step 3: Configure Environment Variables

Add the following environment variables to your `.env` file:

```env
# Blog Package Configuration
BLOG_ENABLED=true
BLOG_PREFIX=[Blog]
BLOG_DRIVER=db

# Reading Time Configuration
BLOG_READING_TIME_WPM=200

# Sanity CMS Configuration (Optional - only if using Sanity driver)
SANITY_PROJECT_ID=your-project-id
SANITY_DATASET=production
SANITY_TOKEN=your-sanity-token
```

### Configuration Options Explained:

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `BLOG_ENABLED` | Enable/disable the blog package | `true` | No |
| `BLOG_PREFIX` | Default prefix for blog-related content | `[Blog]` | No |
| `BLOG_DRIVER` | Storage driver: `db` (database) or `sanity` (Sanity CMS) | `db` | No |
| `BLOG_READING_TIME_WPM` | Words per minute for reading time calculation | `200` | No |
| `SANITY_PROJECT_ID` | Sanity project ID (only if using Sanity driver) | - | Yes (if using Sanity) |
| `SANITY_DATASET` | Sanity dataset name | `production` | Yes (if using Sanity) |
| `SANITY_TOKEN` | Sanity API token | - | Yes (if using Sanity) |

---

## 🗄️ Step 4: Run Database Migrations

The package includes migrations for the following tables:
- `categories` - Blog categories
- `tags` - Blog tags
- `posts` - Blog posts
- `post_tag` - Pivot table for post-tag relationships

Run the migrations:

```bash
php artisan migrate
```

### Migration Details:

The migrations will create:

1. **categories** table:
   - `id` (primary key)
   - `name` (string)
   - `slug` (unique string)
   - `description` (text, nullable)
   - `timestamps`

2. **tags** table:
   - `id` (primary key)
   - `name` (string)
   - `slug` (unique string)
   - `timestamps`

3. **posts** table:
   - `id` (primary key)
   - `title` (string)
   - `slug` (unique string)
   - `excerpt` (text, nullable)
   - `content` (longText)
   - `featured_image` (string, nullable)
   - `category_id` (foreign key to categories, nullable)
   - `author_id` (foreign key to users, nullable)
   - `status` (enum: draft, published, archived)
   - `published_at` (timestamp, nullable)
   - `reading_time` (integer, nullable)
   - `timestamps`

4. **post_tag** table (pivot):
   - `post_id` (foreign key)
   - `tag_id` (foreign key)

### Customizing Migrations (Optional)

If you need to customize the migrations, publish them first:

```bash
php artisan vendor:publish --tag=blog-migrations
```

This copies the migration files to `database/migrations/` where you can modify them before running `php artisan migrate`.

---

## 🔐 Step 5: Set Up Authentication (For Admin API)

The admin API endpoints require Laravel Sanctum authentication. If you haven't installed Sanctum yet:

### Install Laravel Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Configure Sanctum

Add Sanctum middleware to your `app/Http/Kernel.php` (if not already added):

```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### Create API Token (For Testing)

You can create a token for a user:

```php
// In tinker or a seeder
$user = \App\Models\User::first();
$token = $user->createToken('blog-admin')->plainTextToken;
echo $token;
```

Or create a login endpoint in your application:

```php
// routes/api.php
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('blog-admin')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});
```

---

## 📦 Step 6: Install Required Dependencies

The package requires `spatie/laravel-query-builder` for filtering and sorting. It should be installed automatically, but if not:

```bash
composer require spatie/laravel-query-builder
```

---

## ✅ Step 7: Verify Installation

### Check Package Registration

The package should be auto-discovered by Laravel. Verify it's registered:

```bash
php artisan package:discover
```

You should see `Ceygenic\Blog\BlogServiceProvider` in the list.

### Test Routes

Check if routes are registered:

```bash
php artisan route:list | grep blog
```

You should see routes prefixed with `/api/blog`.

### Test the API

Start your Laravel development server:

```bash
php artisan serve
```

Test a public endpoint:

```bash
curl http://localhost:8000/api/blog/posts
```

You should receive a JSON response with an empty data array (if no posts exist yet).

---

## 🎯 Step 8: Using the Package

### Using the Facade

```php
use Ceygenic\Blog\Facades\Blog;

// Create a category
$category = Blog::categories()->create([
    'name' => 'Technology',
    'slug' => 'technology',
    'description' => 'Tech-related posts',
]);

// Create a post
$post = Blog::posts()->create([
    'title' => 'My First Blog Post',
    'slug' => 'my-first-blog-post',
    'content' => 'This is the content of my blog post...',
    'excerpt' => 'A brief excerpt',
    'category_id' => $category->id,
    'status' => 'published',
    'published_at' => now(),
]);

// Create tags
$tag1 = Blog::tags()->create(['name' => 'Laravel', 'slug' => 'laravel']);
$tag2 = Blog::tags()->create(['name' => 'PHP', 'slug' => 'php']);

// Attach tags to post
$post->tags()->attach([$tag1->id, $tag2->id]);
```

### Using Dependency Injection

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

### Using the Service Container

```php
$blog = app('blog');
$posts = $blog->posts()->all();
```

---

## 🌐 Step 9: API Endpoints

### Public Endpoints (No Authentication Required)

All public endpoints are rate-limited to 120 requests per minute.

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
- **GET** `/api/blog/tags/{slug}/posts` - Get posts by tag

#### Authors

- **GET** `/api/blog/authors/{id}` - Get author with posts

### Admin Endpoints (Authentication Required)

All admin endpoints require Sanctum authentication and are rate-limited to 60 requests per minute.

Include the token in the Authorization header:
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

---

## 📝 Step 10: Example API Usage

### Create a Post (Admin)

```bash
curl -X POST http://localhost:8000/api/blog/admin/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Getting Started with Laravel",
    "slug": "getting-started-with-laravel",
    "content": "Laravel is a powerful PHP framework...",
    "excerpt": "Learn the basics of Laravel",
    "category_id": 1,
    "status": "published",
    "published_at": "2024-01-01T00:00:00Z",
    "tags": [1, 2]
  }'
```

### List Published Posts (Public)

```bash
curl "http://localhost:8000/api/blog/posts?filter[status]=published&sort=-published_at"
```

### Search Posts (Public)

```bash
curl "http://localhost:8000/api/blog/posts/search?q=Laravel"
```

### Create a Category (Admin)

```bash
curl -X POST http://localhost:8000/api/blog/admin/categories \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Technology",
    "slug": "technology",
    "description": "Tech-related posts"
  }'
```

---

## 🔄 Step 11: Switching Storage Drivers

The package supports two storage drivers:

### Database Driver (Default)

Uses Laravel Eloquent to store data in your database. This is the default driver.

```env
BLOG_DRIVER=db
```

### Sanity CMS Driver

Uses Sanity CMS as the backend. Configure your Sanity credentials:

```env
BLOG_DRIVER=sanity
SANITY_PROJECT_ID=your-project-id
SANITY_DATASET=production
SANITY_TOKEN=your-sanity-token
```

**Note:** When using the Sanity driver, you don't need to run migrations as data is stored in Sanity CMS.

---

## 🛠️ Step 12: Artisan Commands

The package includes an Artisan command for verifying dual storage:

```bash
php artisan blog:verify-dual-storage
```

This command verifies that data is synchronized between database and Sanity (if both are configured).

---

## 🐛 Troubleshooting

### Issue: Routes not found (404 errors)

**Solution:**
1. Clear route cache: `php artisan route:clear`
2. Clear config cache: `php artisan config:clear`
3. Verify package is discovered: `php artisan package:discover`
4. Check routes: `php artisan route:list | grep blog`

### Issue: Authentication errors on admin endpoints

**Solution:**
1. Ensure Sanctum is installed and configured
2. Verify you're sending the token in the Authorization header: `Authorization: Bearer {token}`
3. Check that the token is valid and not expired
4. Verify the user exists in your database

### Issue: QueryBuilder errors

**Solution:**
```bash
composer require spatie/laravel-query-builder
```

### Issue: Migration errors

**Solution:**
1. Check if tables already exist: `php artisan migrate:status`
2. If tables exist, you may need to drop them first (be careful in production!)
3. Ensure your database connection is configured correctly in `.env`

### Issue: Package not auto-discovered

**Solution:**
1. Run: `php artisan package:discover`
2. Check `composer.json` has the package in `require` section
3. Verify `composer dump-autoload` has been run

### Issue: Class not found errors

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

---

## 📚 Additional Resources

- **API Documentation**: See `API_DOCUMENTATION.md` for detailed API reference
- **Testing Guide**: See `HOW_TO_TEST_API.md` for testing instructions
- **Post Management**: See `POST_MANAGEMENT_STEPS.md` for post management workflows

---

## ✅ Installation Checklist

Use this checklist to ensure you've completed all installation steps:

- [ ] Package installed via Composer
- [ ] Configuration file published (`php artisan vendor:publish --tag=blog-config`)
- [ ] Environment variables added to `.env`
- [ ] Database migrations run (`php artisan migrate`)
- [ ] Laravel Sanctum installed and configured (for admin API)
- [ ] `spatie/laravel-query-builder` installed
- [ ] Routes verified (`php artisan route:list | grep blog`)
- [ ] Tested public endpoint (e.g., `GET /api/blog/posts`)
- [ ] Created test user and token for admin API
- [ ] Tested admin endpoint with authentication

---

## 🎉 You're All Set!

Your blog package is now installed and ready to use. Start creating posts, categories, and tags through the API or using the Facade in your code.

For more information, refer to the API documentation or check the example usage above.

