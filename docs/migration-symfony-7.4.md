# Symfony 5.4 → 7.4 LTS Migration Plan

## Context

The project is a Symfony 5.4 Q&A platform running PHP 7.4 (EOL since Nov 2022). Symfony 5.4 reaches end-of-life in November 2025. Symfony 7.4 is the current LTS target. The migration touches the runtime (PHP), the framework core, all third-party bundles, the ORM annotation system, security patterns, and the test infrastructure.

---

## Should you migrate in one step or iteratively?

**Recommended: 3 iterations.** Going directly 5.4 → 7.4 means absorbing two generations of breaking changes at once. When tests fail, it will be nearly impossible to tell whether a failure comes from PHP 8.x changes, Symfony 6.x changes, or 7.x changes. The iterative path gives you a green test suite at each checkpoint and a clear regression budget.

```
Iteration 1 — Deprecation clean-up on 5.4   (no framework upgrade)
Iteration 2 — Upgrade to Symfony 6.4 LTS    (PHP 8.1)
Iteration 3 — Upgrade to Symfony 7.4 LTS    (PHP 8.2)
```

Each iteration ends with all tests passing and PHPStan at level 7 clean.

---

## Phase 0 — Before touching the framework

### 0.1 Add a targeted test safety net

The project currently has 15 test files. **No web controller has any functional test coverage.** Add the following tests before starting Iteration 1 — they act as the regression net for the entire migration.

**Scope:** one or two representative scenarios per area — one happy path, one access-control check. Do not aim for full coverage; aim for a wire that trips if something breaks.

#### Web controllers (zero coverage today)

| Test | Scenarios | Why |
|---|---|---|
| `QuestionControllerTest` | Homepage (public), show (public), create (verified user), edit (owner vs non-owner via voter) | Most complex controller — multiple auth patterns, `QuestionVoter`, optimistic locking |
| `RegistrationControllerTest` | Register form submit, email verification link | Multi-step flow with password hashing, auto-login, SymfonyCasts verify-email — all change during migration |
| `ApiTokenControllerTest` (web) | List tokens (auth required), create token, delete with CSRF | CSRF validation is easy to silently break |
| `SecurityControllerTest` | Login page renders, 2FA enable flow | 2FA bundle is being upgraded two major versions |

#### API controllers (partially covered — gaps remain)

| Test | Scenarios | Why |
|---|---|---|
| `API/V1/QuestionControllerTest` | GET list, GET single, POST create, PUT update | Core API — only token controller is tested today |
| `API/V1/SpellControllerTest` | GET list, GET single | Same gap |
| `API/V1/UserControllerTest` | GET current user | Same gap |

#### Security & async (no coverage)

| Test | Type | Why |
|---|---|---|
| `QuestionVoterTest` | Unit | A broken voter silently lets the wrong user in |
| `EmailVerifiedVoterTest` | Unit | Same risk — silent security regression |
| `QuestionExportHandlerTest` | Integration | Async handler fails silently; one happy-path dispatch is enough |
| `CheckBlockedUserSubscriberTest` | Functional | Security-critical subscriber with zero coverage |

### 0.2 Use Rector for automated code transforms

Install Rector as a dev dependency:

```bash
composer require --dev rector/rector
```

Create `rector.php` at the project root. Rector will handle the bulk of the mechanical transforms in each iteration, run it **first** before any manual steps:

```php
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\SensioSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSets([
        // Iteration 1 — annotations → attributes, 5.4 deprecations
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensioSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_60,

        // Iteration 2 — Symfony 6.x + PHP 8.1
        SymfonySetList::SYMFONY_61,
        SymfonySetList::SYMFONY_62,
        SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_64,
        LevelSetList::UP_TO_PHP_81,
        PHPUnitSetList::PHPUNIT_100,

        // Iteration 3 — Symfony 7.x + PHP 8.2
        SymfonySetList::SYMFONY_70,
        SymfonySetList::SYMFONY_71,
        SymfonySetList::SYMFONY_72,
        SymfonySetList::SYMFONY_73,
        SymfonySetList::SYMFONY_74,
        LevelSetList::UP_TO_PHP_82,
    ]);
```

Apply the relevant sets per iteration (comment out future sets until you reach that iteration). Always run Rector in dry-run first:

```bash
docker exec symfony_5 ./vendor/bin/rector process --dry-run
docker exec symfony_5 ./vendor/bin/rector process
```

