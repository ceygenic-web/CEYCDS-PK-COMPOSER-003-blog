# Blog Package (ceygenic/blog-core)

A comprehensive Laravel package for managing blog posts, categories, tags, and authors. This package provides a complete RESTful API with public and admin endpoints, supporting both database and Sanity CMS storage drivers.

## 📋 Features

- ✅ Complete RESTful API (JSON:API compliant)
- ✅ Public and Admin endpoints
- ✅ Support for multiple storage drivers (Database & Sanity CMS)
- ✅ Advanced filtering, sorting, and pagination
- ✅ Automatic reading time calculation
- ✅ Slug generation for posts, categories, and tags
- ✅ Post status management (draft, published, archived)
- ✅ Post scheduling and archiving
- ✅ Media upload support
- ✅ Rate limiting
- ✅ Laravel Sanctum authentication for admin endpoints

## 📋 Requirements

- **PHP** >= 8.2
- **Laravel** >= 10.0 or >= 11.0
- **Composer**
- **Database** (MySQL, PostgreSQL, SQLite, etc.)
- **Laravel Sanctum** (for admin API authentication)

---

## 🚀 Installation & Setup

### Step 1: Install the Package

Install the package via Composer:

```bash
composer require ceygenic/blog-core
```

### Step 2: Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=blog-config
```

This creates a `config/blog.php` file in your Laravel application.

### Step 3: Configure Environment Variables

Add the following to your `.env` file:

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

**Configuration Options:**

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `BLOG_ENABLED` | Enable/disable the blog package | `true` | No |
| `BLOG_PREFIX` | Default prefix for blog-related content | `[Blog]` | No |
| `BLOG_DRIVER` | Storage driver: `db` (database) or `sanity` (Sanity CMS) | `db` | No |
| `BLOG_READING_TIME_WPM` | Words per minute for reading time calculation | `200` | No |
| `SANITY_PROJECT_ID` | Sanity project ID (only if using Sanity driver) | - | Yes (if using Sanity) |
| `SANITY_DATASET` | Sanity dataset name | `production` | Yes (if using Sanity) |
| `SANITY_TOKEN` | Sanity API token | - | Yes (if using Sanity) |

### Step 4: Configure Database Connection

The package uses Laravel's default database connection. Ensure your `.env` file has the correct database configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Using a Different Database Connection:**

If you want to store blog data in a separate database, you can configure a custom connection in `config/database.php`:

```php
'connections' => [
    'blog' => [
        'driver' => 'mysql',
        'host' => env('BLOG_DB_HOST', '127.0.0.1'),
        'port' => env('BLOG_DB_PORT', '3306'),
        'database' => env('BLOG_DB_DATABASE', 'blog_db'),
        'username' => env('BLOG_DB_USERNAME', 'root'),
        'password' => env('BLOG_DB_PASSWORD', ''),
        // ... other connection settings
    ],
],
```

Then set the connection in your models or via config. See the [Developer Guide](DEVELOPER_GUIDE.md#database-connection-configuration) for details.

### Step 5: Run Database Migrations

Run the migrations to create the required database tables:

   ```bash
php artisan migrate
```

**Tables Created:**
- `categories` - Blog categories
- `tags` - Blog tags
- `posts` - Blog posts
- `post_tag` - Pivot table for post-tag relationships
- `author_profiles` - Author profile information
- `media` - Media library files

**Note:** If using the Sanity driver (`BLOG_DRIVER=sanity`), you don't need to run migrations as data is stored in Sanity CMS.

### Step 6: Install Laravel Sanctum (For Admin API)

The admin API endpoints require Laravel Sanctum authentication. If you haven't installed it:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Step 6.5: Create Cache Table (For Rate Limiting)

The API routes use rate limiting which requires a cache table when using the database cache driver. Create it:

```bash
php artisan cache:table
php artisan migrate
```

**Note:** If you're using `CACHE_DRIVER=array` in your `.env` for development, you can skip this step. However, for production, it's recommended to use the database cache driver with the cache table.

### Step 7: Install Required Dependencies

The package requires `spatie/laravel-query-builder` for filtering and sorting. It should be installed automatically, but if not:

```bash
composer require spatie/laravel-query-builder
```

### Step 8: Verify Installation

Check if routes are registered:

```bash
php artisan route:list | grep blog
```

Test a public endpoint:

```bash
curl http://localhost:8000/api/blog/posts
```

You should receive a JSON response.

---

## 📖 Usage

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

## 🌐 API Endpoints

### Base URL

All API endpoints are prefixed with `/api/blog`

### Public Endpoints (No Authentication Required)

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
- **GET** `/api/blog/tags/{slug}/posts` - Get posts by tag

#### Authors

- **GET** `/api/blog/authors/{id}` - Get author with posts

### Admin Endpoints (Authentication Required)

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

---

## 📝 Example API Usage

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

### Get Authentication Token

Create a login endpoint in your application:

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

Then login to get a token:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

---

## 🔄 Storage Drivers

The package supports two storage drivers:

### Database Driver (Default)

Uses Laravel Eloquent to store data in your database.

```env
BLOG_DRIVER=db
```

### Sanity CMS Driver

Uses Sanity CMS as the backend.

```env
BLOG_DRIVER=sanity
SANITY_PROJECT_ID=your-project-id
SANITY_DATASET=production
SANITY_TOKEN=your-sanity-token
```

**Note:** When using the Sanity driver, you don't need to run migrations as data is stored in Sanity CMS.

---

## 🛠️ Artisan Commands

The package includes an Artisan command for verifying dual storage:

```bash
php artisan blog:verify-dual-storage
```

This command verifies that data is synchronized between database and Sanity (if both are configured).

---

## 🐛 Troubleshooting

### Routes not found (404 errors)

1. Clear route cache: `php artisan route:clear`
2. Clear config cache: `php artisan config:clear`
3. Verify package is discovered: `php artisan package:discover`
4. Check routes: `php artisan route:list | grep blog`

### Authentication errors on admin endpoints

1. Ensure Sanctum is installed and configured
2. Verify you're sending the token: `Authorization: Bearer {token}`
3. Check that the token is valid and not expired
4. Verify the user exists in your database

### QueryBuilder errors

```bash
composer require spatie/laravel-query-builder
```

### Migration errors

1. Check if tables already exist: `php artisan migrate:status`
2. Ensure your database connection is configured correctly in `.env`
3. Verify database credentials are correct
4. Check if you need to use a different database connection (see [Database Connection Configuration](#step-4-configure-database-connection))

### Package not auto-discovered

1. Run: `php artisan package:discover`
2. Check `composer.json` has the package in `require` section
3. Run: `composer dump-autoload`

---

## ✅ Installation Checklist

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

## 📚 Additional Documentation

- **[Developer Guide](DEVELOPER_GUIDE.md)** - In-depth guide for installing, configuring, and extending the package
- **[Version 2 Developer Guide](V2_DEVELOPER_GUIDE.md)** - Architecture documentation for developers building Version 2

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 👥 Support

For issues, questions, or contributions, please refer to the project repository.

---

## 🎉 You're All Set!

Your blog package is now installed and ready to use. Start creating posts, categories, and tags through the API or using the Facade in your code.
