## Version 1 Architecture Documentation – `ceygenic/blog-core`

This document documents **what was implemented in Version 1** to help developers who will build **Version 2**. It focuses on architecture, design decisions, and implementation details rather than prescribing future changes.

---

### 1. Core Architecture Overview

Version 1 implements a **headless blog engine** with the following structure:

#### 1.1. Service Provider Pattern
- **`BlogServiceProvider`** (`src/BlogServiceProvider.php`):
  - Merges config from `config/blog.php`
  - Registers repository bindings based on driver (`db` or `sanity`)
  - Binds the `Blog` core class as a singleton under the `'blog'` key
  - Auto-loads migrations from `database/migrations/`
  - Loads API routes from `routes/api.php`
  - Registers Artisan commands (e.g., `blog:verify-dual-storage`)

#### 1.2. Facade Pattern
- **`Blog` Facade** (`src/Facades/Blog.php`):
  - Provides static access to the core `Blog` class
  - Used as: `Blog::posts()`, `Blog::createDraft()`, etc.

#### 1.3. Core Class
- **`Blog`** (`src/Blog.php`):
  - Holds three repository instances (Post, Category, Tag)
  - Exposes convenience methods that delegate to repositories
  - Methods include: `createDraft()`, `publishPost()`, `getCategoriesOrdered()`, `searchTags()`, etc.

---

### 2. Repository Pattern Implementation

Version 1 uses a **repository pattern** to abstract data access:

#### 2.1. Repository Interfaces
Located in `src/Contracts/Repositories/`:
- `PostRepositoryInterface` – defines all post operations
- `CategoryRepositoryInterface` – defines category operations
- `TagRepositoryInterface` – defines tag operations

#### 2.2. Repository Implementations
- **Eloquent Repositories** (`src/Repositories/Eloquent/`):
  - `EloquentPostRepository` – full CRUD + post management (publish, archive, duplicate, etc.)
  - `EloquentCategoryRepository` – CRUD + order management (moveUp, moveDown, setOrder)
  - `EloquentTagRepository` – CRUD + search and popular tags

- **Sanity Repositories** (`src/Repositories/Sanity/`):
  - `SanityPostRepository` – read operations via GROQ, write operations throw `RuntimeException`
  - `SanityCategoryRepository` – read operations, write operations stubbed
  - `SanityTagRepository` – read operations, write operations stubbed

#### 2.3. Driver-Based Binding
The `BlogServiceProvider::registerRepositories()` method:
- Reads `config('blog.driver')` (defaults to `'db'`)
- Binds interfaces to Eloquent or Sanity implementations accordingly
- This allows switching storage backends via configuration

---

### 3. Models and Database Structure

#### 3.1. Eloquent Models
Located in `src/Models/`:
- `Post` – blog posts with status (draft, published, archived), scheduling, slugs
- `Category` – categories with ordering support
- `Tag` – tags with many-to-many relationship to posts
- `AuthorProfile` – extends user model with bio, avatar, social links
- `Media` – media library for file uploads

#### 3.2. Database Migrations
Located in `database/migrations/`:
- `create_users_table.php` – only creates if table doesn't exist (prevents conflicts)
- `create_categories_table.php` – includes `order` field for manual ordering
- `create_tags_table.php` – simple name/slug structure
- `create_posts_table.php` – includes `status`, `published_at`, `archived_at`, indexes
- `create_post_tag_table.php` – pivot table for many-to-many
- `create_author_profiles_table.php` – extends users with blog-specific fields
- `create_media_table.php` – stores file paths, disk, metadata

#### 3.3. Database Connection
- **Default connection**: All models use Laravel's default database connection
- **No explicit connection**: Models don't set `$connection` property, relying on Laravel's default
- **Custom connections**: Can be configured by extending models and setting `$connection` property
- **Connection configuration**: Handled via Laravel's standard `config/database.php` configuration