**What Rector automates (~70–80% of code changes):**
- All `@Route`, `@IsGranted`, `@ORM\*`, `@Gedmo\*` annotations → PHP 8 attributes
- Deprecated `getUsername()` / `getSalt()` removal from `User`
- PHP 7.4 → 8.x syntax upgrades
- PHPUnit 9 → 10 API changes

**What Rector cannot touch (manual steps remain):**
- YAML config files (`security.yaml`, `scheb_2fa.yaml`, `messenger.yaml`, etc.)
- `composer.json` version constraints
- `docker-compose.yml` / `Dockerfile`
- Third-party bundle config restructuring (scheb, FOSRest, NelmioApiDoc key renames)
- Zenstruck Foundry v1 → v2 (no Rector ruleset exists for this)

---

## Iteration 1 — Fix all Symfony 5.4 deprecations (stay on 5.4)

Goal: eliminate every deprecation so that upgrading to 6.0 is a no-op.

**Workflow for this iteration:**
1. Run Rector (sets: `SYMFONY_54`, `SYMFONY_60`, `ANNOTATIONS_TO_ATTRIBUTES` for Symfony/Sensio/Doctrine)
2. Manual fixes for what Rector cannot reach
3. PHPStan + tests green checkpoint

### 1.1 Enable deprecation reporting

Add `SYMFONY_DEPRECATIONS_HELPER=weak` to `.env.test` and run the full test suite. Every deprecation notice becomes a visible log entry. Fix them in order of frequency.

### 1.2 Replace SensioFrameworkExtraBundle annotations — via Rector

`sensio/framework-extra-bundle` is dropped entirely in Symfony 7. **Remove it now.**

- **Run Rector** — `SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES` + `SensioSetList::ANNOTATIONS_TO_ATTRIBUTES` converts all `@Route` (34 occurrences) and `@IsGranted` (17 occurrences) automatically
- **Manual:** Remove `sensio/framework-extra-bundle` from `composer.json` and `config/bundles.php`
- **Manual:** Remove `config/packages/sensio_framework_extra.yaml`

### 1.3 Remove symfony/proxy-manager-bridge

`symfony/proxy-manager-bridge` (wrapping `ocramius/proxy-manager`) is removed in Symfony 6.4. Symfony 5.4 ships its own lazy-proxy implementation.

- Remove `symfony/proxy-manager-bridge` from `composer.json`
- If any service is tagged `lazy: true`, verify it still works with Symfony's built-in proxy (it should)

### 1.4 Migrate Doctrine annotations → PHP 8 attributes — via Rector

- **Run Rector** — `DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES` converts all `@ORM\*` (100+ occurrences in `src/Entity/`) and `@Gedmo\*` annotations automatically
- **Manual:** Remove `doctrine/annotations` from `composer.json` once all entity files are converted
- **Manual:** Remove `phpdocumentor/reflection-docblock` if it was an annotations-only dependency

### 1.5 Fix Kernel routing — manual

`RouteCollectionBuilder` is deprecated since Symfony 5.1.

- **File:** `src/Kernel.php`
- Change method signature from `configureRoutes(RouteCollectionBuilder $routes)` to `configureRoutes(RoutingConfigurator $routes)` and update the import call style accordingly.

### 1.6 Clean up User entity — via Rector

- **Run Rector** — `SymfonySetList::SYMFONY_60` removes `getUsername()` and `getSalt()` from `User`
- **Manual verify:** `getUserIdentifier()` and `eraseCredentials()` are still present and correctly typed
- **File:** `src/Entity/User.php`

### 1.7 Validate with PHPStan + CS-fixer

Run PHPStan level 7 and php-cs-fixer after each sub-step to catch regressions early.

---

## Iteration 2 — Upgrade to Symfony 6.4 LTS

**Workflow for this iteration:**
1. Run Rector (sets: `SYMFONY_61` through `SYMFONY_64`, `UP_TO_PHP_81`, `PHPUNIT_100`)
2. Upgrade `composer.json` constraints and run `composer update`
3. Manual config and bundle fixes
4. PHPStan + tests green checkpoint

### 2.1 Upgrade the PHP runtime

- **Dockerfile** (`docker/php/Dockerfile`): change `ARG PHP_VERSION=7.4` → `PHP_VERSION=8.1`
- **composer.json**: change `"php": "^7.4.0"` → `"php": "^8.1"`
- Install PHP 8.1 extensions that differ from 7.4 (fibers, readonly properties — note: constructor property promotion is available but not required)
- Also upgrade **MySQL 5.6 → 8.0+** in `docker-compose.yml` (5.6 is deeply EOL and Doctrine DBAL 3.x drops MySQL 5.6 support)

