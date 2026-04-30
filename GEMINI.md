# Project Context: Symfony 5.4 Q&A Platform

This file provides foundational mandates and context for the Gemini CLI when working in this repository.

## Project Overview
This is a Symfony 5.4 application running in Docker.
- **PHP Version:** 7.4 (No constructor property promotion or PHP 8 attributes).
- **Architecture:** REST API (FosRESTBundle), Mercure for real-time, Redis for caching, Messenger for async tasks.
- **Database:** MySQL 5.6.

## Foundational Mandates
- **Always use traditional constructor syntax** and **Doctrine annotations** (not PHP 8 attributes).
- **Always run commands via `make` or `docker exec`** to ensure they execute within the `symfony_5` container.
- **Prioritize the use of existing services** found in `src/Service/`, `src/Repository/`, and `src/Exporter/`.
- **Validation messages** must use translation keys from `translations/`.
- **Frontend changes** require `make yarn-dev` and a hard browser refresh.

## Development & Testing Workflow
- **Standard Commands:**
  - `make sf c="<command>"`: Run Symfony console commands.
  - `make cc`: Clear cache.
  - `make db-migrate`: Run migrations.
  - `make cs-fix`: Run php-cs-fixer.
- **Testing:**
  - `docker exec symfony_5 php bin/phpunit`: Run all tests.
  - `docker exec symfony_5 php bin/phpunit tests/path/to/TestFile.php`: Run specific test file.
- **Linting:**
  - `linter.sh` runs on git diffs (PHP syntax -> php-cs-fixer -> PHPStan -> Symfony lint -> Twig lint -> ESLint).

## Code Style & Conventions
- Follow PSR-12 for PHP.
- Use `BaseApiController` for new API controllers.
- Use `zenstruck/foundry` for factories and `doctrine/doctrine-fixtures-bundle` for fixtures in tests.
- Date/Time handling should use `src/Service/DateTimeService.php` to remain timezone-aware.
- All datetimes in DB are stored in UTC via `UTCDateTimeType`.

## Key Paths
- **Controllers:** `src/Controller/`
- **Entities:** `src/Entity/`
- **Services:** `src/Service/`
- **Translations:** `translations/`
- **Tests:** `tests/`
