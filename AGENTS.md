# Colorado Sun WordPress Platform (colorado-sun-wp)

## Overview
The Colorado Sun's WordPress site platform. PHP theme/plugin code with a modern build toolchain (Composer + npm).

## Tech Stack
- WordPress (PHP)
- Composer for PHP deps, npm for JS/CSS build
- phpcs.xml for PHP code style (PHP CodeSniffer)

## Commands (from package.json)
- `npm run setup` — initial setup
- `npm run start` — dev server
- `npm run build` — build assets
- `npm run format` — format code
- `npm run lint` — lint (JS/CSS)
- `npm run lint:js`, `npm run lint:css`

## Key directories
- `inc/` — PHP includes
- `src/` — source assets (JS/CSS)
- `docs/` — documentation
- `plugin.php` — plugin entry
- `composer.json` — PHP dependencies

## Conventions
- `main` protected; `feature/<name>` branches, squash-merge PRs.
- Conventional commits.
- PHP linting via `composer lint` / phpcs; JS/CSS via npm lint scripts.

## Docs
- `README.md`, `ROADMAP.md` — project overview and roadmap.
