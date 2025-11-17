## Version 2 Developer Guide – `ceygenic/blog-core`

This document is for **future developers** who plan to build **Version 2** of this blog package.
Version 1 is stable and working; Version 2 should **improve and extend** it without breaking existing users unnecessarily.

---

### 1. Understand Version 1 First

Before changing anything, read:

- `README.md` – What the package does and how to install/use it.
- `DEVELOPER_GUIDE.md` – Detailed architecture and step-by-step usage.
- Key code locations:
  - `src/BlogServiceProvider.php` – registration, bindings, migrations, routes.
  - `src/Blog.php` – core class behind the facade.
  - `src/Contracts/Repositories/*` – repository interfaces.
  - `src/Repositories/Eloquent/*` – Eloquent implementations.
  - `src/Repositories/Sanity/*` – Sanity implementations.
  - `src/Http/Controllers/Api/*` – API controllers.
  - `src/Http/Resources/*` – JSON:API resources.
  - `src/Models/*` – Eloquent models.
  - `src/Traits/*` – shared behaviors (slugs, reading time, authors, cache).
  - `tests/Feature/*` – test coverage and behavior examples.

You should be able to:

- Install v1 into a fresh Laravel app.
- Call public and admin endpoints successfully.
- Switch between `db` and `sanity` drivers and see the effect.

Only after that start planning v2.


---

### 3. Versioning & Backward Compatibility

- Keep the v1 API behavior **as much as possible**, especially:
  - Public routes and their JSON structure.
  - Core facades and method names (`Blog::posts()`, `createDraft`, etc.).
  - Repository interfaces (add new methods carefully).
- If a breaking change is needed:
  - Document it clearly in `CHANGELOG.md` and a new `UPGRADING.md`.
  - Consider deprecating old behavior first where possible.
- Semantic Versioning:
  - v2 should be released as a **major version** on Packagist.

---

### 4. Architecture Improvements to Consider

#### 4.1. Core Domain vs HTTP Layer

Current v1 is already separated, but v2 can formalize:

- **Domain layer**:
  - Models, traits, services, repositories.
  - Should not know about HTTP specifics.
- **HTTP layer**:
  - Controllers and resources.
  - Should call domain services/repositories, not embed complex business logic.

Ideas:

- Introduce dedicated service classes (e.g. `PostService`, `MediaService`) and use them consistently.
- Keep controllers thin: validation + delegation to services.

#### 4.2. Repository Abstraction

The repository interfaces work well but can be refined:

- Avoid putting **too many responsibilities** in a single interface.
  - Consider separating read/write concerns or management actions if needed.
- Make sure methods are **driver-agnostic**:
  - Anything impossible to implement in Sanity should be documented or extracted.

#### 4.3. Sanity Driver Clarification

Right now:
- Eloquent driver (`db`) is fully featured.
- Sanity driver has read operations and throws for many write operations.


Be explicit in docs and types about which methods are supported per driver.

---

### 5. Extensibility & Customization

v1 already allows overriding models and repositories. v2 can:

- Provide clearer extension points:
  - Document exactly how to override repositories, controllers, resources.
  - Consider events/hooks (e.g. `PostPublished`, `PostArchived`) so host apps can hook into lifecycle.
- Consider adding:
  - Config flags to easily **disable** parts of the API (e.g. media, tags) if host app doesn’t need them.
  - Ability to change route prefixes and middleware more easily via config.


---

### 6. Performance & Caching in v2

v1 introduced:

- Simple repository-level caching using `HasCache`.
- Indexes on `status`, `published_at`, `category_id`, `author_id`, etc.


Always keep tests for caching behavior (like `PerformanceTest.php`) updated.

---

### 7. API Evolution

When adding or changing API endpoints in v2:

- Maintain **JSON:API-style** responses via resources.
- Avoid breaking existing endpoint URLs or structures:
  - If necessary, introduce **versioned API routes** (e.g. `/api/blog/v2/...`).
- Consider adding:
  - Additional filters/sorts.
  - New endpoints (e.g. archives, feeds, sitemaps) as separate concerns.

Always:

- Update `README.md` and `DEVELOPER_GUIDE.md`.
- Add or update tests in `tests/Feature/Api/*`.

---

### 8. Testing & Quality

v1 has a strong base of tests:

- `tests/Feature/Api/*` – API behavior.
- `tests/Feature/PostManagementTest.php` – business logic.
- `tests/Feature/CategorySystemTest.php`, `TagSystemTest.php`, `AuthorManagementTest.php`, `MediaLibraryTest.php`, `SearchAndFilteringTest.php`.
- `tests/Feature/PerformanceTest.php` – caching and eager loading.

---


### 11. Final Notes for Future You 👋

- Version 1 is a **solid base**: don’t overcomplicate v2.
- Keep the **headless** nature: engine + API, UI stays in host apps.
- Focus on:
  - Clarity (code and docs)
  - Extensibility
  - Predictable behavior




