# Copilot instructions for Procurex

This file is for future Copilot sessions working on this repository. It contains concrete commands, a high-level architecture summary, and repository-specific conventions that are important for automated agents.

---

## Quick commands

- Setup (one-liner):
  - composer run setup
  - (or) run the steps: composer install; cp .env.example .env; php artisan key:generate; php artisan migrate; npm install; npm run build

- Dev (full):
  - composer run dev  # runs php artisan serve, queue listener and npm run dev concurrently

- Frontend:
  - npm run dev  # Vite dev server
  - npm run build

- Tests (Pest):
  - composer run test         # runs php artisan test
  - Run a single test by name: php artisan test --compact --filter=testName
  - Run a single file: php artisan test --compact tests/Feature/ExampleTest.php
  - Run with composer: composer run test

- PHPUnit config for CI / local runs: phpunit.xml config uses sqlite in-memory for tests (DB_CONNECTION=sqlite, DB_DATABASE=:memory:).

- Lint / format / static tools:
  - composer run format       # runs pint and rector
  - Recommended pre-commit: vendor/bin/pint --dirty --format agent

---

## High-level architecture (big picture)

- Laravel 12 backend (PHP 8.3). The app is a modular procurement workflow system (APP/PPMP → Emanating → PR → RFQ → AOQ → BAC → NOA → PO → PO Transmittal).
- Routes are defined in routes/*.php and primarily use resource controllers (controllers under App\Http\Controllers). Authorization is enforced via middleware and controller/policy logic (role middleware uses App\Enums\RoleType).
- Frontend is an Inertia + Vue 3 SPA: resources/js/app.js boots Inertia and resolves pages from resources/js/Pages/*.vue. Use Ziggy for route helpers.
- Assets built with Vite + TailwindCSS v4; shadcn-vue / reka-ui are used for component UI.
- PDF/DOCX/XLSX generation handled server-side (DomPDF, phpoffice, Spatie packages). Bulk imports (APP/PPMP/Emanatings) happen via controller import endpoints.
- Tests use Pest v4 and make use of factories, in-memory sqlite for CI, and browser tests (Pest browser-style examples exist in repo docs/skills).

---

## Key repository conventions (do not guess these)

- Inertia pages:
  - Put page components in resources/js/Pages. Pages must have a single root element.
  - Use @inertiajs/vue3 <Link> for client navigation and <Form> or useForm for forms to keep SPA behavior consistent.
  - Use deferred props with skeleton/fallback UI when appropriate.

- Tests:
  - Use Pest for all tests. Create tests with: php artisan make:test --pest NameTest
  - Prefer expressive assertions (assertSuccessful(), assertNotFound(), assertForbidden()) instead of raw status codes.
  - Use factories and RefreshDatabase/RefreshDatabase-like helpers. Browser tests live in tests/Browser/.
  - Run minimal tests with filter before pushing: php artisan test --compact --filter=...

- Formatting & static tools:
  - Run composer run format to run Pint + Rector. The repo expects Pint formatting; run vendor/bin/pint --dirty --format agent to fix diffs before committing.

- Routes & authorization:
  - Routes define resource controllers and role-based middleware. Authorization decisions live in controllers/policies; do not duplicate role checks in views.

- Seeds / Demo accounts:
  - README documents seeded demo accounts and the default password (`password`). Useful for local manual testing.

- CI / GitHub skills:
  - This repo already includes .github/skills for: pest-testing, inertia-vue-development, tailwindcss-development. Copilot sessions should activate/adhere to those skills when modifying tests, Inertia/Vue pages, or Tailwind styles.

---

## Other assistant configs found

- .github/skills/* (see above).
- No CLAUDE.md, AGENTS.md, .cursorrules, .windsurfrules, or AIDER_CONVENTIONS.md were found at scan time.

---

## Notes for Copilot agents

- Prefer small, surgical changes. Follow the project's Pint+Rector formatting and run tests (`php artisan test --compact --filter=...`) locally before suggesting code changes.
- Frontend changes may require `npm run build` or `npm run dev` to verify; if a UI change is not visible, ask the user to run the dev/build step.
- Tests run in-memory sqlite (see phpunit.xml). When adding DB-dependent tests, prefer factories and `RefreshDatabase` patterns.
- When working on Inertia/Vue, activate inertia-vue-development skill behavior: single-root components, use <Link>/<Form>, and prefer deferred props with loading states.

---

If you want, I can also add GitHub Actions or MCP server configs (Playwright/Playwright+Puppeteer) for browser tests — would you like an MCP server configured for browser testing or visual regression? 

