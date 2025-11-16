# RESTful API Documentation

## Overview

The Blog Package provides a complete RESTful API with public and admin endpoints, following JSON:API specification.

## Installation

After installing the package, run:

```bash
composer require spatie/laravel-query-builder
```

## API Endpoints

### Base URL

All API endpoints are prefixed with `/api/blog`

### Public Endpoints (No Authentication Required)

#### Posts

- **GET** `/api/blog/posts` - List all posts (paginated, filterable, sortable)
- **GET** `/api/blog/posts/{slug}` - Get single post by slug
- **GET** `/api/blog/posts/search?q={query}` - Search posts

**Query Parameters:**
- `filter[title]` - Filter by title
- `filter[status]` - Filter by status (draft, published, archived)
- `filter[category_id]` - Filter by category ID
- `sort` - Sort by field (title, published_at, created_at)
- `per_page` - Items per page (default: 15)

**Example:**
```
GET /api/blog/posts?filter[status]=published&sort=-published_at&per_page=10
```

#### Categories

- **GET** `/api/blog/categories` - List all categories
- **GET** `/api/blog/categories/{slug}/posts` - Get posts by category

**Query Parameters:**
- `filter[name]` - Filter by name
- `filter[slug]` - Filter by slug
- `sort` - Sort by field (name, created_at)
- `per_page` - Items per page (default: 15)

#### Tags

- **GET** `/api/blog/tags` - List all tags
- **GET** `/api/blog/tags/{slug}/posts` - Get posts by tag

**Query Parameters:**
- `filter[name]` - Filter by name
- `filter[slug]` - Filter by slug
- `sort` - Sort by field (name, created_at)
- `per_page` - Items per page (default: 15)

#### Authors

- **GET** `/api/blog/authors/{id}` - Get author with posts

### Admin Endpoints (Authentication Required)

All admin endpoints require Sanctum authentication and are rate-limited to 60 requests per minute.

#### Posts CRUD

- **GET** `/api/blog/admin/posts` - List all posts
- **POST** `/api/blog/admin/posts` - Create post
- **GET** `/api/blog/admin/posts/{id}` - Get post
- **PUT/PATCH** `/api/blog/admin/posts/{id}` - Update post
- **DELETE** `/api/blog/admin/posts/{id}` - Delete post



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



## Authentication

Admin endpoints require Sanctum authentication. Include the token in the Authorization header:

```
Authorization: Bearer {your-token}
```



## Features Implemented

JSON:API compliant responses
Filtering & Sorting (using spatie/laravel-query-builder)
Pagination (Laravel's built-in paginator)
Rate limiting (middleware on routes)
Authentication (Sanctum integration)
Public endpoints (posts, categories, tags, authors)
Admin endpoints (CRUD operations)
Media upload endpoint

