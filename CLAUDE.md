# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Symfony 5.4 application (Q&A platform) running in Docker. It features a REST API with versioning (FosRESTBundle), real-time push notifications via Mercure, 2FA (TOTP), async message processing via Symfony Messenger, CSV/PDF export, and Redis caching.

## Development Setup

All commands run inside Docker via `make`. The PHP container is named `symfony_5`.

```bash
make init          # Full setup: build images, run migrations, load fixtures, install assets
make up            # Start containers
make down          # Stop containers
make php           # Open shell in PHP container
```

## Common Commands

All Symfony/PHP commands run inside the container via make:

```bash
make sf c="<command>"          # Run any bin/console command
make cc                        # Clear cache
make composer c="<args>"       # Run composer
make db-migrate                # Run doctrine migrations
make db-init                   # Reset DB: clear cache, migrate, load fixtures
make cs-fix                    # Run php-cs-fixer
make yarn-dev                  # Build frontend assets
make yarn-watch                # Watch and rebuild assets
```

To run commands directly inside the container:
```bash
docker exec symfony_5 php bin/console <command>
docker exec symfony_5 ./vendor/bin/phpstan analyse
docker exec symfony_5 ./vendor/bin/php-cs-fixer fix -v
```

## Testing

```bash
# Run all tests (inside container)
docker exec symfony_5 php bin/phpunit

# Run a single test file
docker exec symfony_5 php bin/phpunit tests/path/to/TestFile.php

# Run a specific test method
docker exec symfony_5 php bin/phpunit --filter testMethodName tests/path/to/TestFile.php
```

Tests use `zenstruck/foundry` for factories and `doctrine/doctrine-fixtures-bundle` for fixtures.

## Linting

`linter.sh` runs on changed files (git diff): PHP syntax check → php-cs-fixer → PHPStan → Symfony container lint → twig lint → ESLint.

PHPStan is configured at level 7 (`phpstan.neon`) using the Symfony container XML for service type inference. The `phpstan.dist.neon` runs at level 6 without Symfony-specific analysis.

## Architecture

### API Layer (`src/Controller/API/`)
- `BaseApiController` — shared API controller base
- `V1/` — versioned REST endpoints (Questions, Spells, Users)
- `V2/` — v2 endpoints (Spells only currently)
- API uses FosRESTBundle with serializer groups for response shaping

### Security (`src/Security/`)
- `LoginFormAuthenticator` — standard form login
- `ApiTokenAuthenticator` — Bearer token auth for API (`ApiToken` entity)
- `Voter/` — `QuestionVoter` (edit/delete ownership), `EmailVerifiedVoter`
- 2FA via `scheb/2fa-bundle` with TOTP

### Entities (`src/Entity/`)
- `Question` — core entity, uses Gedmo Timestampable, optimistic locking, owned by `User`
- `User` — has `ApiToken` relationship, email verification, 2FA secret
- `Export` / `ExportStatus` — track async export jobs
- `Spell` — secondary content entity

### Async Processing (`src/Messenger/`)
- `Message/QuestionExport.php` — message dispatched to trigger CSV export
- `MessageHandler/Exporter/` — handles export, writes to cache, updates `ExportStatus`

### Export System (`src/Exporter/`)
- `QuestionExporter` — builds CSV export
- `QuestionExportCache` — Redis-backed cache for export results
- `QuestionExportLimiter` — rate limiting via Symfony Rate Limiter

### Custom Doctrine Types (`src/DoctrineExtensions/DBAL/Types/`)
- `UTCDateTimeType` — stores all datetimes in UTC

### Other Notable Services
- `src/Service/DateTimeService.php` — timezone-aware date handling (timezone saved in cookie)
- `src/EventSubscriber/` — `CheckBlockedUserSubscriber`, `KernelRequestSubscriber` (timezone from cookie), `UserSubscriber`
- `src/Twig/` — custom Twig extensions

## Infrastructure

Docker services: PHP/Apache (`symfony_5`), MySQL 5.6 (`database_symfony_5`), Mercure (`mercure_symfo_5`), Redis 7 (`redis_symfo_5`).

The app code is volume-mounted at `/srv/app` inside the container. HTTPS is served on localhost with a self-signed cert.

## Frontend Assets

After modifying any CSS or JS file in `assets/`, always:
1. Run `make yarn-dev` (or `make yarn-watch`) to recompile
2. **Hard-refresh the browser** (Ctrl+Shift+R) or clear the browser cache before verifying changes — the browser may serve the old compiled file from cache, making it appear the fix had no effect.

## Key Conventions

- Doctrine annotations (not attributes) are used for ORM mapping
- Translations are in `translations/` — validation messages use translation keys (e.g. `"question.title.not_blank"`)
- API serialization uses `symfony/serializer` with groups defined on entities
- PHPStan requires the Symfony container XML (`var/cache/dev/App_KernelDevDebugContainer.xml`) — run `make cc` before PHPStan if cache is missing