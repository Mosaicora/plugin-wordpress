# Contributing

Thank you for helping improve Mosaicora for WordPress.

## Before you begin

- Search existing issues before opening a new report.
- Use GitHub private vulnerability reporting for security concerns.
- Keep changes focused and backward compatible.
- Add regression coverage for every meaningful behavior change.

## Local development

Requirements:

- PHP 8.1 or newer with Composer
- Node.js 22 and pnpm
- Docker for the WordPress browser environment

Install dependencies and run the validation suite:

```bash
composer install
composer validate --strict
composer audit
composer test
composer lint
composer phpcs
composer release:check
pnpm install --frozen-lockfile
pnpm exec playwright install chromium
pnpm wp:start
pnpm test:e2e
pnpm wp:stop
```

## Pull requests

- Explain the user-facing problem and the chosen solution.
- Document compatibility or privacy implications.
- Update `CHANGELOG.md` for release-worthy changes.
- Keep the plugin header, package version, stable tag, and changelog aligned when
  preparing a release.
- Confirm the release ZIP contains no credentials, test dependencies, or local
  paths.

By contributing, you agree that your contribution is licensed under the
project's Apache License 2.0.
