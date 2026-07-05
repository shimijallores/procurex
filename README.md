# Procurex

Procurex is a browser-based procurement management system for the **General Services Office of the Province of Batangas, Philippines**. It digitizes the complete local government procurement workflow — from annual procurement planning through purchase request, bidding, award resolution, purchase order, and post-award inspection.

---

## Features

### End-to-End Procurement Pipeline

```
APP / PPMP → Emanating → Canvassing → Purchase Request → RFQ → AOQ → BAC Resolution → NOA → Purchase Order → PO Transmittal
```

### Modules

**Administration** — Users, Roles, Offices, Project Codes, Audit Logs
**Planning** — Calendar, Funds, Projects, APPs, PPMPs
**Sourcing** — Master List, Suppliers, Emanatings, Canvassing
**Solicitation** — Purchase Requests, RFQs, AOQs, BAC Resolutions
**Award & Post-Award** — NOA, Purchase Orders, PO Transmittals, COA / Acceptance & Inspection
**Cross-Cutting** — Dashboard, Global Search, SVP Matrix, Templates

### Key Capabilities

- **Role-based access control** — 10 distinct role types with module-level visibility and permissions
- **XLSX bulk imports** — APPs, PPMPs, Emanatings, work programs, project briefs
- **Bulk PDF download** — Generate ZIP archives of all PDFs per module with optional date range filter (RFQ, AOQ, NOA, Purchase Order, BAC Resolution, PO Transmittal)
- **Document generation** — PDF and DOCX output for all key procurement documents
- **Global search** — Search across 8 document types simultaneously
- **Batch processing** — Batch PO transmittals, batch PDF printing, batch NOA printing
- **Editable snapshots** — Issued documents preserve snapshot data while allowing edits
- **Supplier submission tracking** — Track quotation submissions per RFQ per supplier
- **Winner determination** — Automated bid analysis and lowest calculated/responsive quotation ranking

---

## Tech Stack

| Layer        | Technology                                                           |
| ------------ | -------------------------------------------------------------------- |
| **Backend**  | PHP ^8.3, Laravel 12                                                 |
| **Frontend** | Vue 3 (Composition API, TypeScript), Inertia.js v2                   |
| **CSS**      | Tailwind CSS v4, shadcn-vue (reka-ui), Lucide icons                  |
| **Database** | MySQL (production) / SQLite (local dev)                              |
| **PDF**      | Spatie Laravel PDF (Browsershot/Puppeteer) + DomPDF (batch printing) |
| **Excel**    | Laravel Excel (Maatwebsite) 3.1                                      |
| **Search**   | Laravel Scout                                                        |
| **Build**    | Vite 7                                                               |
| **Testing**  | Pest PHP 4                                                           |
| **Tooling**  | Laravel Pint, Rector 2, Laravel Sail (Docker)                        |

---

## Getting Started

- Refer to the user manual for a more comprehensive guide on installation, configuration, and usage.

## Seeded Accounts

Default password for all accounts: `password`

| Role                       | Email                        |
| -------------------------- | ---------------------------- |
| Super Admin                | superadmin@procurex.com      |
| BAC Reso Admin             | bacreso@procurex.com         |
| Budgeting Admin            | budgeting@procurex.com       |
| Canvassing Admin           | canvassing@procurex.com      |
| PR Admin                   | pradmin@procurex.com         |
| Quotation Admin            | quotation@procurex.com       |
| Document Admin             | document@procurex.com        |
| Office Sample (Veterinary) | veterenaryadmin@procurex.com |

---

## Architecture

### Directory Structure

```
app/
├── Console/Commands/     # Artisan commands (LockExpiredBatches, ExportSeedData)
├── Enums/                # RoleType
├── Exports/              # Excel export classes
├── Http/
│   ├── Controllers/      # 36 controllers
│   ├── Middleware/        # Role-based access, Inertia setup
│   └── Requests/         # 47 form request classes
├── Imports/              # XLSX import classes
├── Models/               # 45 Eloquent models
└── Rules/                # Custom validation rules

resources/
├── js/
│   ├── Components/       # Reusable Vue components
│   ├── Pages/            # Inertia page components
│   ├── Layout/           # App layout
│   └── Composable/       # Vue composables
└── views/                # Blade templates (PDF, DOCX)

routes/
├── web.php               # Main application routes
├── templates.php         # Template download routes
└── console.php           # Scheduled tasks

database/
├── migrations/           # 43 migrations
├── seeders/              # 14 seeders with sample data
└── factories/            # Model factories
```

### Models

45 Eloquent models covering the full procurement lifecycle: `AcceptanceInspection`, `AOQ`, `APP`, `BACResolution`, `Batch`, `Calendar`, `Canvas`, `COAInspection`, `Emanating`, `Fund`, `MasterListItem`, `NOA`, `POTransmittal`, `PPMP`, `Project`, `PurchaseOrder`, `PurchaseRequest`, `RFQ`, `Supplier`, `SvpMatrix`, `User`, and supporting models.

### Scheduled Tasks

- **`batches:lock-expired`** — Automatically locks batches whose earmark date range has ended (runs every minute).

---

## Development

### Code Style

```bash
composer run format        # Laravel Pint
composer run refactor      # Rector
```

### Testing

```bash
php artisan test --compact
```

---

## License

Copyright 2025 Province of Batangas

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
