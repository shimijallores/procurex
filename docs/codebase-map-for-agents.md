# Codebase Map for AI Agents (Nooks, Crannies, and Safe Edit Paths)

## 1) Where Things Live

## Entry and wiring

- App boot: `bootstrap/app.php`
- HTTP routes: `routes/web.php`
- Console routes: `routes/console.php`

## HTTP layer

- Controllers: `app/Http/Controllers/`
- Request validation objects: `app/Http/Requests/`
- Middleware:
    - `CheckRoleAccess.php`
    - `HandleInertiaRequests.php`

## Domain layer

- Models: `app/Models/`
- Enum roles: `app/Enums/RoleType.php`
- Minimal services currently present: `app/Services/CalendarService.php`

## Frontend layer

- Inertia boot: `resources/js/app.js`
- Page components: `resources/js/Pages/`
- Shared components: `resources/js/components/`
- App layout: `resources/js/Layout/`

## Data and schema docs

- Migration files: `database/migrations/`
- Flattened schema reference: `docs/migrations-schema.md`
- Example import docs/csv files: `documents/`

---

## 2) Practical Navigation Patterns

## Pattern A: Add/change module behavior

1. Find route in `routes/web.php`.
2. Open matching controller method in `app/Http/Controllers/*Controller.php`.
3. Inspect model relations used by eager loads (`with([...])`).
4. Inspect request validator class (if present).
5. Open paired Inertia page under `resources/js/Pages/<Module>/`.

## Pattern B: Fix workflow bug

1. Identify stage entity (`PPMP`, `Emanating`, `Canvas`, `PurchaseRequest`, etc.).
2. Search for writes to controlling status fields (`status`, `is_approved`, `finalized_at`).
3. Verify transition endpoint and guard condition.
4. Validate downstream entities still receive expected prerequisites.

## Pattern C: Adjust role access

1. Check route middleware role list in `routes/web.php`.
2. Confirm role names against `RoleType` enum values.
3. Review `CheckRoleAccess` office-special-case behavior.
4. Confirm dashboard and quick links for affected role in `DashboardController`.

---

## 3) High-Risk Areas (Edit Carefully)

- Any method that changes approval flags or statuses.
- Canvas completion logic (writes values back to upstream request items).
- PR return/budget return paths (can cascade back to Emanating/PPMP outcomes).
- BAC finalize boundary (`finalized_at`) because NOA generation depends on it.
- Route role middleware strings (typos silently break access expectations).

---

## 4) Data Import/Export and Documents

- CSV/Excel imports are exposed in planning and emanating modules (see routes and related controllers/import classes in `app/Imports`).
- PDF outputs exist for PR, Earmark, RFQ, AOQ, BAC Resolution, NOA, PO, PO Transmittal.
- Document fidelity matters: generated records appear to function as official artifacts.

When changing templates/print logic, test both data correctness and formatting expectations.

---

## 5) AI Task Recipes

## “I need to implement a new rule in approval flow”

- Start from transition endpoint route (`approve`/`reject`/`finalize`/`return`).
- Add guard condition in controller transaction block.
- Keep status updates and timestamps consistent.
- Ensure error flash message is explicit for user actionability.
- Validate dashboard counts still align with status semantics.

## “I need to add a field to a module”

- Add migration + model `fillable`/`casts`.
- Update request validation class.
- Update controller persistence mapping.
- Update Inertia page form + display table/details.
- Update PDF template if the field belongs on printed forms.

## “I need to debug access denied”

- Verify authenticated user has loaded role and role name exact match.
- Verify route middleware role string.
- For office users, confirm non-system role + office linkage in middleware conditions.

---

## 6) Verification Checklist Before Merge

- No transition endpoint left with inconsistent status/state writes.
- No route/controller/page mismatch after renaming.
- No role string mismatch versus `RoleType` enum.
- Relevant tests run (`composer test` or targeted tests).
- If touched documents/PDF logic: sample-render at least one record per changed module.

This checklist catches the most common regressions in this codebase.
