## Migration Schema Summary (database/migrations)

### Users (`users`)

- **table:** User (`users`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `name : string`
    - `office_id : unsignedBigInteger (nullable)`
    - `role_id : unsignedBigInteger`
    - `email : string`
    - `email_verified_at : timestamp (nullable)`
    - `password : string`
    - `remember_token : string(100) (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Office (`office_id` → `offices.id`, nullable)
    - belongs to Role (`role_id` → `roles.id`)
- **constraints:**
    - PK: `id`
    - Unique: `email`
    - FK: `office_id` references `offices(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `role_id` references `roles(id)` onDelete `cascade` (onUpdate not specified)

### Roles (`roles`)

- **table:** Role (`roles`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `office_id : unsignedBigInteger (nullable)`
    - `is_system_role : boolean (default false)`
    - `name : string`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Office (`office_id` → `offices.id`, nullable)
- **constraints:**
    - PK: `id`
    - Unique: `name`
    - Index: `is_system_role`
    - Index: `name`
    - FK: `office_id` references `offices(id)` onDelete `cascade` (onUpdate not specified)

### Offices (`offices`)

- **table:** Office (`offices`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `name : string`
    - `code : string`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - none
- **constraints:**
    - PK: `id`
    - Unique: `name`
    - Unique: `code`
    - Index: `name`
    - Index: `code`

### Funds (`funds`)

- **table:** Fund (`funds`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `office_id : unsignedBigInteger`
    - `code : string`
    - `name : string`
    - `type : enum('general','project')`
    - `fiscal_year : unsignedSmallInteger`
    - `remarks : string (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Office (`office_id` → `offices.id`)
- **constraints:**
    - PK: `id`
    - Unique: `code`
    - Index: `code`
    - Index: (`office_id`,`fiscal_year`)
    - FK: `office_id` references `offices(id)` onDelete `cascade` (onUpdate not specified)

### Projects (`projects`)

- **table:** Project (`projects`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `fund_id : unsignedBigInteger`
    - `name : string`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Fund (`fund_id` → `funds.id`)
- **constraints:**
    - PK: `id`
    - Index: `fund_id`
    - FK: `fund_id` references `funds(id)` onDelete `cascade` (onUpdate not specified)

### Work Programs (`work_programs`)

- **table:** WorkProgram (`work_programs`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `project_id : unsignedBigInteger`
    - `file_url : string`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Project (`project_id` → `projects.id`)
- **constraints:**
    - PK: `id`
    - Index: `project_id`
    - FK: `project_id` references `projects(id)` onDelete `cascade` (onUpdate not specified)

### Project Briefs (`project_briefs`)

- **table:** ProjectBrief (`project_briefs`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `project_id : unsignedBigInteger`
    - `file_url : string`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Project (`project_id` → `projects.id`)
- **constraints:**
    - PK: `id`
    - Index: `project_id`
    - FK: `project_id` references `projects(id)` onDelete `cascade` (onUpdate not specified)

### Project Proposals (`project_proposals`)

- **table:** ProjectProposal (`project_proposals`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `project_id : unsignedBigInteger`
    - `file_url : string`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Project (`project_id` → `projects.id`)
- **constraints:**
    - PK: `id`
    - Index: `project_id`
    - FK: `project_id` references `projects(id)` onDelete `cascade` (onUpdate not specified)

### APPs (`apps`)

- **table:** APP (`apps`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `office_id : unsignedBigInteger`
    - `fiscal_year : unsignedSmallInteger`
    - `uploaded_file : string (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Office (`office_id` → `offices.id`)
- **constraints:**
    - PK: `id`
    - Index: `fiscal_year`
    - Index: (`office_id`,`fiscal_year`)
    - FK: `office_id` references `offices(id)` onDelete `cascade` (onUpdate not specified)

### APP Categories (`app_categories`)

- **table:** APPCategory (`app_categories`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `app_id : unsignedBigInteger (nullable)`
    - `pap_code : string`
    - `name : string`
    - `early_procurement : boolean (nullable, default false)`
    - `mode_of_procurement : string`
    - `schedule_from_month : unsignedTinyInteger (nullable)`
    - `schedule_to_month : unsignedTinyInteger (nullable)`
    - `source_of_fund : string (nullable)`
    - `estimated_budget : decimal(15,2) (default 0)`
    - `mooe_amount : decimal(15,2) (nullable, default 0)`
    - `co_amount : decimal(15,2) (nullable, default 0)`
    - `remarks : string (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to APP (`app_id` → `apps.id`, nullable)
- **constraints:**
    - PK: `id`
    - Unique: `pap_code`
    - Index: `app_id`
    - Index: `pap_code`
    - Index: `name`
    - FK: `app_id` references `apps(id)` onDelete `cascade` (onUpdate not specified)

### APP Items (`app_items`)

- **table:** APPItem (`app_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `app_category_id : unsignedBigInteger`
    - `name : string`
    - `estimated_budget : decimal(15,2)`
    - `mooe_amount : decimal(15,2) (nullable)`
    - `co_amount : decimal(15,2) (nullable)`
    - `remarks : string (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to APPCategory (`app_category_id` → `app_categories.id`)
- **constraints:**
    - PK: `id`
    - Index: `app_category_id`
    - Index: `name`
    - FK: `app_category_id` references `app_categories(id)` onDelete `cascade` (onUpdate not specified)

### Calendars (`calendars`)

- **table:** Calendar (`calendars`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `date : date`
    - `type : enum('holiday','special_workday','blackout','suspended')`
    - `name : string (nullable)`
    - `remarks : string (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - none
- **constraints:**
    - PK: `id`
    - Unique: `date`
    - Index: `date`

### PPMPs (`ppmps`)

- **table:** PPMP (`ppmps`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `office_id : unsignedBigInteger`
    - `project_id : unsignedBigInteger`
    - `account_code : string (nullable)`
    - `project_code : string (nullable)`
    - `fiscal_year : unsignedSmallInteger`
    - `is_addendum : boolean (default false)`
    - `remarks : string (nullable)`
    - `csv_path : string (nullable)`
    - `budget_notices : json (nullable)`
    - `status : string (default 'pending')`
    - `is_approved : boolean (default false)`
    - `approved_at : timestamp (nullable)`
    - `approved_by : unsignedBigInteger (nullable)`
    - `rejection_reason : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Office (`office_id` → `offices.id`)
    - belongs to Project (`project_id` → `projects.id`)
    - belongs to User as approver (`approved_by` → `users.id`, nullable)
- **constraints:**
    - PK: `id`
    - Unique: `account_code`
    - Unique: `project_code`
    - Index: (`office_id`,`project_id`,`fiscal_year`) named `idx_ppmp`
    - FK: `office_id` references `offices(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `project_id` references `projects(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `approved_by` references `users(id)` onDelete `set null` (onUpdate not specified)

### PPMP Categories (`ppmp_categories`)

- **table:** PPMPCategory (`ppmp_categories`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `ppmp_id : unsignedBigInteger`
    - `code : string`
    - `name : string`
    - `estimated_budget : decimal(15,2)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PPMP (`ppmp_id` → `ppmps.id`)
- **constraints:**
    - PK: `id`
    - Unique: `code`
    - Index: `ppmp_id` named `idx_ppmp_category_ppmp`
    - FK: `ppmp_id` references `ppmps(id)` onDelete `cascade` (onUpdate not specified)

### PPMP Items (`ppmp_items`)

- **table:** PPMPItem (`ppmp_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `ppmp_category_id : unsignedBigInteger`
    - `name : string`
    - `quantity : unsignedBigInteger`
    - `unit : string`
    - `estimated_budget : decimal(15,2)`
    - `mode_of_procurement : enum('bidding','small value','direct','direct_contracting')`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PPMPCategory (`ppmp_category_id` → `ppmp_categories.id`)
- **constraints:**
    - PK: `id`
    - Index: (`name`,`unit`) named `idx_ppmp_item_name_unit`
    - FK: `ppmp_category_id` references `ppmp_categories(id)` onDelete `cascade` (onUpdate not specified)

### PPMP Item Months (`ppmp_item_months`)

- **table:** PPMPItemMonth (`ppmp_item_months`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `ppmp_item_id : unsignedBigInteger`
    - `month : unsignedTinyInteger`
    - `planned_quantity : unsignedBigInteger (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PPMPItem (`ppmp_item_id` → `ppmp_items.id`)
- **constraints:**
    - PK: `id`
    - FK: `ppmp_item_id` references `ppmp_items(id)` onDelete `cascade` (onUpdate not specified)

### Emanatings (`emanatings`)

- **table:** Emanating (`emanatings`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `ppmp_id : unsignedBigInteger`
    - `project_id : unsignedBigInteger`
    - `ppmp_category_id : unsignedBigInteger`
    - `charged_to_code : string (nullable)`
    - `pr_no : string (nullable)`
    - `fiscal_year : unsignedSmallInteger`
    - `quarter : unsignedTinyInteger (nullable)`
    - `month : unsignedTinyInteger (nullable)`
    - `purpose : text`
    - `is_addendum : boolean (default false)`
    - `remarks : text (nullable)`
    - `reimbursement : boolean (default false)`
    - `csv_path : string (nullable)`
    - `items_match_ppmp : boolean (default false)`
    - `is_canvassed : boolean (default false)`
    - `status : string (default 'pending')`
    - `is_approved : boolean (default false)`
    - `approved_at : timestamp (nullable)`
    - `approved_by : unsignedBigInteger (nullable)`
    - `rejection_reason : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PPMP (`ppmp_id` → `ppmps.id`)
    - belongs to Project (`project_id` → `projects.id`)
    - belongs to PPMPCategory (`ppmp_category_id` → `ppmp_categories.id`)
    - belongs to User as approver (`approved_by` → `users.id`, nullable)
- **constraints:**
    - PK: `id`
    - FK: `ppmp_id` references `ppmps(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `project_id` references `projects(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `ppmp_category_id` references `ppmp_categories(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `approved_by` references `users(id)` onDelete `set null` (onUpdate not specified)

### Emanating Items (`emanating_items`)

- **table:** EmanatingItem (`emanating_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `emanating_id : unsignedBigInteger`
    - `ppmp_item_id : unsignedBigInteger`
    - `quantity : integer (nullable)`
    - `unit : string(50) (nullable)`
    - `total_price : decimal(15,2) (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Emanating (`emanating_id` → `emanatings.id`)
    - belongs to PPMPItem (`ppmp_item_id` → `ppmp_items.id`)
- **constraints:**
    - PK: `id`
    - FK: `emanating_id` references `emanatings(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `ppmp_item_id` references `ppmp_items(id)` onDelete `cascade` (onUpdate not specified)

### Suppliers (`suppliers`)

- **table:** Supplier (`suppliers`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `name : string`
    - `contact_person : string (nullable)`
    - `contact_number : string (nullable)`
    - `email : string (nullable)`
    - `address : text (nullable)`
    - `tin : string (nullable)`
    - `remarks : text (nullable)`
    - `is_active : boolean (default true)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - none
- **constraints:**
    - PK: `id`

### Master List Categories (`master_list_categories`)

- **table:** MasterListCategory (`master_list_categories`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `name : string`
    - `description : text (nullable)`
    - `is_active : boolean (default true)`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - none
- **constraints:**
    - PK: `id`

### Master List Items (`master_list_items`)

- **table:** MasterListItem (`master_list_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `master_list_category_id : unsignedBigInteger`
    - `supplier_id : unsignedBigInteger`
    - `item_name : string`
    - `unit : string (nullable)`
    - `default_unit_price : decimal(15,2) (nullable)`
    - `is_phased_out : boolean (default false)`
    - `phased_out_reason : text (nullable)`
    - `remarks : text (nullable)`
    - `search_key : string (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to MasterListCategory (`master_list_category_id` → `master_list_categories.id`)
    - belongs to Supplier (`supplier_id` → `suppliers.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`supplier_id`,`master_list_category_id`,`item_name`,`unit`) named `master_list_items_unique`
    - FK: `master_list_category_id` references `master_list_categories(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `supplier_id` references `suppliers(id)` onDelete `cascade` (onUpdate not specified)

### Canvasses (`canvasses`)

- **table:** Canvass (`canvasses`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `emanating_id : unsignedBigInteger`
    - `created_by : unsignedBigInteger`
    - `status : enum('pending','completed','returned') (default 'pending')`
    - `return_reason : text (nullable)`
    - `total_amount : decimal(15,2) (nullable)`
    - `completed_at : timestamp (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Emanating (`emanating_id` → `emanatings.id`)
    - belongs to User as creator (`created_by` → `users.id`)
- **constraints:**
    - PK: `id`
    - FK: `emanating_id` references `emanatings(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `created_by` references `users(id)` onDelete `cascade` (onUpdate not specified)

### Canvas Items (`canvas_items`)

- **table:** CanvasItem (`canvas_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `canvas_id : unsignedBigInteger`
    - `emanating_item_id : unsignedBigInteger`
    - `computed_price : decimal(15,2) (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Canvass (`canvas_id` → `canvasses.id`)
    - belongs to EmanatingItem (`emanating_item_id` → `emanating_items.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`canvas_id`,`emanating_item_id`)
    - FK: `canvas_id` references `canvasses(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `emanating_item_id` references `emanating_items(id)` onDelete `cascade` (onUpdate not specified)

### Canvas Item Selections (`canvas_item_selections`)

- **table:** CanvasItemSelection (`canvas_item_selections`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `canvas_item_id : unsignedBigInteger`
    - `master_list_item_id : unsignedBigInteger`
    - `quantity : decimal(10,2) (default 1)`
    - `unit_price : decimal(15,2)`
    - `subtotal : decimal(15,2)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to CanvasItem (`canvas_item_id` → `canvas_items.id`)
    - belongs to MasterListItem (`master_list_item_id` → `master_list_items.id`)
- **constraints:**
    - PK: `id`
    - FK: `canvas_item_id` references `canvas_items(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `master_list_item_id` references `master_list_items(id)` onDelete `cascade` (onUpdate not specified)

### Purchase Requests (`purchase_requests`)

- **table:** PurchaseRequest (`purchase_requests`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `emanating_id : unsignedBigInteger`
    - `office_id : unsignedBigInteger`
    - `fund_id : unsignedBigInteger`
    - `pr_no : string (nullable)`
    - `pr_date : date (nullable)`
    - `sai_no : string (nullable)`
    - `sai_date : date (nullable)`
    - `purpose : text (nullable)`
    - `total_amount : decimal(15,2) (default 0)`
    - `status : enum('draft','returned','for_budget_review','approved','cancelled') (default 'draft')`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to Emanating (`emanating_id` → `emanatings.id`)
    - belongs to Office (`office_id` → `offices.id`)
    - belongs to Fund (`fund_id` → `funds.id`)
- **constraints:**
    - PK: `id`
    - Index: `pr_no`
    - Index: `status`
    - Index: `office_id`
    - Index: `fund_id`
    - Index: `emanating_id`
    - Index: `pr_date`
    - FK: `emanating_id` references `emanatings(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `office_id` references `offices(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `fund_id` references `funds(id)` onDelete `cascade` (onUpdate not specified)

### Purchase Request Items (`purchase_request_items`)

- **table:** PurchaseRequestItem (`purchase_request_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `purchase_request_id : unsignedBigInteger`
    - `emanating_item_id : unsignedBigInteger`
    - `quantity : unsignedInteger`
    - `unit_cost : decimal(15,2) (default 0)`
    - `line_total : decimal(15,2) (default 0)`
    - `vat_applicable : boolean (default false)`
    - `vat_rate : decimal(5,4) (nullable, default 0.1200)`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PurchaseRequest (`purchase_request_id` → `purchase_requests.id`)
    - belongs to EmanatingItem (`emanating_item_id` → `emanating_items.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`purchase_request_id`,`emanating_item_id`) named `uq_pr_emanating_item`
    - Index: `purchase_request_id`
    - Index: `emanating_item_id`
    - FK: `purchase_request_id` references `purchase_requests(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `emanating_item_id` references `emanating_items(id)` onDelete `cascade` (onUpdate not specified)

### Earmarks (`earmarks`)

- **table:** Earmark (`earmarks`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `purchase_request_id : unsignedBigInteger`
    - `fund_id : unsignedBigInteger`
    - `earmark_no : string`
    - `earmark_date : date`
    - `certified_amount : decimal(15,2)`
    - `expense_class : string (nullable)`
    - `resolution_no : string (nullable)`
    - `ordinance_no : string (nullable)`
    - `ordinance_date : date (nullable)`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PurchaseRequest (`purchase_request_id` → `purchase_requests.id`)
    - belongs to Fund (`fund_id` → `funds.id`)
- **constraints:**
    - PK: `id`
    - Unique: `earmark_no`
    - Index: `purchase_request_id`
    - Index: `fund_id`
    - Index: `earmark_date`
    - Index: `earmark_no`
    - FK: `purchase_request_id` references `purchase_requests(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `fund_id` references `funds(id)` onDelete `cascade` (onUpdate not specified)

### RFQs (`rfqs`)

- **table:** RFQ (`rfqs`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `pr_id : unsignedBigInteger`
    - `svp_no : string`
    - `rfq_date : date`
    - `submission_deadline : date (nullable)`
    - `project_name : string`
    - `abc_amount : decimal(15,2)`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PurchaseRequest (`pr_id` → `purchase_requests.id`)
- **constraints:**
    - PK: `id`
    - Unique: `svp_no`
    - Index: `pr_id` named `idx_rfq_pr`
    - Index: `rfq_date` named `idx_rfq_date`
    - Index: `submission_deadline` named `idx_rfq_submission_deadline`
    - FK: `pr_id` references `purchase_requests(id)` onDelete `cascade` (onUpdate not specified)

### RFQ Items (`rfq_items`)

- **table:** RFQItem (`rfq_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `rfq_id : unsignedBigInteger`
    - `pr_item_id : unsignedBigInteger`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to RFQ (`rfq_id` → `rfqs.id`)
    - belongs to PurchaseRequestItem (`pr_item_id` → `purchase_request_items.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`rfq_id`,`pr_item_id`) named `uq_rfq_item_rfq_pr_item`
    - Index: `rfq_id` named `idx_rfq_item_rfq`
    - Index: `pr_item_id` named `idx_rfq_item_pr_item`
    - FK: `rfq_id` references `rfqs(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `pr_item_id` references `purchase_request_items(id)` onDelete `cascade` (onUpdate not specified)

### RFQ Suppliers (`rfq_suppliers`)

- **table:** RFQSupplier (`rfq_suppliers`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `rfq_id : unsignedBigInteger`
    - `supplier_id : unsignedBigInteger`
    - `is_late : boolean (default false)`
    - `submitted_at : dateTime (nullable)`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to RFQ (`rfq_id` → `rfqs.id`)
    - belongs to Supplier (`supplier_id` → `suppliers.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`rfq_id`,`supplier_id`) named `uq_rfq_supplier_rfq_supplier`
    - Index: `rfq_id` named `idx_rfq_supplier_rfq`
    - Index: `supplier_id` named `idx_rfq_supplier_supplier`
    - Index: `is_late` named `idx_rfq_supplier_is_late`
    - Index: `submitted_at` named `idx_rfq_supplier_submitted_at`
    - FK: `rfq_id` references `rfqs(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `supplier_id` references `suppliers(id)` onDelete `cascade` (onUpdate not specified)

### RFQ Supplier Items (`rfq_supplier_items`)

- **table:** RFQSupplierItem (`rfq_supplier_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `rfq_supplier_id : unsignedBigInteger`
    - `rfq_item_id : unsignedBigInteger`
    - `unit_price : decimal(15,2) (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to RFQSupplier (`rfq_supplier_id` → `rfq_suppliers.id`)
    - belongs to RFQItem (`rfq_item_id` → `rfq_items.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`rfq_supplier_id`,`rfq_item_id`) named `uq_rfq_supplier_item_supplier_item`
    - Index: `rfq_supplier_id` named `idx_rfq_supplier_item_supplier`
    - Index: `rfq_item_id` named `idx_rfq_supplier_item_item`
    - FK: `rfq_supplier_id` references `rfq_suppliers(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `rfq_item_id` references `rfq_items(id)` onDelete `cascade` (onUpdate not specified)

### AOQs (`aoqs`)

- **table:** AOQ (`aoqs`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `rfq_id : unsignedBigInteger`
    - `aoq_date : date`
    - `winner_supplier_id : unsignedBigInteger (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to RFQ (`rfq_id` → `rfqs.id`)
    - belongs to Supplier as winner (`winner_supplier_id` → `suppliers.id`, nullable)
- **constraints:**
    - PK: `id`
    - Unique: `rfq_id` named `uq_aoq_rfq`
    - Index: `rfq_id` named `idx_aoq_rfq`
    - Index: `aoq_date` named `idx_aoq_date`
    - Index: `winner_supplier_id` named `idx_aoq_winner_supplier`
    - FK: `rfq_id` references `rfqs(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `winner_supplier_id` references `suppliers(id)` onDelete `set null` (onUpdate not specified)

### BAC Resolutions (`bac_resolutions`)

- **table:** BACResolution (`bac_resolutions`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `aoq_id : unsignedBigInteger`
    - `resolution_no : string`
    - `resolution_date : date`
    - `meeting_date : date (nullable)`
    - `project_name : string`
    - `winner_supplier_name : string`
    - `winner_amount : decimal(15,2) (default 0)`
    - `calculation_label : string (default 'Lowest Calculated')`
    - `justification : text (nullable)`
    - `signatory_chairperson : string (nullable)`
    - `signatory_member_one : string (nullable)`
    - `signatory_member_two : string (nullable)`
    - `signatory_member_three : string (nullable)`
    - `finalized_at : timestamp (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to AOQ (`aoq_id` → `aoqs.id`)
- **constraints:**
    - PK: `id`
    - Unique: `resolution_no`
    - Unique: `aoq_id` named `uq_bac_resolution_aoq`
    - Index: `resolution_no` named `idx_bac_resolution_no`
    - Index: `resolution_date` named `idx_bac_resolution_date`
    - Index: `meeting_date` named `idx_bac_meeting_date`
    - Index: `finalized_at` named `idx_bac_finalized_at`
    - FK: `aoq_id` references `aoqs(id)` onDelete `cascade` (onUpdate not specified)

### NOAs (`noas`)

- **table:** NOA (`noas`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `bac_resolution_id : unsignedBigInteger`
    - `noa_no : string`
    - `noa_date : date`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to BACResolution (`bac_resolution_id` → `bac_resolutions.id`)
- **constraints:**
    - PK: `id`
    - Unique: `noa_no`
    - Unique: `bac_resolution_id` named `uq_noa_bac_resolution`
    - Index: `bac_resolution_id` named `idx_noa_bac_resolution`
    - Index: `noa_date` named `idx_noa_date`
    - FK: `bac_resolution_id` references `bac_resolutions(id)` onDelete `cascade` (onUpdate not specified)

### Purchase Orders (`purchase_orders`)

- **table:** PurchaseOrder (`purchase_orders`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `noa_id : unsignedBigInteger`
    - `po_no : string`
    - `po_date : date`
    - `mode_of_procurement : string (default 'Small Value')`
    - `place_of_delivery : string`
    - `delivery_term_days : smallInteger (nullable)`
    - `payment_term : string (nullable)`
    - `total_amount : decimal(15,2) (default 0)`
    - `total_amount_words : text (nullable)`
    - `remarks : text (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to NOA (`noa_id` → `noas.id`)
- **constraints:**
    - PK: `id`
    - Unique: `po_no`
    - Unique: `noa_id` named `uq_purchase_order_noa`
    - Index: `noa_id` named `idx_purchase_order_noa`
    - Index: `po_no` named `idx_purchase_order_no`
    - Index: `po_date` named `idx_purchase_order_date`
    - Index: `mode_of_procurement` named `idx_purchase_order_mode`
    - FK: `noa_id` references `noas(id)` onDelete `cascade` (onUpdate not specified)

### Purchase Order Items (`purchase_order_items`)

- **table:** PurchaseOrderItem (`purchase_order_items`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `purchase_order_id : unsignedBigInteger`
    - `rfq_item_id : unsignedBigInteger`
    - `quantity_snapshot : integer`
    - `unit_cost_snapshot : decimal(15,2)`
    - `amount_snapshot : decimal(15,2)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PurchaseOrder (`purchase_order_id` → `purchase_orders.id`)
    - belongs to RFQItem (`rfq_item_id` → `rfq_items.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`purchase_order_id`,`rfq_item_id`) named `uq_purchase_order_item_po_rfq_item`
    - Index: `purchase_order_id` named `idx_purchase_order_item_po`
    - Index: `rfq_item_id` named `idx_purchase_order_item_rfq_item`
    - FK: `purchase_order_id` references `purchase_orders(id)` onDelete `cascade` (onUpdate not specified)
    - FK: `rfq_item_id` references `rfq_items(id)` onDelete `cascade` (onUpdate not specified)

### PO Transmittals (`po_transmittals`)

- **table:** POTransmittal (`po_transmittals`)
- **properties:**
    - `id : unsignedBigInteger (PK, auto-increment)`
    - `purchase_order_id : unsignedBigInteger`
    - `type : enum('coa','opg')`
    - `transmittal_no : string (nullable)`
    - `transmittal_date : date`
    - `header_text : text (nullable)`
    - `signatory_name : string (nullable)`
    - `signatory_title : string (nullable)`
    - `coa_circular_no : string (nullable)`
    - `created_at : timestamp (nullable)`
    - `updated_at : timestamp (nullable)`
- **relationships:**
    - belongs to PurchaseOrder (`purchase_order_id` → `purchase_orders.id`)
- **constraints:**
    - PK: `id`
    - Unique: (`purchase_order_id`,`type`)
    - Index: (`purchase_order_id`,`type`)
    - Index: `transmittal_date`
    - Index: `type`
    - FK: `purchase_order_id` references `purchase_orders(id)` onDelete `cascade` (onUpdate not specified)
