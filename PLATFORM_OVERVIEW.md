# Zentraq — Platform Overview
### Full-Stack ERP SaaS for Growing Businesses

---

## What Is Zentraq?

Zentraq is a multi-tenant, cloud-based ERP platform built for small and mid-sized businesses that need more than a billing tool but can't justify the complexity or cost of SAP or Oracle. It covers the full operational spine of a product business — from lead to order, from purchase to production, from raw material to finished goods — all in one system with a single source of truth.

The platform is purpose-built for businesses that deal in physical products, especially those with manufacturing, assembly, or complex inventory needs.

---

## Platform Capabilities at a Glance

| Area | Capability |
|---|---|
| Modules | 10+ fully integrated business modules |
| Database tables | 70+ specialized tables |
| API endpoints | 150+ |
| Inventory tracking | Quantity, Serial number, Lot/Batch |
| Document generation | PDF (Orders, POs, MRS, Issue Slips) |
| Access control | Feature-level RBAC with custom roles |
| Integrations | Webhooks, Indiamart, Razorpay |
| Architecture | Multi-tenant SaaS with per-company isolation |

---

## Modules & Features

---

### 1. Inventory Management

The inventory engine is the backbone of Zentraq. It is not a simple quantity counter — it is a full warehouse management layer with three tracking methods, a complete movement ledger, and reservation logic that keeps availability accurate in real time.

**Stock Tracking Methods**
- **Quantity tracking** — standard in/out with on-hand and reserved balances
- **Lot / Batch tracking** — groups of units with manufacture date, expiry date, and per-lot status (active, quarantine, on hold, rejected, expired, consumed, scrapped)
- **Serial number tracking** — individual unit-level traceability from receipt through production to dispatch

**Stock Operations**
- Manual stock adjustments with reason and note
- Bulk adjustment import via CSV template (up to 2,000 rows, validated before commit)
- Full movement audit trail: initial stock, received, dispatched, produced, consumed, returned, transferred, scrapped
- Multi-location stock: view on-hand, reserved, and available by location

**Stock Reservations**
- When a Sales Order or Manufacturing Order is confirmed, stock is soft-reserved automatically
- Available quantity shown to users always reflects real uncommitted stock — no double-selling
- Reservations released automatically on order completion or cancellation

**Why this matters:** Most SMB tools let you track quantity and nothing else. Zentraq tracks individual serial numbers across the full chain — you always know exactly which unit is where and why.

---

### 2. Products & Catalogue

- Product master with SKU, description, unit of measure, pricing, and tax assignment
- Product categories for organisation
- Per-product stock tracking method selection (none, quantity, lot, serial)
- Customer-specific price lists
- Unit of measure (UOM) configuration per product
- Bulk product import via CSV

---

### 3. Sales

End-to-end sales workflow from first contact to delivery.

**Quotations & Orders**
- Create quotations, convert to sales orders
- Line item level: product, quantity, unit price, discount (flat or %), tax
- Order-level discount support
- Round-off configuration (auto/manual/off per company)
- Serial and lot number assignment at order level
- Credit limit visibility per customer

**Deliveries**
- Create delivery notes against confirmed sales orders
- Pick specific serial numbers or lots for dispatch
- Delivery status management (draft → dispatched → delivered)
- PDF generation for delivery notes

**Documents & Communication**
- PDF generation for quotations and sales orders
- Email orders directly to customers from within the system
- Audit trail: every status change, edit, and dispatch recorded with user and timestamp

**Customers**
- Multiple addresses per customer (billing, shipping, other)
- Customer contacts with designations
- Payment terms, credit limits
- Customer grouping and segmentation
- Duplicate detection on creation

---

### 4. Purchasing

Mirror image of Sales, designed for the procurement workflow.

**Purchase Orders**
- Multi-line POs with product, quantity, unit price, tax
- Status management (draft → confirmed → received → closed)
- PDF generation and email to vendor
- Audit trail

**Goods Receipt Notes (GRN)**
- Receive stock against a PO — partial receipts supported
- Assign serial numbers and lot numbers at the point of receipt
- Automatic stock movement created on GRN confirmation
- GRN audit trail

**Vendors**
- Vendor master with multiple addresses and contacts
- Duplicate detection
- Purchase history linkage

---

### 5. Manufacturing

This is where Zentraq separates itself from generic SMB tools. The manufacturing module is a proper production management system — not a workaround built on top of inventory.

**Bill of Materials (BOM)**
- Multi-level BOM creation with component products, quantities, UOM, and operation sequence
- Multiple BOMs per product (e.g., different variants or process versions)
- BOM status (active/archived)

**Manufacturing Orders (MO)**
- Create MOs from a BOM with planned quantity and scheduled date
- Source and destination location selection
- Material requirements calculated automatically from BOM at MO creation

