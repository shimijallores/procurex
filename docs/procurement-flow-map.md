# Procurement Flow Map (Business Pipeline + State Gates)

This is the operational chain implemented by routes/controllers/models.

## 1) End-to-End Stage Chain

1. **Planning Inputs**
    - APP (`apps`)
    - PPMP (`ppmps`)
2. **Emanating Request**
    - built from approved planning context
3. **Canvassing**
    - supplier quotations and item-level selections
4. **Purchase Request (PR)**
    - created from approved + canvassed emanating
5. **Budgeting / Earmark**
    - budget certification + PR approval/return logic
6. **RFQ**
    - quotation requests from approved PRs with earmarks
7. **AOQ**
    - abstract and winner selection
8. **BAC Resolution**
    - draft then finalize
9. **NOA**
    - notice of award
10. **Purchase Order (PO)**
11. **PO Transmittal**

- COA / OPG transmittal records

---

## 2) Core Gate Conditions (Critical)

These are key “must-pass” checks observed in controllers/model usage:

- **PPMP must be approved** before related Emanating can be approved.
- **Emanating items must match PPMP** for approval path.
- **Canvassing completion** writes computed prices back and marks canvas completed.
- **PR creation eligibility** requires Emanating to be approved + canvassed and not yet linked to another PR.
- **RFQ creation** requires PR status `approved` and existing `earmark`.
- **AOQ follows RFQ**, BAC follows AOQ, NOA follows finalized BAC, PO follows NOA, transmittal follows PO.

If any gate logic is changed, verify all downstream modules.

---

## 3) High-Value Status Fields

These fields drive most pipeline behavior:

- `ppmps.status`, `ppmps.is_approved`, `ppmps.rejection_reason`
- `emanatings.status`, `emanatings.is_approved`, `emanatings.is_canvassed`, `emanatings.rejection_reason`
- `canvasses.status` (`pending`, `completed`, `returned` observed)
- `purchase_requests.status` (`draft`, `for_budget_review`, `approved`, `returned` observed)
- `bac_resolutions.finalized_at` (draft vs finalized)

Treat these as workflow control states, not display-only fields.

---

## 4) Controller-Level Transition Endpoints

Key transition routes in `routes/web.php`:

- PPMP: `approve`, `reject`
- Emanating: `approve`, `reject`
- Canvas: `complete`, `return`, `saveItemSelections`
- Purchase Request: `approve`, `return`, `pdf`
- Earmark: `budget-return`, `pdf`
- RFQ/AOQ/BAC/NOA/PO/PO Transmittal: PDF and creation/finalization chain

This app’s correctness depends heavily on transition endpoints rather than plain resource updates.

---

## 5) Relationship Backbone (Simplified)

- `PPMP` → has many categories/items
- `Emanating` → belongs to `PPMP`, `Project`, optional category context; has many `EmanatingItem`; has one `PurchaseRequest`; has many `Canvas`
- `Canvas` → belongs to `Emanating`; has many `CanvasItem`
- `PurchaseRequest` → belongs to `Emanating`; has one `Earmark`; feeds RFQ
- `RFQ` → belongs to `PurchaseRequest`; has suppliers/items; has one `AOQ`
- `AOQ` → has one `BACResolution`
- `BACResolution` → has one `NOA`
- `NOA` → has one `PurchaseOrder`
- `PurchaseOrder` → has many PO items; has many `POTransmittal`

Use `docs/migrations-schema.md` for complete table-level details.

---

## 6) Role-to-Module Ownership (Operational)

- **Superadmin:** full visibility
- **Budgeting Admin:** PPMP, Emanating, Earmark, budget-related PR decisions
- **Canvassing Admin:** suppliers/master list/canvass lifecycle
- **PR Admin:** purchase request creation + PR transitions
- **Quotation Admin:** RFQ/AOQ
- **BAC Reso Admin:** BAC resolution finalization
- **Document Admin:** NOA, PO, PO transmittal
- **Office roles:** office-scoped modules via `role:... ,office` routes

Use this map before changing authorization or dashboard metrics.
