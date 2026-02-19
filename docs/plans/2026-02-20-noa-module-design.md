# NOA Module Design (Approved)

## Scope

Implement a new `NOA` (Notice of Award) module under Documents with role-gated access for `Document Admin`, componentized Vue pages, and printable PDF aligned with provided sample.

## Decisions

- Role: Add `Document Admin` as a system role (enum + seeder user).
- Data model: Lean relational.
- NOA number: default to RFQ `svp_no` exactly.
- NOA date: default to RFQ date, editable.
- Constraints: `noa_no` unique and `bac_resolution_id` unique (one NOA per BAC Resolution).

## Data Model

Table: `noas`

- `id` bigint PK
- `bac_resolution_id` bigint FK -> `bac_resolutions.id`
- `noa_no` varchar unique
- `noa_date` date
- timestamps
- indexes on `bac_resolution_id`, `noa_date`

Relationships:

- NOA belongsTo BACResolution
- BACResolution hasOne NOA

## Access & Navigation

- Access: `Superadmin`, `Document Admin`
- Sidebar: Documents group includes `BAC Resolutions` and `Notice of Award`

## Pages / Components

- `NOAs/Index.vue` + componentized index header/stats/table
- `NOAs/Create.vue` + componentized create form/header
- `NOAs/Show.vue` + componentized show header/details + delete modal
- Table/index style follows PPMP pattern using shadcn-vue cards/tables/filter/search/pagination.

## PDF

- Route: `noas/{noa}/pdf`
- Controller print method renders `pdf.noa`
- Content structure based on sample:
    - Header block (Provincial Governor Office)
    - NOA no/date
    - Recipient/supplier block
    - Notice text referencing BAC resolution and RFQ date
    - Project + contract amount table (words + figures)
    - Governor signature + conforme block

## Additional Fixes Included

- Add print PDF icon action in each table row for:
    - AOQ index
    - BAC Resolutions index

## Validation

- Route list contains NOA CRUD+PDF routes
- Static diagnostics pass for touched PHP/Vue/Blade files