#### 3.4. Key Database Design Decisions
- **Indexes**: Added on `status`, `published_at`, `category_id`, `author_id` for performance
- **Soft deletes**: Not used; posts use `archived_at` instead
- **User table**: Package checks for existing `users` table before creating

---

### 4. Traits and Shared Behaviors

Version 1 includes reusable traits in `src/Traits/`:

#### 4.1. `HasSlug`
- Auto-generates unique slugs from `title` or `name` fields
- Listens to model `creating` and `updating` events
- Used by `Post`, `Category`, and `Tag` models

#### 4.2. `HasReadingTime`
- Calculates reading time from `content` field
- Uses `BLOG_READING_TIME_WPM` config (default: 200 words per minute)
- Listens to model `saving` event
- Used by `Post` model

#### 4.3. `BlogAuthor`
- Extends host app's `User` model with blog-specific functionality
- Adds relationships: `authorProfile` (hasOne), `blogPosts` (hasMany)
- Adds accessors: `bio`, `avatar`, `social_links`
- Optional trait for host apps to include

#### 4.4. `HasCache`
- Simple caching helper for repositories
- Provides `remember()` and `forget()` methods
- Uses Laravel's cache with configurable TTL and prefix

---

### 5. HTTP Layer (API)

#### 5.1. Route Structure
Routes defined in `routes/api.php`:
- **Public routes** (`/api/blog/*`):
  - Rate-limited to 120 requests/minute
  - No authentication required
  - Endpoints: posts, categories, tags, authors, search

- **Admin routes** (`/api/blog/admin/*`):
  - Rate-limited to 60 requests/minute
  - Protected by `auth:sanctum` middleware
  - Full CRUD + management actions (publish, archive, duplicate, etc.)

#### 5.2. Controllers
Located in `src/Http/Controllers/Api/`:
- **Public controllers** (`Public/`):
  - `PostController` – index, show, search
  - `CategoryController` – index, posts by category
  - `TagController` – index, popular, posts by tag
  - `AuthorController` – show, posts by author

- **Admin controllers** (`Admin/`):
  - `PostController` – full CRUD + publish, archive, duplicate, schedule, etc.
  - `CategoryController` – CRUD + order management
  - `MediaController` – upload, index, show, update, destroy

#### 5.3. JSON:API Resources
Located in `src/Http/Resources/`:
- Resources follow JSON:API-style structure
- Used to transform models for API responses
- Includes relationships and metadata

---

### 6. Configuration System

#### 6.1. Config File
`config/blog.php` includes:
- **Driver**: `db` or `sanity`
- **Reading time**: WPM setting
- **Author**: User model class path
- **Models**: Overridable model classes
- **Media**: Disk, directory, max size settings
- **Cache**: Enabled flag, TTL, prefix

#### 6.2. Environment Variables
Version 1 uses these `.env` variables:
- `BLOG_ENABLED` – enable/disable package
- `BLOG_DRIVER` – storage driver
- `BLOG_READING_TIME_WPM` – reading time calculation
- `BLOG_USER_MODEL` – author user model
- `BLOG_MEDIA_*` – media configuration
- `BLOG_CACHE_*` – cache configuration
- `SANITY_*` – Sanity CMS credentials (if using Sanity driver)

---

### 7. Post Management Features

Version 1 implements these post management operations:

#### 7.1. Status Management
- **Draft**: Created with `status = 'draft'`, `published_at = null`
- **Published**: `status = 'published'`, `published_at` set
- **Archived**: `status = 'archived'`, `archived_at` set
- **Scheduled**: `status = 'scheduled'`, `published_at` in future

#### 7.2. Post Actions
Implemented in `EloquentPostRepository`:
- `createDraft()` – creates post as draft
- `publish()` – publishes post, sets `published_at`
- `unpublish()` – reverts to draft
- `toggleStatus()` – switches between draft/published
- `schedule()` – schedules for future publication
- `duplicate()` – creates copy with new slug
- `archive()` – archives post
- `restore()` – restores from archive

