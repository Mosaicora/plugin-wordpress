# Mosaicora for WordPress

[![Continuous integration](https://github.com/Mosaicora/plugin-wordpress/actions/workflows/ci.yml/badge.svg)](https://github.com/Mosaicora/plugin-wordpress/actions/workflows/ci.yml)
[![Latest release](https://img.shields.io/github/v/release/Mosaicora/plugin-wordpress)](https://github.com/Mosaicora/plugin-wordpress/releases/latest)
[![License](https://img.shields.io/github/license/Mosaicora/plugin-wordpress)](LICENSE)

Mosaicora automatically publishes deterministic Open Graph and X image metadata
for indexable WordPress pages. Administrators connect a site with its public
Mosaicora site ID; no API token is stored.

## Features

- Adds complete 1200 × 630 JPEG Open Graph and X image metadata.
- Supports homepages, singular public content, pagination, public archives, and
  public taxonomies while excluding non-indexable requests.
- Provides monthly, weekly, stable, manual, and page-specific cache revisions.
- Adds guided, typed Mosaicora v3 overrides to block and classic editors.
- Preserves per-site settings in Multisite installations.
- Uses no database tables, cron jobs, tracking, or routine server-to-server
  requests.

## Requirements

- WordPress 7.0 or newer
- PHP 8.1 or newer
- A Mosaicora site ID

## Installation

Download `mosaicora-<version>.zip` from the
[latest GitHub release](https://github.com/Mosaicora/plugin-wordpress/releases/latest).
In WordPress, open **Plugins → Add New Plugin → Upload Plugin**, select the ZIP,
and activate Mosaicora. Then open **Settings → Mosaicora** to enter the site ID
and enable the integration.

The plugin is prepared for the WordPress.org directory but is not listed there
yet. WordPress.org deployment will begin after the directory application is
approved.

## Configuration

Global settings control whether Mosaicora renders metadata and how often image
URLs rotate. Editable public posts and pages also receive a Mosaicora editor
panel for an optional template, cache revision, typed semantic values, or a
page-level disable setting.

If another SEO plugin publishes Open Graph tags, disable its social-image output
to avoid duplicate metadata. Mosaicora displays an administrative warning when
it detects a commonly used SEO plugin.

## Development

```bash
composer install
composer validate --strict
composer audit
composer test
composer lint
composer phpcs
pnpm install --frozen-lockfile
pnpm test:e2e
```

The browser suite uses Docker, `wp-env`, WordPress 7.0.2, and PHP 8.1.

## Build a release

Install runtime dependencies and create the WordPress.org-ready ZIP:

```bash
composer install --no-dev --classmap-authoritative
composer package
composer release:check
```

The archive is written to `build/mosaicora-<version>.zip` with one `mosaicora/`
root. It contains only runtime plugin files, the Composer autoloader, Mosaicora
PHP core source, licenses, notices, translations, and user documentation.

## Developer filters

- `mosaicora_og_should_render(bool $render, int $post_id)`
- `mosaicora_og_page_url(string $url)`
- `mosaicora_og_image_url_options(OgImageUrlOptions $options, int $post_id)`
- `mosaicora_og_image_alt(string $alt, int $post_id, string $page_url)`
- `mosaicora_og_override(array $override, int $post_id, string $page_url)`

Return the documented type from each filter. Invalid page URLs or image option
objects stop metadata rendering safely.

## Data and privacy

Settings and page overrides stay in the WordPress database. The plugin does not
send API tokens, run background synchronization, or add tracking. Social
platforms and browsers request generated images from `cdn.mosaicora.io`; the
request contains the public Mosaicora site ID and public page URL in the image
path. The settings preview makes the same request when an administrator views
it.

See the [Mosaicora privacy policy](https://mosaicora.io/privacy) and
[terms](https://mosaicora.io/terms) for service details.

## Support and security

Use [GitHub Issues](https://github.com/Mosaicora/plugin-wordpress/issues) for
reproducible bugs and feature requests. Please report security vulnerabilities
privately as described in [SECURITY.md](SECURITY.md).

## Contributing

Contributions are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before
opening a pull request and follow the [Code of Conduct](CODE_OF_CONDUCT.md).

Mosaicora for WordPress is licensed under GPL-3.0-or-later. Bundled dependency
licenses are documented in [third-party-notices.txt](third-party-notices.txt).
