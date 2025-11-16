# How to Test the RESTful API

This guide shows you how to test the RESTful API endpoints to verify they work correctly.

## 🧪 Method 1: Automated Tests (Recommended)

Run the automated API tests:

```bash
composer test
```

Or run specific API test files:

```bash
# Test all API endpoints
vendor/bin/phpunit tests/Feature/Api/

# Test public endpoints only
vendor/bin/phpunit tests/Feature/Api/PublicPostApiTest.php
vendor/bin/phpunit tests/Feature/Api/PublicCategoryApiTest.php
vendor/bin/phpunit tests/Feature/Api/PublicTagApiTest.php

# Test admin endpoints only
vendor/bin/phpunit tests/Feature/Api/AdminPostApiTest.php
vendor/bin/phpunit tests/Feature/Api/AdminCategoryApiTest.php
```

## 🧪 Method 2: Using Orchestra Testbench Workbench

To test API endpoints manually:

```bash
# Build workbench
composer build

# Navigate to workbench
cd workbench

# Run migrations
php artisan migrate

# Start server
php artisan serve
```

Then test endpoints using curl, Postman, or any HTTP client.

## 🧪 Method 3: Install in Laravel Application

### Step 1: Install Package in Laravel App

```bash
# In your Laravel app
composer require ceygenic/blog-core:@dev
```

### Step 2: Run Migrations

```bash
php artisan migrate
```

### Step 3: Test Endpoints

#### Public Endpoints (No Auth Required)

```bash
# List posts
curl http://localhost:8000/api/blog/posts

# Get single post
curl http://localhost:8000/api/blog/posts/test-post-slug

# Search posts
curl "http://localhost:8000/api/blog/posts/search?q=Laravel"

# Filter posts
curl "http://localhost:8000/api/blog/posts?filter[status]=published"

# Sort posts
curl "http://localhost:8000/api/blog/posts?sort=-published_at"

# List categories
curl http://localhost:8000/api/blog/categories

# Get posts by category
curl http://localhost:8000/api/blog/categories/technology/posts

# List tags
curl http://localhost:8000/api/blog/tags

# Get posts by tag
curl http://localhost:8000/api/blog/tags/laravel/posts
```

#### Admin Endpoints (Auth Required)

First, get a Sanctum token:

```bash
# Login and get token (example)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

Then use the token:

```bash
# Create post
curl -X POST http://localhost:8000/api/blog/admin/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Post",
    "slug": "new-post",
    "content": "Post content",
    "status": "published",
    "published_at": "2024-01-01T00:00:00Z"
  }'

# Update post
curl -X PUT http://localhost:8000/api/blog/admin/posts/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Updated Title"}'

# Delete post
curl -X DELETE http://localhost:8000/api/blog/admin/posts/1 \
  -H "Authorization: Bearer YOUR_TOKEN"

# Upload media
curl -X POST http://localhost:8000/api/blog/admin/media/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg"
```

## 🧪 Method 4: Using PHPUnit HTTP Testing

The test files use Laravel's HTTP testing methods:

```php
// Example from tests
$response = $this->getJson('/api/blog/posts');
$response->assertStatus(200);
$response->assertJsonStructure(['data']);
```

## 📋 Test Coverage

The automated tests cover:

### Public Endpoints
- ✅ List posts (with pagination)
- ✅ Get single post
- ✅ Search posts
- ✅ Filter posts by status
- ✅ Sort posts
- ✅ List categories
- ✅ Get posts by category
- ✅ List tags
- ✅ Get posts by tag
- ✅ 404 error handling

### Admin Endpoints
- ✅ Create post
- ✅ Update post
- ✅ Delete post
- ✅ Create category
- ✅ Update category
- ✅ Delete category
- ✅ Validation errors

## 🔍 Quick Test Checklist

Run these tests to verify everything works:

```bash
# 1. Run all tests
composer test

# 2. Check specific API tests pass
vendor/bin/phpunit tests/Feature/Api/

# 3. Verify JSON:API format
# Check that responses have 'data', 'type', 'id', 'attributes' structure

# 4. Verify filtering works
# Test: ?filter[status]=published

# 5. Verify sorting works
# Test: ?sort=-published_at

# 6. Verify pagination works
# Test: ?per_page=10&page=2
```

## 🐛 Troubleshooting

### Issue: Routes not found

**Solution:** Make sure routes are loaded:
```php
// In BlogServiceProvider::boot()
$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
```

### Issue: QueryBuilder errors

**Solution:** Install the package:
```bash
composer require spatie/laravel-query-builder
```

### Issue: Authentication errors on admin endpoints

**Solution:** 
- For tests: Use `$this->withoutMiddleware(['auth:sanctum'])`
- For real app: Set up Sanctum and provide valid token

### Issue: 404 on all endpoints

**Solution:** Check route registration and ensure you're using the correct URL prefix `/api/blog`

## 📝 Summary

**Easiest way to test:**

```bash
composer test
```

This will run all API tests automatically and verify:
- ✅ All endpoints respond correctly
- ✅ JSON:API format is correct
- ✅ Filtering and sorting work
- ✅ Pagination works
- ✅ CRUD operations work
- ✅ Error handling works