### 2.2 Upgrade all Symfony packages to 6.4.*

In `composer.json`, change every `5.4.*` constraint to `6.4.*`:

```
symfony/asset, symfony/cache, symfony/console, symfony/debug-bundle,
symfony/doctrine-messenger, symfony/dotenv, symfony/form,
symfony/framework-bundle, symfony/mailer, symfony/messenger,
symfony/property-access, symfony/property-info, symfony/rate-limiter,
symfony/routing, symfony/security-bundle, symfony/serializer,
symfony/translation, symfony/twig-bundle, symfony/validator,
symfony/var-dumper, symfony/yaml
```

Also update dev packages:
- `symfony/browser-kit`, `symfony/css-selector`, `symfony/stopwatch`, `symfony/web-profiler-bundle` → `6.4.*`
- `symfony/phpunit-bridge` → `^7.0` (bridges can track ahead of the framework)
- `symfony/flex` → `^2.0` (v1 doesn't support Symfony 6+)
- `symfony/maker-bundle` → latest `^1.x` (supports 6.4)

### 2.3 Upgrade third-party bundles — Symfony 6.4 compatible versions

| Package | Current | Target | Notes |
|---|---|---|---|
| `friendsofsymfony/rest-bundle` | ^3.4 | ^3.8 or ^4.x | Check if 3.x supports Sf 6.4 |
| `scheb/2fa-bundle` + `scheb/2fa-totp` | ^5.13 | ^6.x | Major version bump; config keys changed |
| `nelmio/api-doc-bundle` | ^4.11 | ^4.x or ^5.x | v4.x supports Sf 6 with some config tweaks |
| `snc/redis-bundle` | ^4.6 | ^4.x | Likely compatible with Sf 6 |
| `stof/doctrine-extensions-bundle` | ^1.7 | ^1.12+ | Needs Gedmo ^3.x which supports PHP 8 attributes |
| `endroid/qr-code-bundle` | ^4.1 | ^5.x | v5 drops Sf5 support |
| `knplabs/knp-snappy-bundle` | ^1.9 | ^1.10 | Verify Sf6 compatibility |
| `knplabs/knp-time-bundle` | ^1.19 | ^2.x | v2 drops Sf5 support |
| `sentry/sentry-symfony` | ^4.3 | ^5.x | v5 adds Sf6 support |
| `friendsofsymfony/jsrouting-bundle` | ^2.8 | ^3.x | v3 supports Sf6 |
| `willdurand/js-translation-bundle` | ^8.0 | ^8.x | Verify |
| `symfony/ux-chartjs` | ^2.4 | ^2.x | Likely compatible |
| `symfonycasts/verify-email-bundle` | ^1.11 | ^1.x | Usually keeps up |
| `zenstruck/foundry` | ^1.21 | **^2.0** | **Major API rewrite** — see below |
| `phpunit/phpunit` | ^9.5 | **^10.5** | Required for PHP 8.1+ |

### 2.4 Zenstruck Foundry v1 → v2 (test-only, but significant)

Foundry 2.x has a **completely rewritten API**. All factory files and test classes using factories will need updates:

- `Factories` trait → `ZenstruckFoundryTestCase` or static `::createOne()` syntax changes
- `ModelFactory` → `PersistentProxyObjectFactory` or `ObjectFactory`
- Story classes changed
- Affected files: `src/Factory/UserFactory.php`, `QuestionFactory.php`, `SpellFactory.php`, `ExportFactory.php` and all test files in `tests/`

Plan 3–4 days for Foundry migration alone.

### 2.5 Update security.yaml

- Remove `enable_authenticator_manager: true` — it is the default in Symfony 6.0+ and the key is gone
- `scheb/2fa-bundle` v6 changes config key names — review `config/packages/scheb_2fa.yaml`

### 2.6 Update Doctrine ORM

- `doctrine/orm` → `^2.17` (minimum for Symfony 6.4 compatibility)
- `doctrine/doctrine-bundle` → `^2.11+`
- `doctrine/doctrine-migrations-bundle` → `^3.3+`
- `doctrine/dbal` → `^3.8` (DBAL 4.x drops MySQL 5.6, but you'll be on 8.0 by now)

### 2.7 PHPStan level 7 re-verification

After all package upgrades, re-run PHPStan and the full test suite before moving to Iteration 3.

---

## Iteration 3 — Upgrade to Symfony 7.4 LTS

**Workflow for this iteration:**
1. Run Rector (sets: `SYMFONY_70` through `SYMFONY_74`, `UP_TO_PHP_82`)
2. Upgrade `composer.json` constraints and run `composer update`
3. Manual config and bundle fixes
4. PHPStan + tests green checkpoint

### 3.1 Upgrade PHP to 8.2

- **Dockerfile**: `PHP_VERSION=8.2` (or 8.3, which is currently the best choice for longevity)
- **composer.json**: `"php": "^8.2"`

### 3.2 Upgrade all Symfony packages to 7.4.*

Same list as step 2.2, change constraints to `7.4.*`.

Dev packages:
- `symfony/phpunit-bridge` → `^7.4`
- `symfony/maker-bundle` → latest `^1.x`

### 3.3 Upgrade third-party bundles — Symfony 7.4 compatible versions

| Package | Notes |
|---|---|
| `friendsofsymfony/rest-bundle` | Verify v3.x or v4.x has Sf7 support; if not, evaluate replacing with native Symfony API platform or plain controllers |
| `scheb/2fa-bundle` | → `^7.x` (matches Symfony major version) |
| `nelmio/api-doc-bundle` | → `^5.x` (v5 is the Sf7-compatible release) |
| `knplabs/knp-time-bundle` | → `^2.x` |
| `sentry/sentry-symfony` | → `^5.x` |
| `friendsofsymfony/jsrouting-bundle` | → `^3.x` |
| `snc/redis-bundle` | Verify Sf7 support in `^4.x` |

### 3.4 Remove remaining legacy packages

These packages have no place in Symfony 7:
- `symfony/proxy-manager-bridge` — already removed in Iteration 1
- `sensio/framework-extra-bundle` — already removed in Iteration 1
- `doctrine/annotations` — already removed in Iteration 1

### 3.5 PHP 8.2-specific adjustments

- Dynamic property creation is now an error by default — audit any classes that set `$object->undeclaredProp = ...`
- Deprecated `utf8_encode()` / `utf8_decode()` — replace with `mb_convert_encoding()`
- Review any `is_callable()` or `call_user_func()` patterns

### 3.6 Final PHPStan + test suite pass

Full suite must be green. PHPStan must pass at level 7 with no deprecation exceptions.

---

## Critical Files Impacted

| Area | Files |
|---|---|
| Runtime | `docker/php/Dockerfile`, `docker-compose.yml`, `composer.json` |
| Kernel routing | `src/Kernel.php` |
| All entities | `src/Entity/User.php`, `Question.php`, `Spell.php`, `ApiToken.php`, `Export.php`, `ExportStatus.php` |
| All controllers | `src/Controller/*.php` (9 files), `src/Controller/API/V1/*.php` (4 files), `src/Controller/API/V2/*.php` (1 file) |
| Security | `src/Entity/User.php` (remove deprecated methods) |
| Security config | `config/packages/security.yaml` |
| 2FA config | `config/packages/scheb_2fa.yaml` |
| Bundle registry | `config/bundles.php` |
| Routing config | `config/routes/annotations.yaml` (will become `attributes.yaml`) |
| Test factories | `src/Factory/*.php` (4 files) |
| All tests | `tests/**/*.php` (15 files) |

---

## Packages to Remove

- `sensio/framework-extra-bundle`
- `symfony/proxy-manager-bridge`
- `doctrine/annotations`
- `phpdocumentor/reflection-docblock` (if annotations-only dependency)

## Packages to Replace or Evaluate

- `friendsofsymfony/rest-bundle` — if no Symfony 7 release exists, consider migrating API controllers to Symfony's native `#[MapRequestPayload]` + `#[MapQueryString]` + standard controllers (significant refactor but removes a heavy dependency)
- `willdurand/js-translation-bundle` — check active maintenance status

---

## Verification (end of each iteration)

```bash
docker exec symfony_5 php bin/phpunit                          # full test suite green
docker exec symfony_5 ./vendor/bin/phpstan analyse             # level 7 clean
docker exec symfony_5 ./vendor/bin/php-cs-fixer fix --dry-run # no violations
docker exec symfony_5 php bin/console debug:container          # all services resolve
docker exec symfony_5 php bin/console debug:router             # all routes registered
docker exec symfony_5 php bin/console doctrine:schema:validate # schema valid
```

Manual smoke tests:
- Login + 2FA flow
- API token authentication (Bearer token)
- Question creation, edit, delete (ownership voter)
- CSV/PDF export via async Messenger
- Mercure real-time push
- API documentation page (`/api/doc`)
- Admin dashboard