#### 7.3. Query Helpers
- `getPublished()` – only published posts
- `getDrafts()` – only drafts
- `getScheduled()` – only scheduled posts
- `getArchived()` – only archived posts

---

### 8. Category Ordering System

Version 1 implements manual category ordering:

#### 8.1. Order Field
- `categories` table includes `order` integer field
- Categories can be manually ordered

#### 8.2. Order Management Methods
In `EloquentCategoryRepository`:
- `allOrdered()` – returns categories sorted by `order`
- `moveUp()` – decreases order (moves up)
- `moveDown()` – increases order (moves down)
- `setOrder()` – sets specific order value

---

### 9. Tag System

Version 1 includes tag features:

#### 9.1. Tag Operations
- Many-to-many relationship with posts via `post_tag` pivot table
- Auto-complete search via `search()` method
- Popular tags via `getPopular()` method (based on post count)

#### 9.2. Tag Endpoints
- `GET /api/blog/tags` – list/search tags
- `GET /api/blog/tags/popular` – popular tags
- `GET /api/blog/tags/{tag}/posts` – posts by tag

---

### 10. Media Library

#### 10.1. Media Model
- `Media` model stores file metadata
- Uses Laravel `Storage` facade
- Configurable disk (local, s3, etc.)

#### 10.2. Media Operations
- Upload via `POST /api/blog/admin/media/upload`
- CRUD operations available
- Respects `BLOG_MEDIA_MAX_SIZE` config

---

### 11. Search and Filtering

Version 1 uses `spatie/laravel-query-builder` for filtering:

#### 11.1. Filtering
- Posts can be filtered by: `title`, `status`, `category_id`, `author_id`
- Uses query parameters: `filter[status]=published`

#### 11.2. Sorting
- Sortable fields: `published_at`, `title`, `created_at`
- Uses query parameters: `sort=-published_at` (descending)

#### 11.3. Pagination
- Default: 15 items per page
- Configurable via `per_page` query parameter

---

### 12. Testing Structure

Version 1 includes comprehensive tests in `tests/Feature/`:

#### 12.1. API Tests
- `Api/PostControllerTest.php` – public post endpoints
- `Api/CategoryControllerTest.php` – category endpoints
- `Api/TagControllerTest.php` – tag endpoints
- `Api/AuthorControllerTest.php` – author endpoints
- `Api/AdminPostControllerTest.php` – admin post CRUD
- `Api/AdminCategoryControllerTest.php` – admin category CRUD
- `Api/AdminMediaControllerTest.php` – media upload

#### 12.2. Feature Tests
- `PostManagementTest.php` – post lifecycle (draft, publish, archive, etc.)
- `CategorySystemTest.php` – category ordering
- `TagSystemTest.php` – tag operations
- `AuthorManagementTest.php` – author profiles
- `MediaLibraryTest.php` – media operations
- `SearchAndFilteringTest.php` – search and filtering
- `PerformanceTest.php` – caching and eager loading
- `DualStorageSystemTest.php` – driver switching

---

### 13. Key Design Decisions in Version 1

#### 13.1. Headless Architecture
- **Decision**: Package provides backend + API only, no UI
- **Rationale**: Allows host apps to build their own frontend/admin UI
- **Impact**: All functionality accessible via API or Facade

#### 13.2. Dual Storage Drivers
- **Decision**: Support both Eloquent (`db`) and Sanity CMS (`sanity`)
- **Rationale**: Flexibility for different use cases
- **Implementation**: Repository pattern allows swapping implementations
- **Limitation**: Sanity driver has limited write operations (mostly stubs)

#### 13.3. JSON:API Style Responses
- **Decision**: Use JSON:API-style resources for API responses
- **Rationale**: Consistent, standardized API structure
- **Implementation**: Laravel API Resources in `src/Http/Resources/`

#### 13.4. No Soft Deletes
- **Decision**: Use `archived_at` instead of Laravel soft deletes
- **Rationale**: More explicit control over archived state
- **Impact**: Posts can be archived and restored without using soft delete trait

