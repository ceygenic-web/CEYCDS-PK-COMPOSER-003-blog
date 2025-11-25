# Installation AND Setup

## Step 1: Generate GitHub Personal Access Token *(one-time per machine)*

1. Visit [GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)](https://github.com/settings/tokens).
2. Click **Generate new token (classic)**.
3. Name it (e.g., `Laravel Package Access`).
4. Select the `repo` scope (full control of private repositories).
5. Generate and copy the token immediately—you will not see it again.

## Step 2: Configure Composer Authentication *(one-time per machine)*

Tell Composer to use the token:

```bash
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
```

Replace `YOUR_GITHUB_TOKEN` with the token from Step 1. After this, Composer can access all private GitHub packages for this machine.

## Step 3: Add Repository to `composer.json`

In your application's `composer.json`, add the private repository entry:

```json
"repositories": {
    "repository-name": {
        "type": "vcs",
        "url": "https://github.com/ceygenic-web/CEYCDS-PK-COMPOSER-003-blog.git"
    }
}
```

> If a `repositories` block already exists, append the entry instead of replacing the block.

## Step 4: Install the Package

Run Composer with the package name (and version/constraint you need):

```bash
composer require ceygenic/blog-core
```

Accepted values include tags (e.g., `1.0.0`), branches (e.g., `dev-main`), or constraints (e.g., `^1.0`). Ensure the value matches what is defined in the package’s `composer.json`.

## Step 5: Inspect the Installed Package (optional)

```bash
composer show ceygenic/blog-core
```

This confirms the installed version and available metadata.

