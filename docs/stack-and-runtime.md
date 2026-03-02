# Stack and Runtime (AI-Oriented)

## 1) Technology Stack

### Backend

- **Framework:** Laravel 12 (`laravel/framework`)
- **Language:** PHP 8.2+
- **ORM:** Eloquent models in `app/Models`
- **Auth:** Laravel auth middleware + custom role middleware alias `role`
- **Excel import:** `maatwebsite/excel`
- **PDF generation:** `spatie/laravel-pdf` (plus dompdf packages present)

### Frontend

- **Renderer:** Inertia.js (`inertiajs/inertia-laravel` + `@inertiajs/vue3`)
- **UI framework:** Vue 3
- **Build tool:** Vite 7 + `laravel-vite-plugin`
- **Styling:** Tailwind CSS 4 + `tw-animate-css`
- **Routing helper in JS:** Ziggy (`tightenco/ziggy`, `ziggy-js`)

### Tooling & Quality

- Tests: Pest / PHPUnit (`tests/`)
- Formatting/refactor: Pint + Rector (`composer format`)
- Debug: Laravel Debugbar in dev

---

## 2) Runtime Boot Sequence

## HTTP entry and app bootstrap

1. HTTP request enters Laravel public entrypoint (`public/index.php`).
2. App configured in `bootstrap/app.php`:
    - web routes from `routes/web.php`
    - command routes from `routes/console.php`
    - health endpoint `/up`
3. Middleware registration in `bootstrap/app.php`:
    - appends `HandleInertiaRequests` to web middleware stack
    - aliases `role` => `App\Http\Middleware\CheckRoleAccess`

## Inertia lifecycle

- Root frontend app is in `resources/js/app.js`.
- Page components are resolved dynamically from `resources/js/Pages/**/*.vue`.
- Shared props are injected by `app/Http/Middleware/HandleInertiaRequests.php`:
    - `auth.user` with loaded `role` + `office`
    - flash messages (`success`, `error`)

---

## 3) Request Routing and Security Model

## Route organization

- Main route file: `routes/web.php`
- Auth entry:
    - `GET /login` (`SessionController@index`)
    - `POST /login` (`SessionController@login`)
- All business modules are inside `Route::middleware(['auth'])` group.

## Role-based access

- Route middleware uses comma-separated role names and supports special `office` token.
- Middleware logic in `app/Http/Middleware/CheckRoleAccess.php`:
    - redirects guests/no-role users to login
    - allows explicit role matches
    - allows non-system office-bound roles when `office` is in allowed list

## Role constants

System roles are centralized in `app/Enums/RoleType.php`:

- Superadmin
- BAC Reso Admin
- Budgeting Admin
- Canvassing Admin
- Document Admin
- PR Admin
- Quotation Admin

---

## 4) Frontend Structure

## Core folders

- `resources/js/Pages/` — route-level pages (per module)
- `resources/js/components/` — reusable UI components
- `resources/js/Layout/` — app layout shells
- `resources/js/composables/` — reusable logic/hooks
- `resources/js/lib/` — utilities

## Module page namespaces

Each backend module maps to a page folder (examples):

- `Pages/PPMPs`
- `Pages/Emanatings`
- `Pages/Canvasses`
- `Pages/PurchaseRequests`
- `Pages/RFQs`, `Pages/AOQs`, `Pages/BACResolutions`
- `Pages/NOAs`, `Pages/PurchaseOrders`, `Pages/POTransmittals`

---

## 5) Background/Dev Runtime Commands

From `composer.json` scripts:

- `composer setup`:
    - install PHP deps
    - initialize `.env`
    - key generation
    - migrate DB
    - install JS deps
    - build frontend

- `composer dev` runs concurrently:
    - `php artisan serve`
    - `php artisan queue:listen --tries=1`
    - `npm run dev`

- `composer test`:
    - clears config cache
    - runs Laravel test suite

---

## 6) Important Architectural Characteristics

- This is a **workflow/state-machine-heavy app**, not just CRUD.
- Many modules expose both CRUD and transition endpoints (`approve`, `reject`, `finalize`, `return`, `complete`).
- PDF outputs are first-class deliverables for records/compliance.
- Planning and request entities are linked by strict gate conditions (e.g., approved PPMP before Emanating approval; approved + canvassed Emanating before PR creation).

When editing, always check downstream stage assumptions before changing statuses or relationship logic.
