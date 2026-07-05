# Procurex User Manual

Provincial Government of Batangas — Procurement Management System

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Deployment](#2-deployment)
3. [Getting Started](#3-getting-started)
4. [Dashboard](#4-dashboard)
5. [Administration](#5-administration)
6. [Planning & Budgeting](#6-planning--budgeting)
7. [Sourcing & Preparation](#7-sourcing--preparation)
8. [Solicitation & Awards](#8-solicitation--awards)
9. [Post-Award & Compliance](#9-post-award--compliance)
10. [Cross-Cutting Features](#10-cross-cutting-features)
11. [Frequently Asked Questions](#11-frequently-asked-questions)
12. [Glossary](#12-glossary)

---

## 1. Introduction

Procurex is a web-based procurement management system built for the **General Services Office of the Province of Batangas**. It digitizes the entire government procurement lifecycle — from annual planning all the way through purchase order transmittal — following the rules and procedures set by Republic Act No. 9184 (Government Procurement Reform Act).

### Core Workflow

```
APP / PPMP → Emanating → Canvassing → Purchase Request
  → RFQ → AOQ → BAC Resolution → NOA → Purchase Order
  → PO Transmittal → Acceptance & Inspection
```

Each stage feeds into the next, and the system tracks documents across the entire chain.

### Who Uses Procurex

| Role                 | Responsibilities                                   |
| -------------------- | -------------------------------------------------- |
| **Super Admin**      | Full system access, user management, configuration |
| **PR Admin**         | Creates and manages Purchase Requests              |
| **Quotation Admin**  | Manages RFQs and AOQs                              |
| **BAC Reso Admin**   | Manages BAC Resolutions                            |
| **Budgeting Admin**  | Manages APPs, PPMPs, Funds                         |
| **Canvassing Admin** | Manages canvassing and master list                 |
| **Document Admin**   | Manages NOAs, POs, transmittals                    |
| **Office Staff**     | Creates emanatings and initiates requests          |

---

## 2. Deployment

---

### 2.1 Linux Localhost

#### 2.1.1 Prerequisites

```bash
# Install PHP 8.3 and extensions
sudo apt install -y php8.3 php8.3-cli php8.3-mbstring php8.3-xml \
  php8.3-bcmath php8.3-curl php8.3-zip php8.3-sqlite3 php8.3-mysql \
  php8.3-gd php8.3-intl php8.3-tokenizer

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20+
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Git
sudo apt install -y git unzip
```

#### 2.1.2 Configure PHP (All 4 Limits)

In `/etc/php/8.3/cli/php.ini`:

```ini
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
```

#### 2.1.3 Clone and Setup

```bash
cd ~
git clone <your-repo-url> procurex
cd procurex

composer run setup
php artisan migrate --seed
php artisan storage:link
```

#### 2.1.4 Run the App

```bash
php artisan serve
```

Open `http://localhost:8000` in your browser.

#### 2.1.5 PDF Generation

Install Google Chrome for Browsershot PDFs:

```bash
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo apt install -y ./google-chrome-stable_current_amd64.deb
```

If you get Chrome sandbox errors, run:

```bash
sudo sysctl -w kernel.unprivileged_userns_clone=1
```

#### 2.1.6 Queue Worker (Background Tasks)

Open a second terminal and run:

```bash
cd ~/procurex
php artisan schedule:work
```

> This handles automated tasks like locking expired batches.

---

### 2.2 Windows Localhost

#### 2.2.1 Prerequisites

- **PHP 8.3** — Download from https://windows.php.net (non-thread-safe, Zip variant). Add to PATH.
- **Composer** — Download from https://getcomposer.org
- **Node.js 20+** — Download from https://nodejs.org
- **Git** — Download from https://git-scm.com
- **Chrome** — Already installed on most machines.

Make sure `php`, `composer`, `node`, `npm` and `git` are all available in your command line.

#### 2.2.2 Configure PHP (All 4 Limits)

In your `php.ini`:

```ini
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
```

#### 2.2.3 Installation

Open **PowerShell** or **Command Prompt**:

```bash
cd C:\
git clone <your-repo-url> procurex
cd procurex

composer run setup
php artisan migrate --seed
php artisan storage:link
```

#### 2.2.4 Run the App

```bash
php artisan serve
```

Open `http://localhost:8000` in your browser.

#### 2.2.5 Queue Worker (Background Tasks)

Open a second terminal:

```bash
cd C:\procurex
php artisan schedule:work
```

---

## 3. Getting Started

### Logging In

1. Open Procurex in your browser (URL provided by your administrator).
2. Enter your **email address** and **password**.
3. Click **Sign In**.

> If you forgot your password, contact your system administrator to reset it.

### The Main Interface

After logging in, you'll see:

- **Top Navigation Bar** — your name/avatar, notifications, and logout.
- **Sidebar Menu** — links to all modules you have access to (varies by role).
- **Main Content Area** — where the active page appears.
- **Breadcrumbs** — at the top of each page showing where you are.

### Switching Between Modules

Click any item in the sidebar to open that module. The sidebar collapses automatically on smaller screens.

---

## 4. Dashboard

The Dashboard is your home screen. It shows:

- **Role-based metrics** — counts of pending items relevant to your role (e.g., pending PRs, active RFQs).
- **Workflow Pipeline Chart** — a visual flow showing how many documents are at each stage of procurement.
- **Monthly Activity** — a line chart showing document activity over the past months.
- **Recent Records** — quick links to the most recently created documents.
- **Quick Actions** — one-click links to create new documents.

### Using the Dashboard

- Click any metric card to go directly to that module's list.
- Hover over pipeline stages to see counts.
- Click a recent record to open it.

---

## 5. Administration

These modules are available only to **Super Admin** users.

### Users

Manage who can access the system.

- **View Users** — see all registered users.
- **Create User** — add a new user with email, name, password, and role assignment.
- **Edit User** — update user details or change their role.
- **Deactivate User** — prevent a user from logging in without deleting their account.

### Roles

Roles control what each user can see and do.

- Each role has a name and a set of **permissions**.
- Permissions control access to specific modules and actions (create, edit, delete, view).
- Users can have multiple roles.

### Offices

Manage the organizational units of the provincial government.

- Add, edit, and deactivate offices.
- Each Purchase Request is linked to an office.

### Project Codes

Reference codes used to categorize procurement projects.

- Add and manage project codes used across the system.

### Audit Logs

A read-only log of all significant changes made in the system.

- See who did what and when.
- Filter by date range, user, or action type.

---

## 6. Planning & Budgeting

### Calendar

Mark holidays and non-working days.

- **Why it matters** — Procurex checks the calendar when computing procurement timelines.
- Add entries by selecting a date and marking it as a non-working day.

> Only working days are counted in procurement period calculations.

### Funds

Manage funding sources.

- Funds are categorized as **PS** (Personnel Services), **MOOE** (Maintenance and Other Operating Expenses), or **CO** (Capital Outlay).
- Each fund is linked to one or more offices and project codes.
- Used when creating APPs and PPMPs to identify the funding source.

### Projects

Records of procurement projects with supporting documents.

- Each project can have:
    - **Project Brief** — summary description.
    - **Work Program** — breakdown of activities and timelines.
    - **Project Proposal** — detailed proposal documents.

### APP — Annual Procurement Plan

The APP is the government's yearly procurement blueprint.

- **Import from Excel** — download the template, fill it out, and upload.
- **View APP** — see all APP submissions grouped by year.
- **Categories & Items** — drill down into each APP to see categories and line items.
- **Edit** — make corrections to imported data.

**To import an APP:**

1. Go to **APPs** and click **New APP**.
2. Download the Excel template.
3. Fill in your data following the template format.
4. Upload the completed file.
5. The system will validate and import the data.

### PPMP — Project Procurement Management Plan

The PPMP breaks down each project's procurement needs by month.

- **Import from Excel** — similar to APP import.
- **Addenda** — create addenda to modify an existing PPMP.
- **Monthly Breakdown** — see quantities and amounts by month.

---

## 7. Sourcing & Preparation

### Master List

A standardized catalog of items and services the province procures.

- **Categories** — group related items (e.g., Office Supplies, Medical Equipment).
- **Items** — each item has a name, description, unit of measure, and default supplier(s).
- Used by Canvassing and Emanatings as a source of truth for item selection.

### Suppliers

Directory of registered suppliers.

- **Add Supplier** — enter company name, address, contact person, and other details.
- **Supplier Items** — link suppliers to items they can provide from the Master List.
- Used during canvassing and RFQ supplier invitations.

### Emanatings

The starting point for office-originated procurement requests.

An Emanating is a bundle of items that an office needs. It's the first step before a Purchase Request is created.

**To create an Emanating:**

1. Go to **Emanatings** and click **New Emanating**.
2. Select the **office** and enter a **purpose**.
3. Add items from the Master List using the sidebar selector.
4. Set quantities for each item.
5. Save as draft or submit for processing.

### Canvassing

Supplier price comparison before Purchase Request creation.

Canvassing allows you to collect prices from multiple suppliers for the same items.

**To create a Canvass:**

1. Go to **Canvassing** and click **New Canvass**.
2. Enter project details.
3. Add items — use the Master List sidebar to find and add items.
4. For each item, enter prices from different suppliers.
5. Save and submit.

---

## 8. Solicitation & Awards

### Purchase Requests

The formal request to purchase goods or services.

**Statuses:**

- **Draft** — still being worked on.
- **Approved** — ready for the next step.
- **Returned** — sent back for revision.

**To create a Purchase Request:**

1. Go to **Purchase Requests** and click **New PR**.
2. Select the **office**, **fund**, and **project**.
3. Add items with quantities and estimated costs.
4. Upload supporting documents if needed.
5. Submit for approval.

> PR items can originate from Emanatings, Canvassing, or be added manually.

### RFQ — Request for Quotation

The formal solicitation document sent to suppliers for price quotations.

**To create an RFQ:**

1. Go to **RFQs** and click **New RFQ**.
2. Select an approved **Purchase Request** as the source.
3. The system copies items and amounts from the PR.
4. Invite **suppliers** to submit quotations.
5. Set the **RFQ date** and **deadline**.
6. Save and publish.

**Supplier Submission Tracking:**

- Mark when a supplier has submitted their quotation.
- Track which suppliers have/have not submitted.
- View submitted prices per item.

**Printing:**

- Click the **PDF** icon to generate a printable RFQ document.
- Download multiple RFQs as a ZIP from the index page using **Download PDFs** with optional date filter.

### AOQ — Abstract of Quotation

The bid analysis document that determines the winning supplier.

**To create an AOQ:**

1. Go to **AOQs** and click **New AOQ**.
2. Select an **RFQ** (by SVP number).
3. The system loads all suppliers and their submitted prices.
4. View the calculation of totals, rankings, and the lowest calculated quotation.
5. Select the **winner supplier**.
6. Save the AOQ.

**Batch Assignment:**

- AOQs can be grouped into **Batches** for consolidated processing.
- Batches have **earmark dates** (from/to) and can be **locked** to prevent changes.
- During AOQ creation, you can either let the system auto-assign based on earmark dates or manually select a batch.

**Printing:**

- Print individual AOQ as a landscape PDF.
- Download multiple AOQs as a ZIP from the index page.

### BAC Resolutions

The formal resolution of the Bids and Awards Committee awarding the contract.

**To create a BAC Resolution:**

1. Go to **BAC Resolutions** and click **New BAC Resolution**.
2. Select a **Batch** of AOQs.
3. The system aggregates all AOQs in the batch.
4. Review the summary table and detailed abstracts.
5. Enter the resolution number and meeting date.
6. **Finalize** to lock the resolution. Finalized resolutions cannot be edited.
7. Use **Regenerate** to recalculate abstracts if AOQ data changes before finalization.

**Printing:**

- Print as a legal-size PDF.
- Download multiple from the index page.

### NOA — Notice of Award

The official notice sent to the winning supplier.

**To create a NOA:**

1. Go to **NOAs** and click **New NOA**.
2. Select a **Batch** that has AOQs with BAC Resolutions.
3. The system shows all eligible AOQs.
4. For each AOQ, review the winner, amounts, and recipient details.
5. The **NOA number** is auto-generated (format: YYYY-NNNN) but you can edit it.
6. Enter recipient name and title (auto-suggested from supplier data).
7. Save to generate the NOA.

**Printing:**

- Print individual NOA as PDF.
- Batch print all NOAs in a batch.

### Purchase Orders

The official order document authorizing the purchase.

**To create a Purchase Order:**

1. Go to **Purchase Orders** and click **New PO**.
2. Select a **Batch** and then a **NOA**.
3. The system copies item details from the NOA/RFQ.
4. The **PO number** is auto-generated (format: MMYY-NNNN) but you can edit it.
5. Enter delivery terms:
    - **15 days** for amounts below ₱200,000
    - **30 days** for amounts ₱200,000 and above
6. Save to generate the PO.

**Printing:**

- Print individual PO as PDF.
- Batch print all POs in a batch.

### PO Transmittals

Transmittal documents sent to COA (Commission on Audit) and OPG (Office of the Provincial Governor) along with POs.

**To create a Transmittal:**

1. Go to **PO Transmittals** and click **New Transmittal**.
2. Select a **Batch** to see available POs.
3. Choose POs to include.
4. Select the **type** — COA (for audit) or OPG (for governor's office).
5. The system generates a combined transmittal document.

**Printing:**

- Print combined COA/OPG transmittal as PDF.
- Batch print all transmittals.
- Download multiple as ZIP from index.

---

## 9. Post-Award & Compliance

### COA Inspection / Acceptance & Inspection

Record the inspection and acceptance of delivered goods.

**To create an Inspection Report:**

1. Go to the module and click **New**.
2. Select the related **Purchase Order**.
3. Enter inspection details and results.
4. Mark items as accepted, rejected, or partially accepted.
5. Save the report.

### SVP Matrix

A cross-reference matrix tracking SVP numbers across the procurement chain.

- See how SVP numbers flow from AOQ → RFQ → NOA → PO.
- Helps auditors and administrators trace any document through the entire workflow.

---

## 10. Cross-Cutting Features

### Global Search

Search across 8 document types at once:

- Purchase Requests
- RFQs
- NOAs
- Purchase Orders
- BAC Resolutions
- Suppliers
- Acceptance & Inspection Reports
- PO Transmittals

**To search:**

1. Click the search icon (magnifying glass) in the top bar.
2. Type your query.
3. Results appear grouped by document type.
4. Click any result to open that document.

### Bulk PDF Download

Most modules have a **Download PDFs** button on their index page.

1. Click **Download PDFs**.
2. Optionally set a **date range** to filter.
3. Leave dates empty to download all.
4. Click **Download** — the system generates individual PDFs and bundles them into a ZIP file.
5. Your browser will download the ZIP automatically.

Available for: RFQs, AOQs, NOAs, Purchase Orders, BAC Resolutions, PO Transmittals.

### Templates

Downloadable templates for bulk imports:

- APP Import Template (XLSX)
- PPMP Import Template (XLSX)
- Emanating Import Template (XLSX)
- Work Program Template (DOCX)
- Project Brief Template (DOCX)

Go to **Templates** to download any of these.

### Batch Processing

**Batches** group related documents together for consolidated processing.

- Create batches with **earmark dates** and **lock status**.
- Batches can be used to:
    - Group AOQs for BAC Resolutions
    - Group NOAs for batch printing
    - Group POs for transmittals

### Activity Logs

Every significant action in the system is logged:

- Document creation, updates, and deletions
- Status changes (e.g., PR approved, resolution finalized)
- User login activity

Administrators can review these logs for audit purposes.

---

## 11. Frequently Asked Questions

**Q: I can't log in. What do I do?**
Contact your system administrator to verify your account is active and reset your password if needed.

**Q: I don't see some modules in the sidebar.**
Your role determines which modules you can access. Contact your administrator if you need access to additional modules.

**Q: How do I print a document?**
Most documents have a **PDF** button or icon. Click it to generate a printable PDF in a new tab.

**Q: How do I download multiple documents at once?**
Go to the module's index page and click **Download PDFs**. You can optionally filter by date range.

**Q: I made a mistake on a document. How do I fix it?**
If the document is still in **Draft** status, you can edit it. Once approved or finalized, editing may be restricted. Contact your administrator if you need to correct a finalized document.

**Q: What's an SVP number?**
Small Value Procurement number — a unique identifier assigned to each RFQ. It follows the format YYYY-NNNN (year + sequential number).

**Q: What's the difference between a Batch and a module?**
A **Batch** is a grouping mechanism for AOQs, NOAs, or POs. A **module** is a document type (RFQ, AOQ, NOA, etc.).

**Q: The system feels slow. What can I do?**
Try refreshing the page. If the issue persists, check your internet connection or contact your administrator.

**Q: Can I undo a BAC Resolution finalization?**
No. Once a resolution is finalized, it is locked. Contact your administrator if corrections are needed.

**Q: How are delivery terms determined for POs?**

- ₱200,000 and below: **15 days** delivery period.
- Above ₱200,000: **30 days** delivery period.

**Q: What happens when a batch's earmark dates expire?**
The system automatically locks expired batches, preventing further changes to AOQs within that batch.

---

## 12. Glossary

| Term          | Definition                                    |
| ------------- | --------------------------------------------- |
| **AOQ**       | Abstract of Quotation — bid analysis document |
| **APP**       | Annual Procurement Plan                       |
| **BAC**       | Bids and Awards Committee                     |
| **COA**       | Commission on Audit                           |
| **Emanating** | Office-originated procurement request bundle  |
| **MOOE**      | Maintenance and Other Operating Expenses      |
| **NOA**       | Notice of Award                               |
| **OPG**       | Office of the Provincial Governor             |
| **PO**        | Purchase Order                                |
| **PPMP**      | Project Procurement Management Plan           |
| **PR**        | Purchase Request                              |
| **PS**        | Personnel Services                            |
| **RFQ**       | Request for Quotation                         |
| **SVP**       | Small Value Procurement                       |
| **ABC**       | Approved Budget for the Contract              |
| **PSR**       | Price Submission Report                       |
| **BAC Reso**  | BAC Resolution (award resolution)             |
| **CO**        | Capital Outlay                                |
| **CAN**       | Canvassing / Canvass                          |
| **EMAN**      | Emanating                                     |

---

## 13. Troubleshooting & Known Issues

### PDF Not Rendering / Blank PDF

If generated PDFs appear blank, cut off, or fail to open:

- **Chrome/Browsershot not installed** — PDF generation requires Google Chrome. On Linux, install it:
    ```bash
    sudo apt install -y fonts-liberation
    wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
    sudo apt install -y ./google-chrome-stable_current_amd64.deb
    ```
- **Missing Arial font** — Linux servers lack Arial. Install `fonts-liberation` (above) or add `Liberation Sans` to the CSS font stack. On Linux, Liberations Sans is rendered as a metric-compatible Arial replacement.
- **PHP memory limit** — Large PDFs (many line items) may exceed `memory_limit`. Bump to at least `256M` or `512M` in `php.ini`.
- **Chrome sandbox errors** — Run `sudo sysctl -w kernel.unprivileged_userns_clone=1` to fix.

### PDF Download Takes Too Long / Times Out

- Increase `max_execution_time` and `max_input_time` in `php.ini` (recommended: `300`).
- Bulk downloads with many records will take longer — keep date filters narrow.

### Browser Shows PDF as Raw Text / Downloads Instead of Opening Inline

- This is normal browser behavior. The PDF is served as a download; save and open it in a local PDF viewer.

### "Unable to locate file in Vite manifest"

- Frontend assets haven't been built. Run:
    ```bash
    npm run build
    ```
    Or keep `npm run dev` running while developing.

### Other Issues

For all other problems, contact the system administrator.

---

_Procurex — Provincial Government of Batangas Procurement Management System_

_For support, contact the system administrator._
