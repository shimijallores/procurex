# Procurex

Procurex is a browser-based procurement management system for the General Services Office of the Province of Batangas.

## Seeded Accounts

Default password for all seeded users: `password`

- Super Admin — `superadmin@procurex.com`
- BAC Reso Admin — `bacreso@procurex.com`
- Budgeting Admin — `budgeting@procurex.com`
- Canvassing Admin — `canvassing@procurex.com`
- PR Admin — `pradmin@procurex.com`
- Quotation Admin — `quotation@procurex.com`
- Document Admin — `document@procurex.com`
- Office Sample (Veterinary Admin) — `veterenaryadmin@procurex.com`

## Modules

- **Dashboard** — role-based metrics, workflow pipeline chart, monthly activity line chart, recent records, and quick access links.
- **Users / Roles / Offices / Project Codes** — account, access management, and reference data.
- **Calendar** — holidays and non-working date checks.
- **Funds** — funding source types (PS/MOOE/CO) mapped to offices and project codes.
- **Projects** — project records with associated briefs, work programs, and project proposals.
- **APPs** — Annual Procurement Plan submissions via XLSX import, with category and item management.
- **PPMPs** — Project Procurement Management Plan records with addendum support via XLSX.
- **Master List** — standardized item categories and catalog items for canvassing.
- **Emanatings** — office-originated procurement request bundles, the starting point for the workflow.
- **Canvassing** — canvass records with master list sidebar for supplier price selection.
- **Suppliers** — supplier directory with profile details.
- **Purchase Requests** — PR drafting, status tracking (draft/approved/returned), and PDF printing.
- **RFQs** — Request for Quotation generation, supplier invitation, and submission tracking.
- **AOQs** — Abstract of Quotation with bid analysis and winner determination.
- **BAC Resolutions** — BAC award resolutions with finalization workflow.
- **NOA** — Notice of Award generation, winner amount tracking, and PDF printing.
- **Purchase Orders** — PO generation from NOA with item snapshots, delivery term logic (15/30 days based on ₱200k threshold).
- **PO Transmittals** — COA/OPG transmittal documents from PO batches with printable formats.
- **COA / Acceptance & Inspection** — inspection and receiving report records.
- **SVP Matrix** — matrix view tracking SVP numbers across AOQ → RFQ → NOA → PO.
- **Templates** — downloadable XLSX/DOCX templates for imports.
- **Audit / Activity Logs** — system-wide change tracking.

## Core Functions

- End-to-end procurement workflow: APP/PPMP → Emanating → PR → RFQ → AOQ → BAC → NOA → PO → PO Transmittal.
- Role-based access control and module visibility.
- XLSX-based bulk import for APPs, PPMPs, Emanatings, work programs, and project briefs.
- Global search across 8 document types (PR, RFQ, NOA, PO, BAC Resolution, Supplier, A&I, PO Transmittal).
- Batch PO transmittal generation with type-ahead PO selection.
- PDF and DOCX document generation for procurement records.
- Editable document templates with preserved snapshot data for issued records.
- Reusable form components with existence-warning modals to prevent accidental data loss.

## Tech Stack

- **Backend:** PHP 8.3, Laravel 12, Laravel Scout, Laravel Excel
- **Frontend:** Vue 3 (Composition API), Inertia.js v2, Tailwind CSS v4, shadcn-vue, Iconify
- **Database:** MySQL
- **PDF:** DomPDF
- **Testing:** Pest PHP 4
- **Tooling:** Laravel Pint, Rector 2, Laravel Sail