**Production Workflow**
1. **Draft** — MO created, materials visible, not yet committed
2. **Confirm** — Stock reservations created for all required materials; low-stock warnings shown before confirmation
3. **Allocate Materials** — Issue specific stock (including serial/lot numbers) from source location to the shop floor
4. **Record Production** — Record finished goods output; specify consumed quantities per material
5. **Complete** — Automatic completion when planned quantity is produced; force-complete option available
6. **Cancel** — Full reversal: all reservations released, all allocated stock returned

**Material Traceability**
- Each material allocation is logged with the user, date, and items issued
- Serial numbers tracked individually from stock → allocated → consumed/returned
- Lot quantities tracked through the same lifecycle

**Manufacturing Documents**
- **Material Requirement Sheet (MRS)** — planning document showing required, allocated, and remaining quantities per component; designed for pre-production review
- **Material Issue Slip (MIS)** — physical handover document per allocation; includes serial/lot numbers issued, three-field signature block (Prepared By / Issued By / Received By); standard in SAP/Odoo-class ERP

**Material Returns**
- Return unused materials back to stock from the shop floor
- Serial numbers returned to "available" status

---

### 6. CRM

A structured sales pipeline — not just a contact list.

**Pipeline & Leads**
- Kanban-style pipeline view with drag-and-drop stage reordering
- Lead status: Active, Won, Lost (with lost reason)
- Expected revenue, probability, and expected close date per lead
- Won revenue tracking

**Stages**
- Fully configurable pipeline stages per company
- Win/loss flags per stage for reporting

**Lead Management**
- Lead source tracking (direct, referral, marketing, external integrations)
- Assignment to team members
- Priority levels (low, medium, high, urgent)
- Tags for flexible categorisation
- Product interest tracking

**Lead History & Activities**
- Full timeline: stage changes, notes, status updates, activity completion, quotations linked, customer conversion
- Activity types: call, email, meeting, todo — with due dates, outcomes, and completion tracking
- File attachments on activities

**Lead Conversion**
- Convert a won lead directly to a Customer record — no re-entering data

**Integrations**
- **Indiamart webhook** — leads from Indiamart flow directly into the CRM pipeline automatically
- Webhook framework for additional integrations

---

### 7. Users, Roles & Access Control

Enterprise-grade permissions without the enterprise complexity.

**Custom Roles**
- Create any number of roles per company (e.g., Sales Executive, Store Manager, Production Supervisor)
- Admin flag for full-access roles

**Feature-Level Permissions**
- Permissions are assigned at the feature level — not just module level
- Example: a user can have read access to Sales Orders but not write access; can confirm but not cancel
- Every sensitive operation (confirm, cancel, import, export, allocate, produce) has its own permission key

**Teams**
- Group users into teams
- Team membership management

**Access in practice:** The UI conditionally shows/hides buttons and actions based on the logged-in user's permissions — not just server-side enforcement but also clean UI — users never see actions they can't perform.

---

### 8. Dashboard & Analytics

