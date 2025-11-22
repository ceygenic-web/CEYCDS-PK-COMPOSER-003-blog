# Installation & Setup

## Step 1: Install the Package

Install the package via Composer:

```bash
composer require ceygenic/blog-core
```

## Step 2: Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=blog-config
```

This creates a `config/blog.php` file in your Laravel application.

## Step 3: Configure Environment Variables

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

### Configuration Options

The following table describes all available environment variables:

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `BLOG_ENABLED` | Enable/disable the blog package | `true` | No |
| `BLOG_PREFIX` | Default prefix for blog-related content | `[Blog]` | No |
| `BLOG_DRIVER` | Storage driver: `db` (database) or `sanity` (Sanity CMS) | `db` | No |
| `BLOG_READING_TIME_WPM` | Words per minute for reading time calculation | `200` | No |
| `SANITY_PROJECT_ID` | Sanity project ID (only if using Sanity driver) | - | Yes (if using Sanity) |
| `SANITY_DATASET` | Sanity dataset name | `production` | Yes (if using Sanity) |
| `SANITY_TOKEN` | Sanity API token | - | Yes (if using Sanity) |

## Step 4: Configure Database Connection

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

## Step 5: Run Database Migrations

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

## Step 6: Install Laravel Sanctum (For Admin API)

The admin API endpoints require Laravel Sanctum authentication. If you haven't installed it:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## Step 6.5: Create Cache Table (For Rate Limiting)

The API routes use rate limiting which requires a cache table when using the database cache driver. Create it:

```bash
php artisan cache:table
php artisan migrate
```

**Note:** If you're using `CACHE_DRIVER=array` in your `.env` for development, you can skip this step. However, for production, it's recommended to use the database cache driver with the cache table.

## Step 7: Install Required Dependencies

The package requires `spatie/laravel-query-builder` for filtering and sorting. It should be installed automatically, but if not:

```bash
composer require spatie/laravel-query-builder
```

## Step 8: Verify Installation

Check if routes are registered:

```bash
php artisan route:list | grep blog
```

Test a public endpoint:

```bash
curl http://localhost:8000/api/blog/posts
```

You should receive a JSON response.