#### 13.5. Auto-Loading Migrations
- **Decision**: Migrations auto-load by default
- **Rationale**: Easier installation, no manual publishing required
- **Flexibility**: Can still publish migrations for customization

#### 13.6. Rate Limiting
- **Decision**: Different limits for public (120/min) vs admin (60/min)
- **Rationale**: Protect admin endpoints, allow higher public traffic
- **Implementation**: Laravel throttle middleware

---

### 14. Extension Points in Version 1

Version 1 allows customization through:

#### 14.1. Model Overrides
- Models can be overridden in `config/blog.php`
- Host apps can extend models with additional functionality

#### 14.2. Repository Overrides
- Repository interfaces can be rebound in host app's service provider
- Allows custom data access logic

#### 14.3. Config Customization
- All settings configurable via `config/blog.php` or `.env`
- Reading time, media, cache all customizable

#### 14.4. Trait Usage
- `BlogAuthor` trait is optional for host apps
- Host apps can add trait to their `User` model for author features

---

### 15. Known Limitations in Version 1

#### 15.1. Sanity Driver
- Write operations (create, update, delete) throw `RuntimeException`
- Only read operations are fully implemented
- Sanity mutations would need to be implemented for full support

#### 15.2. No Events/Hooks
- Version 1 does not emit Laravel events (e.g., `PostPublished`, `PostArchived`)
- Host apps cannot easily hook into post lifecycle events

#### 15.3. No Service Layer
- Business logic is in repositories and controllers
- No dedicated service classes (except `PostService` which exists but may not be fully utilized)

#### 15.4. Limited Extensibility
- No plugin/hook system for extending functionality
- Extension requires overriding repositories/models

---

### 16. Code Organization

#### 16.1. Directory Structure
```
src/
├── Blog.php                    # Core class
├── BlogServiceProvider.php     # Service provider
├── Contracts/
│   └── Repositories/          # Repository interfaces
├── Facades/
│   └── Blog.php               # Facade
├── Http/
│   ├── Controllers/
│   │   └── Api/               # Public & Admin controllers
│   └── Resources/             # JSON:API resources
├── Models/                     # Eloquent models
├── Repositories/
│   ├── Eloquent/              # Eloquent implementations
│   └── Sanity/                # Sanity implementations
├── Services/                   # Service classes (minimal)
└── Traits/                     # Reusable traits
```

#### 16.2. Test Organization
```
tests/
├── Feature/
│   ├── Api/                   # API endpoint tests
│   └── *.php                  # Feature tests
└── TestCase.php               # Base test case
```

---

### 17. Dependencies

Version 1 requires:
- **Laravel** >= 10.0 or >= 11.0
- **PHP** >= 8.2
- **spatie/laravel-query-builder** – for filtering/sorting
- **Laravel Sanctum** – for admin API authentication (host app responsibility)

---

### 18. Summary for Version 2 Developers

**What Version 1 provides:**
- Complete headless blog engine with RESTful API
- Dual storage driver support (Eloquent + Sanity)
- Post management (draft, publish, archive, schedule, duplicate)
- Category ordering system
- Tag system with search and popular tags
- Media library
- Author profiles linked to host app's User model
- Comprehensive test coverage
- JSON:API-style responses
- Rate limiting and authentication

**Key architectural patterns:**
- Repository pattern for data access abstraction
- Facade pattern for convenient static access
- Service provider for package registration
- Trait-based shared behaviors
- Driver-based storage switching

**Areas that may need attention in Version 2:**
- Sanity driver write operations (currently stubbed)
- Event system for lifecycle hooks
- Service layer formalization
- Enhanced extensibility mechanisms
- Additional API endpoints (archives, feeds, sitemaps)
- Database connection configuration (currently handled via model extension)

**Related Documentation:**
- See [README.md](README.md) for installation and basic setup, including database connection configuration
- See [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) for detailed usage instructions and database connection setup

This documentation should help you understand Version 1's implementation before planning Version 2 improvements.
