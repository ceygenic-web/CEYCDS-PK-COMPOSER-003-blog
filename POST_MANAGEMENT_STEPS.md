# Post Management Implementation Steps

## Overview
This document outlines the step-by-step implementation of Post Management features for the Blog Package.

## Step-by-Step Implementation Plan

### Step 1: Update Database Migration
**File:** `database/migrations/2024_01_01_000003_create_posts_table.php`

**Changes:**
- Add `author_id` column (nullable, foreign key to users table)
- Add `reading_time` column (integer, nullable)
- Ensure all required fields are present

**Fields Required:**
- ✅ title
- ✅ slug
- ✅ excerpt
- ✅ content
- ✅ featured_image
- ✅ category_id
- ✅ status (draft/published/archived)
- ✅ published_at
- ⚠️ author_id (to be added)
- ⚠️ reading_time (to be added)

---

### Step 2: Create HasSlug Trait
**File:** `src/Traits/HasSlug.php`

**Purpose:** Auto-generate slugs from title if not provided

**Features:**
- Generate slug from title using `Str::slug()`
- Ensure uniqueness by appending number if duplicate
- Handle slug updates when title changes
- Can be used by Post, Category, Tag models

**Methods:**
- `generateSlug(string $title, ?int $excludeId = null): string`
- `setSlugAttribute(?string $value): void` (mutator)

---

### Step 3: Create HasReadingTime Trait/Observer
**File:** `src/Traits/HasReadingTime.php` or `src/Observers/PostObserver.php`

**Purpose:** Auto-calculate reading time based on content length

**Features:**
- Calculate reading time from content (average reading speed: 200-250 words/minute)
- Update reading time when content changes
- Configurable words per minute (default: 200)

**Methods:**
- `calculateReadingTime(string $content, int $wordsPerMinute = 200): int`
- `setReadingTimeAttribute(): void` (mutator or observer)

---

### Step 4: Update Post Model
**File:** `src/Models/Post.php`

**Changes:**
- Add `author_id` to fillable
- Add `reading_time` to fillable
- Add `author_id` and `reading_time` to casts
- Use `HasSlug` trait
- Use `HasReadingTime` trait/observer
- Add `author()` relationship (BelongsTo User)
- Add business logic methods

**New Methods:**
- `author(): BelongsTo` - Relationship to User model
- `duplicate(): Post` - Duplicate a post
- `publish(): bool` - Publish the post
- `unpublish(): bool` - Unpublish the post
- `toggleStatus(): bool` - Toggle between draft/published
- `schedule(\DateTime $date): bool` - Schedule post for future publication
- `isScheduled(): bool` - Check if post is scheduled
- `isDraft(): bool` - Check if post is draft
- `isArchived(): bool` - Check if post is archived

---

### Step 5: Create PostService Class
**File:** `src/Services/PostService.php`

**Purpose:** Centralize business logic for post operations

**Methods:**
- `createDraft(array $data): Post` - Create draft post
- `publish(int $postId): Post` - Publish a post
- `unpublish(int $postId): Post` - Unpublish a post
- `toggleStatus(int $postId): Post` - Toggle post status
- `schedule(int $postId, \DateTime $date): Post` - Schedule post
- `duplicate(int $postId, ?string $newTitle = null): Post` - Duplicate post
- `archive(int $postId): Post` - Archive a post
- `restore(int $postId): Post` - Restore from archive

---

### Step 6: Update PostRepositoryInterface
**File:** `src/Contracts/Repositories/PostRepositoryInterface.php`

**New Methods:**
- `createDraft(array $data): Post`
- `publish(int $id): Post`
- `unpublish(int $id): Post`
- `toggleStatus(int $id): Post`
- `schedule(int $id, \DateTime $date): Post`
- `duplicate(int $id, ?string $newTitle = null): Post`
- `archive(int $id): Post`
- `getDrafts(): Collection`
- `getScheduled(): Collection`
- `getArchived(): Collection`

---

### Step 7: Update Repository Implementations
**Files:**
- `src/Repositories/Eloquent/EloquentPostRepository.php`
- `src/Repositories/Sanity/SanityPostRepository.php`

**Changes:**
- Implement all new interface methods
- Add business logic for each operation

---

### Step 8: Update Blog Facade/Service
**File:** `src/Blog.php`

**New Methods:**
- `createDraft(array $data): Post`
- `publishPost(int $id): Post`
- `unpublishPost(int $id): Post`
- `togglePostStatus(int $id): Post`
- `schedulePost(int $id, \DateTime $date): Post`
- `duplicatePost(int $id, ?string $newTitle = null): Post`
- `archivePost(int $id): Post`

---

### Step 9: Update Admin Controllers
**File:** `src/Http/Controllers/Api/Admin/PostController.php`

**New Endpoints:**
- `POST /api/blog/admin/posts/{id}/publish` - Publish post
- `POST /api/blog/admin/posts/{id}/unpublish` - Unpublish post
- `POST /api/blog/admin/posts/{id}/toggle-status` - Toggle status
- `POST /api/blog/admin/posts/{id}/schedule` - Schedule post
- `POST /api/blog/admin/posts/{id}/duplicate` - Duplicate post
- `POST /api/blog/admin/posts/{id}/archive` - Archive post

---

### Step 10: Update PostResource
**File:** `src/Http/Resources/PostResource.php`

**Changes:**
- Add `author` relationship
- Add `reading_time` attribute
- Include author data in response

---

### Step 11: Add Tests
**File:** `tests/Feature/PostManagementTest.php`

**Test Cases:**
- Test slug auto-generation
- Test reading time calculation
- Test draft creation
- Test publishing/unpublishing
- Test status toggle
- Test scheduling
- Test duplication
- Test archiving

---

## Implementation Order

1. ✅ **Step 1:** Update migration (add author_id, reading_time)
2. ✅ **Step 2:** Create HasSlug trait
3. ✅ **Step 3:** Create HasReadingTime trait
4. ✅ **Step 4:** Update Post model (add traits, relationships, methods)
5. ✅ **Step 5:** Create PostService (optional, can use model methods directly)
6. ✅ **Step 6:** Update PostRepositoryInterface
7. ✅ **Step 7:** Update repository implementations
8. ✅ **Step 8:** Update Blog facade
9. ✅ **Step 9:** Update Admin controllers
10. ✅ **Step 10:** Update PostResource
11. ✅ **Step 11:** Add tests

---

## Key Features to Implement

### 1. Slug Auto-Generation
- Generate from title automatically
- Ensure uniqueness
- Handle updates

### 2. Reading Time Calculation
- Calculate from content word count
- Default: 200 words/minute
- Update automatically

### 3. Draft Management
- Create drafts without publishing
- Save drafts
- Auto-save functionality (future)

### 4. Post Scheduling
- Set future published_at date
- Auto-publish when date arrives (requires scheduler)

### 5. Post Duplication
- Copy all post data
- Generate new slug
- Set status to draft
- Option to change title

### 6. Status Toggle
- Toggle between draft/published
- Handle published_at accordingly

---

## Notes

- **Author Relationship:** Uses Laravel's User model from host app (polymorphic or direct)
- **Reading Time:** Configurable via config file
- **Slug Generation:** Uses Laravel's Str::slug() helper
- **Scheduling:** Requires Laravel's task scheduler to be set up in host app
- **Validation:** All operations should validate data and permissions