- Business summary at a glance (orders, leads, stock, production)
- Operator-specific summary (relevant to the user's role)
- Sales by month (trend chart)
- Top customers by revenue
- Leads by month (pipeline health)

---

### 9. Activities & Task Management

Across the platform, activities can be logged against any entity.

- Types: call, email, meeting, todo
- Priority levels, due dates, due times
- Assignment to users
- Outcome and completion tracking
- File attachments
- Status: pending, in progress, completed, cancelled, skipped

---

### 10. Subscriptions & Billing

- Multi-tier subscription plans
- Monthly and annual billing cycles
- Trial and pilot period support
- Per-module activation — companies only pay for what they use
- Razorpay integration for payment processing
- User seat management with flexible seat limits
- Subscription status tracking (trial, active, past due, cancelled, suspended)

---

### 11. Document Management

- PDF generation: Sales Orders, Purchase Orders, Delivery Notes, Material Requirement Sheets, Material Issue Slips
- Email delivery of documents directly from the system
- File attachments on activities and entities
- Consistent document design with company branding (logo, company details)

---

### 12. Audit Trails

Every transactional module has a complete, immutable history:
- Every status change is recorded
- User and timestamp on every entry
- Event type classification (created, confirmed, cancelled, note added, stage changed, allocated, output recorded, etc.)
- No record is destroyed — cancelled items remain visible in history

---

## Technical Architecture

| Layer | Technology |
|---|---|
| Framework | Custom TinyPHP MVC (lightweight, purpose-built) |
| Language | PHP 8.2+ |
| Database | MySQL with multi-DB connection support |
| Templating | Laravel Blade (server-side rendering) |
| Authentication | JWT (API) + Session (web) |
| PDF engine | mPDF |
| Multi-tenancy | Per-company data isolation, company-scoped all queries |
| Webhooks | Inbound webhook framework with token validation and logging |

---

## Who Is This For?

Zentraq is built for businesses that buy or make physical products and need visibility across the full operation. It hits the sweet spot between a basic accounting tool (too shallow) and SAP/Oracle (too expensive and complex).

---

### Small & Mid-Size Manufacturers

**The fit:** Companies that make or assemble products, manage raw material stock, and need to track what went into each finished unit.

**The pain Zentraq solves:**
- "We don't know how much raw material we have until someone physically counts it."
- "We can't trace which batch of material went into a customer's product."
- "Production and warehouse don't talk — stock gets over-committed."

**What they get:** Full BOM-to-production workflow, serial and lot traceability, material allocation slips for the shop floor, automatic stock reservation on confirmation.

---

### Trading & Distribution Companies

**The fit:** Businesses that buy from suppliers and sell to customers, with stock managed across one or more locations.

**The pain Zentraq solves:**
- "We don't know real available stock — only on-hand quantity, not what's already committed."
- "We can't track individual units when a customer has a warranty claim."
- "Purchasing, warehouse, and sales are on spreadsheets that don't sync."

**What they get:** Live available stock (on-hand minus reservations), serial number tracking from receipt to dispatch, full PO-to-GRN-to-SO-to-delivery traceability.

---

### Industrial Equipment & Machinery Businesses

**The fit:** Companies dealing in capital goods where each unit has a serial number, and after-sales service matters.

**The pain Zentraq solves:**
- "We can't tell which serial number was delivered to which customer without digging through spreadsheets."
- "Warranty claims take days to resolve because traceability doesn't exist."

**What they get:** Serial number assigned at receipt, visible on sales orders and deliveries, traceable back to the specific GRN and supplier.

---

### Paint, Chemical & Batch-Process Manufacturers

**The fit:** Companies producing in batches where lot expiry, quarantine, and quality hold matter.

**The pain Zentraq solves:**
- "We don't know which batch we're shipping until it's already out the door."
- "Quarantine stock gets picked and shipped accidentally."

**What they get:** Lot tracking with expiry dates, lot status management (quarantine, on hold, rejected), lot assignment on sales orders and GRNs, lot-level audit trail.

---

### Businesses with a Sales Team

**The fit:** Any business where multiple salespeople manage leads and convert them to orders.

**The pain Zentraq solves:**
- "Leads come in from Indiamart and sit in a shared inbox — no one knows who picked it up."
- "We can't see the pipeline — just a flat list of customers."
- "Quotes get created in Word, sent by email, and then lost."

**What they get:** Automated Indiamart lead capture, Kanban pipeline, quotations inside the system, lead-to-customer-to-order conversion in one flow.

---

## Why Zentraq Is a Game-Changer for These Businesses

Most businesses at this size run on a combination of: a basic accounting package, WhatsApp for warehouse coordination, spreadsheets for inventory, and memory for production planning. The result is stock surprises, missed deliveries, untraceable defects, and a founder who can never confidently answer "what do we have in stock right now?"

Zentraq replaces that patchwork with a single system where:

**1. Stock is always accurate.**
Not just on-hand quantity — actual available quantity after reservations. Sales, purchasing, and manufacturing all talk to the same number.

**2. Traceability is built in, not bolted on.**
Serial and lot tracking is native to the data model — not a tag or a note field. Every unit can be traced from supplier receipt to customer delivery, through any production step in between.

**3. Production is a managed process, not a manual one.**
BOMs, material requirements, allocation slips, output recording — the shop floor has the documents it needs, and the system reflects reality. No more "how much raw material do we have for this order?" answered by walking to the warehouse.

**4. The sales pipeline is connected to operations.**
A won deal in CRM can become a quotation, then a sales order, then a delivery — all in Zentraq. Stock is reserved the moment the order is confirmed. The warehouse knows what to pick without a phone call.

**5. Everyone sees only what they need.**
The permissions system is granular enough to give a warehouse operator access to adjustments without touching sales, a production supervisor access to manufacturing without touching CRM, and a salesperson access to their own orders without seeing purchasing. No training cost on "don't click that."

**6. The audit trail never lies.**
Every action is logged — who did it, when, and what changed. For disputes, compliance, quality investigations, or simply answering "why did this order go wrong?" — the history is always there.

---

## Comparison to Alternatives

| | Zentraq | Tally/QuickBooks | Zoho Inventory | SAP B1 |
|---|---|---|---|---|
| Serial & Lot tracking | Full lifecycle | Basic | Basic | Full lifecycle |
| Manufacturing BOM | Full workflow | None | None | Full workflow |
| CRM with pipeline | Built in | None | Add-on | Add-on |
| Stock reservations | Native | None | Partial | Native |
| Custom roles | Granular | Basic | Basic | Granular |
| PDF + Email docs | Native | Native | Native | Native |
| Setup complexity | Low | Very low | Low | Very high |
| Cost | SaaS (affordable) | One-time | SaaS (mid) | Very high |
| Customisation | High (purpose-built) | Low | Medium | Very high (SAP consultants) |

---

*Zentraq gives growing businesses the operational depth of an enterprise ERP at a fraction of the complexity and cost — built specifically for the way product businesses in this market actually operate.*
