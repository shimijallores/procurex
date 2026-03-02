# Procurex AI Context Pack

This folder is a **codebase-native instruction set** for AI agents and engineers working in Procurex.
It is optimized for fast context loading: start broad, then drill into exact flow and files.

## Recommended Read Order

1. `stack-and-runtime.md` — framework stack, boot/runtime lifecycle, auth, frontend/server wiring.
2. `procurement-flow-map.md` — end-to-end procurement pipeline and status gates.
3. `codebase-map-for-agents.md` — file-level map, common change recipes, guardrails.
4. `../migrations-schema.md` — full database table-level reference.

## One-Screen Summary

- Backend: Laravel 12 + PHP 8.2 with Eloquent, Inertia bridge, custom role middleware.
- Frontend: Vue 3 + Inertia + Vite + Tailwind 4 + Ziggy.
- Domain: government procurement workflow from APP/PPMP planning through PO transmittal.
- Key control points: role-based route access, approval/rejection state transitions, PDF generation, CSV imports.

## Golden Rule for AI Changes

When changing behavior, follow this sequence:

1. Locate route in `routes/web.php`.
2. Trace controller method.
3. Check related model relations/casts.
4. Check request validator (if used) in `app/Http/Requests`.
5. Check paired Inertia page in `resources/js/Pages/**`.
6. Confirm downstream workflow impact in `procurement-flow-map.md`.

This prevents local fixes from breaking pipeline stages.
