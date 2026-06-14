export const pageInfoMap = {
  'dashboard': {
    title: 'Dashboard',
    sections: [
      {
        heading: 'Overview',
        icon: 'lucide:layout-dashboard',
        content: 'The Dashboard gives you a snapshot of everything happening in the procurement system. You\'ll see key numbers like active purchase requests, notices of award, and purchase orders at a glance.',
      },
      {
        heading: 'Recent Activity',
        icon: 'lucide:activity',
        content: 'The list on the right shows the latest actions taken in the system — newly created documents, approvals, and updates — so you can stay on top of what\'s changed.',
      },
      {
        heading: 'Quick Access',
        icon: 'lucide:mouse-pointer-2',
        content: 'Use the sidebar to navigate to any module. The dashboard widgets are clickable — tap on any number to go directly to that module\'s list page.',
      },
    ],
  },
  'users': {
    title: 'Users',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:users',
        content: 'The Users module lets administrators manage who can access the system and what they\'re allowed to do. You can add new users, update their information, or remove access when needed.',
      },
      {
        heading: 'Roles & Permissions',
        icon: 'lucide:shield',
        content: 'Each user is assigned one or more roles (like "PR Admin" or "PO Admin") that determine which parts of the system they can see and use. A user with multiple roles has combined access from all of them.',
      },
    ],
  },
  'roles': {
    title: 'Roles',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:shield',
        content: 'Roles define what actions a user can perform in the system. Each role has a specific set of permissions tailored to a job function.',
      },
      {
        heading: 'Available Roles',
        icon: 'lucide:list',
        content: 'The system includes roles for each stage of procurement: Checking Admin, Canvassing Admin, PR Admin, RFQ Admin, Abstract Admin, Resolution Admin, NOA Admin, PO Admin, and Inspection Admin. The SuperAdmin role has full access to everything.',
      },
    ],
  },
  'offices': {
    title: 'Offices & General Codes',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:building-2',
        content: 'Manage the different offices and departments within the province that participate in procurement. Each office can have its own procurement plans and requests.',
      },
      {
        heading: 'General Codes',
        icon: 'lucide:hash',
        content: 'You can also manage reference codes and identifiers used across the system, keeping everything organized and consistent.',
      },
    ],
  },
  'project-codes': {
    title: 'Project Codes',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:tags',
        content: 'Project codes are used to tag and track procurement activities that belong to specific projects. This helps separate project-based spending from general administrative purchases.',
      },
    ],
  },
  'accounts': {
    title: 'Accounts',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:book-marked',
        content: 'Accounts represent budget categories or chart of accounts entries. They\'re used to categorize spending and link procurement items to the right budget lines.',
      },
    ],
  },
  'apps': {
    title: 'APP — Annual Procurement Plan',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:clipboard-check',
        content: 'The Annual Procurement Plan (APP) is the overall budget plan for an office for the fiscal year. It sets the budget ceiling — how much can be spent on each category of goods and services.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:upload',
        content: 'Upload an XLSX file with the office\'s planned budget breakdown. The system reads the file and creates the budget categories and items automatically.',
      },
      {
        heading: 'Features',
        icon: 'lucide:sparkles',
        content: 'Only one APP is kept per office per year. Uploading a new one automatically replaces the previous version — there\'s no history of old uploads. This ensures the system always uses the latest approved budget figures.',
      },
    ],
  },
  'funds': {
    title: 'Funds',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:wallet',
        content: 'Funds represent the sources of money for procurement. Each fund is tied to an office and can be either a "General" fund (administrative budget) or a "Project" fund (specific project with supporting documents).',
      },
      {
        heading: 'How It Works',
        icon: 'lucide:settings',
        content: 'When creating a Project fund, you\'ll need to upload a Work Program, Project Brief, and Project Proposal. These documents are used later to validate that procurement items match what was planned.',
      },
    ],
  },
  'ppmps': {
    title: 'PPMP — Procurement Management Plan',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:clipboard-list',
        content: 'The PPMP is the office\'s detailed annual procurement plan. It lists every item to be purchased, organized by budget account, with quantities and monthly distribution.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:upload',
        content: 'Upload an XLSX file with the office\'s planned items. The import reads categories, items, quantities, and monthly schedules.',
      },
      {
        heading: 'Features',
        icon: 'lucide:sparkles',
        content: 'If items already exist for the same office, project code, and fiscal year, uploading a new file creates an addendum — the new items are added alongside the old ones rather than replacing them. You can keep extending the plan throughout the year as needs come up.',
      },
    ],
  },
  'emanatings': {
    title: 'Emanatings',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:clipboard-minus',
        content: 'An Emanating Request is the step where you actually request to purchase something from the PPMP. It draws from a specific fund and PPMP category to create a formal request.',
      },
      {
        heading: 'The Approval Process',
        icon: 'lucide:check-circle',
        content: 'Each emanating request goes through an approval workflow. Once approved, it becomes the basis for creating a Purchase Request. The system automatically checks that items exist in both the PPMP and APP before allowing approval.',
      },
      {
        heading: 'Features',
        icon: 'lucide:sparkles',
        content: 'For project-type funds, the system cross-checks items against the Work Program, Project Brief, and Project Proposal — if items aren\'t in those documents, the request gets flagged. The emanating number is auto-generated in sequential order (e.g., 2026-001, 2026-002).',
      },
    ],
  },
  'purchase-requests': {
    title: 'Purchase Requests',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:file-plus-2',
        content: 'The Purchase Requests module is where formal procurement requests are created and managed. This is the first official document in the procurement pipeline.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:file-text',
        content: 'Create a purchase request by providing the item details, budget information, and supporting documents. Once created, the PR moves through the workflow to become an RFQ.',
      },
    ],
  },
  'purchase-request-matrix': {
    title: 'PR Matrix',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:table-properties',
        content: 'The PR Matrix gives you a consolidated view of all purchase request items across different offices. It\'s a dashboard-style overview to track the status and progress of all PRs in one place.',
      },
    ],
  },
  'svp-matrix': {
    title: 'SVP Matrix',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:grid-2x2',
        content: 'The SVP Matrix is a master tracking table that shows the entire lifecycle of a procurement from start to finish — from the SVP number and PR to the PO and transmittal.',
      },
      {
        heading: 'How to Read It',
        icon: 'lucide:eye',
        content: 'Each row represents one procurement item. The columns show key information at every stage: the office, batch, PR number, ABC amount, supplier, awarded amount, and the dates each step was completed.',
      },
    ],
  },
  'suppliers': {
    title: 'Suppliers',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:truck',
        content: 'Manage the list of suppliers and vendors that the province works with. Each supplier\'s contact information, ownership details, and status are tracked here.',
      },
    ],
  },
  'rfqs': {
    title: 'RFQ — Request for Quotation',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:file-text',
        content: 'The Request for Quotation (RFQ) is sent to suppliers to get price quotes for items listed in a Purchase Request. It\'s the first step in the bidding process.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:send',
        content: 'Select a purchase request and issue an RFQ with the required items and deadlines. Suppliers respond with their quotations, which are then evaluated in the next step.',
      },
    ],
  },
  'aoqs': {
    title: 'AOQ — Abstract of Quotation',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:file-spreadsheet',
        content: 'The Abstract of Quotation summarizes and compares all the price quotes received from suppliers. It helps identify the lowest compliant bidder.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:bar-chart-3',
        content: 'Select RFQs to include, then organize them into a batch. The system groups multiple RFQs under one batch number and displays supplier quotes side by side for comparison.',
      },
      {
        heading: 'Features',
        icon: 'lucide:sparkles',
        content: 'RFQs can be grouped into batches for batch processing. Each batch gets a unique batch number. You can create one AOQ per RFQ or process them together in a batch.',
      },
    ],
  },
  'noas': {
    title: 'NOA — Notice of Award',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:file-badge',
        content: 'The Notice of Award (NOA) is the formal document that informs the winning supplier that their bid has been accepted. It includes the award amount, recipient details, and terms.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:printer',
        content: 'Select AOQs from a batch and create NOAs for them. Each AOQ gets its own NOA. You can print NOAs individually or print all NOAs in a batch at once.',
      },
      {
        heading: 'Features',
        icon: 'lucide:sparkles',
        content: 'Batch printing is supported — generate PDFs for all NOAs under a batch with one click. The winner amount is stored on the NOA itself so it\'s preserved even if the underlying AOQ changes.',
      },
    ],
  },
  'bac-resolutions': {
    title: 'BAC Resolutions',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:files',
        content: 'BAC Resolutions are the official decisions made by the Bids and Awards Committee. They document the committee\'s approval of the winning bidder and the award amount.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:file-check',
        content: 'Create a resolution based on an AOQ batch. You can include multiple AOQs under a single batch in one resolution. The resolution records the committee\'s decision and serves as the basis for issuing Notices of Award.',
      },
      {
        heading: 'Features',
        icon: 'lucide:sparkles',
        content: 'Supports batch resolutions — multiple AOQs from the same batch can be covered in one resolution document. The batch column helps track which batch each AOQ belongs to.',
      },
    ],
  },
  'purchase-orders': {
    title: 'Purchase Orders',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:file-signature',
        content: 'The Purchase Order (PO) is the official order sent to the winning supplier. It contains the items, quantities, prices, delivery terms, and payment terms.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:printer',
        content: 'Create a PO from an approved NOA. The system suggests a PO date based on the NOA date and calculates the delivery term (15 or 30 days) based on the award amount. POs can be printed individually or all POs in a batch at once.',
      },
      {
        heading: 'Features',
        icon: 'lucide:sparkles',
        content: 'Batch printing available — generate PDFs for all POs under a batch. Delivery terms are dynamic: ₱200k+ gets 30 days, below ₱200k gets 15 days. The PO date auto-defaults to one day after the NOA date.',
      },
    ],
  },
  'po-transmittals': {
    title: 'PO Transmittal',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:send',
        content: 'The PO Transmittal is the document that accompanies the Purchase Order when it\'s sent to various offices for review and approval. It tracks where the PO is in the routing process.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:radio',
        content: 'Select a PO and choose the transmittal type (e.g., COA, SVP). The system generates the transmittal number automatically and records when and where the PO was sent.',
      },
    ],
  },
  'acceptance-inspections': {
    title: 'Acceptance & Inspection',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:clipboard-check',
        content: 'The Acceptance & Inspection module records when delivered goods are inspected and accepted. It documents the condition of items and whether they meet specifications.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:search-check',
        content: 'When a supplier delivers items against a PO, create an inspection record. Note whether items passed inspection, any findings, and the date of acceptance.',
      },
    ],
  },
  'coa-inspections': {
    title: 'COA Inspection',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:file-check-2',
        content: 'The COA Inspection module manages inspections conducted by the Commission on Audit. It tracks COA\'s review and findings on procurement transactions.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:search',
        content: 'Create COA inspection records tied to specific purchase orders. Record the findings, recommendations, and any actions taken.',
      },
    ],
  },
  'master-list-items': {
    title: 'Master List',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:list-checks',
        content: 'The Master List is a catalog of commonly procured items with standard specifications and prices. It ensures consistency across all purchase requests.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:plus-circle',
        content: 'Add items to the master list with their standard name, unit, and estimated price. These items can be referenced when creating purchase requests to maintain consistency.',
      },
    ],
  },
  'canvasses': {
    title: 'Canvassing',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:shopping-cart',
        content: 'The Canvassing module is where you invite suppliers to bid and collect their price quotations. It\'s the hands-on part of getting market prices for needed items.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:bar-chart-4',
        content: 'Create a canvass, select items from the master list, invite suppliers, and record their quoted prices. The system helps compare bids side by side.',
      },
    ],
  },
  'calendars': {
    title: 'System Calendar',
    sections: [
      {
        heading: 'What It Does',
        icon: 'lucide:calendar',
        content: 'The System Calendar lets you manage working days and non-working days (holidays, weekends) that affect procurement deadlines.',
      },
      {
        heading: 'How to Use',
        icon: 'lucide:pencil',
        content: 'Mark dates as working or non-working. The system uses this calendar to auto-calculate deadlines and suggest dates that fall on working days.',
      },
    ],
  },
}

export function getPageInfo(routeName) {
  if (!routeName) return null

  for (const [key, info] of Object.entries(pageInfoMap)) {
    if (routeName.startsWith(key)) {
      return info
    }
  }

  return null
}
