@extends('layouts.front')
@section('title', 'Welcome')

@section('content')
<div data-bs-spy="scroll" data-bs-target="#navbarSupportedContent" class="scrollspy-example">

  <!-- ===================== HERO ===================== -->
  <section id="landingHero">
    <div id="landingHero" class="section-py landing-hero position-relative">
      <div style="background-image: linear-gradient(180deg,#e3ebff,#fff);z-index: 0;pointer-events: none;display: flex;position: absolute;inset: 0;overflow: hidden;">
        <img src="{{asset('/assets/img/hero-bg.svg')}}" alt="hero background" style="object-fit: cover;min-width: 100%;min-height: 100%;max-width: 100%;display: inline-block;object-position: top;" />
      </div>
      <div class="container">
        <div class="hero-text-box text-center position-relative">
          <h1 class="text-primary hero-title display-6 fw-extrabold mb-12 mt-10">
            Stop Managing Your Business<br class="d-none d-lg-block" /> in Excel and WhatsApp</h1>
          <h2 class="hero-sub-title h6 mt-12 mb-8" style="letter-spacing: 0.5px;font-size: 17px;">
            Manage inventory, purchasing, production, sales orders and customer relationships from a single system. <br class="d-none d-lg-block" />
            Built for manufacturers, retailers, distributors and product-based businesses.
          </h2>
          <div class="mb-12 pb-4">
            <a href="/register/" class="btn btn-primary w-px-250 py-2">Start Free Trial</a>
            <p class="hero-sub-title h6 mt-4 b-0">No credit card required &nbsp;·&nbsp; Ready in minutes</p>
          </div>          
        </div>

        <div id="heroDashboardAnimation" class="hero-animation-img mt-6">
          <div id="heroAnimationImg" class="position-relative hero-dashboard-img">
            <img src="{{asset('/assets/img/front-pages/landing-page/hero-dashboard-light.png')}}" alt="Zentraq dashboard" class="animation-img" style="border-radius: 6px;" />            
          </div>
        </div>
      </div>
    </div>
    <div class="landing-hero-blank"></div>
  </section>
  <!-- Hero: End -->

  <!-- ===================== PAIN POINTS ===================== -->
  <section class="section-py" style="background-image: linear-gradient(360deg, #e3ebff, #fff);">
    <div class="container">
      <h4 class="text-center mb-4 fs-2rem fw-extrabold">Does this sound familiar?</h4>
      <p class="text-center text-muted mb-12 pb-2">Most growing businesses hit the same walls before switching to a connected system.</p>

      <div class="row gx-0 gy-10 g-sm-12 text-center">

        <div class="col-lg-4 col-sm-6">
          <div class="mb-4 text-danger"><i class="bx bx-error-circle" style="font-size:3rem;"></i></div>
          <h5 class="fw-semibold mb-2">Inventory never matches reality</h5>
          <p class="text-muted small px-4">Stock counts in your system don't match what's actually on the shelf.</p>
        </div>

        <div class="col-lg-4 col-sm-6">
          <div class="mb-4 text-danger"><i class="bx bx-time-five" style="font-size:3rem;"></i></div>
          <h5 class="fw-semibold mb-2">Production gets delayed</h5>
          <p class="text-muted small px-4">Materials aren't available when production starts — because no one reserved them.</p>
        </div>

        <div class="col-lg-4 col-sm-6">
          <div class="mb-4 text-danger"><i class="bx bx-spreadsheet" style="font-size:3rem;"></i></div>
          <h5 class="fw-semibold mb-2">Stock tracked in spreadsheets</h5>
          <p class="text-muted small px-4">Multiple Excel files, no single source of truth, and constant reconciliation headaches.</p>
        </div>

        <div class="col-lg-4 col-sm-6">
          <div class="mb-4 text-danger"><i class="bx bx-phone-call" style="font-size:3rem;"></i></div>
          <h5 class="fw-semibold mb-2">Vendor follow-ups fall through</h5>
          <p class="text-muted small px-4">No trail of what was ordered, what arrived, and what's still pending with suppliers.</p>
        </div>

        <div class="col-lg-4 col-sm-6">
          <div class="mb-4 text-danger"><i class="bx bx-cart-alt" style="font-size:3rem;"></i></div>
          <h5 class="fw-semibold mb-2">Sales orders get missed</h5>
          <p class="text-muted small px-4">Orders buried in WhatsApp chats or inboxes, leading to delays and unhappy customers.</p>
        </div>

        <div class="col-lg-4 col-sm-6">
          <div class="mb-4 text-danger"><i class="bx bx-data" style="font-size:3rem;"></i></div>
          <h5 class="fw-semibold mb-2">Data split across everything</h5>
          <p class="text-muted small px-4">Inventory in one sheet, sales in another, purchases in WhatsApp — nothing connected.</p>
        </div>

      </div>

      <div class="text-center mt-12 pt-4">
        <p class="fw-semibold fs-5 mb-6">Zentraq solves all of them — in one connected system.</p>
        <a href="#landingFeatures" class="btn btn-outline-primary px-8">See how it works</a>
      </div>
    </div>
  </section>
  <!-- Pain Points: End -->

  <!-- ===================== FEATURES ===================== -->
  <section id="landingFeatures" class="section-py landing-features">
    <div class="container">

      <h4 class="text-center mb-4 fs-2rem">
        <span class="position-relative fw-extrabold z-1">Built for how your business actually runs
          <img src="{{asset('/assets/img/front-pages/icons/section-title-icon.png')}}" alt="" class="section-title-img position-absolute object-fit-contain bottom-0 z-n1" />
        </span>
      </h4>
      <p class="text-center mb-16 pb-4 text-muted">Every module is connected — so your inventory, purchasing, manufacturing and sales always stay in sync.</p>

      <!-- Feature 1: Inventory — image left -->
      <div class="row align-items-center g-8 g-lg-14 flex-lg-row-reverse pt-12 mb-12 pb-12">
        <div class="col-lg-5">
          <span class="badge bg-label-primary mb-4 px-3 py-2 rounded-pill" style="font-size:.8rem;letter-spacing:.5px;">Inventory Management</span>
          <h4 class="fw-extrabold mb-4">Always know exactly what's in stock</h4>
          <p class="text-muted mb-6">Real-time inventory that reflects every movement — purchases, sales, production and manual adjustments — the moment it happens.</p>
          <ul class="list-unstyled mb-0">
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Available quantity automatically accounts for reservations and pending receipts.</span>
            </li>
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Track serial numbers and batches across multiple warehouses and locations.</span>
            </li>
            <li class="d-flex align-items-start gap-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Complete stock movement history — every receipt, issue and transfer with a full audit trail.</span>
            </li>
          </ul>
        </div>
        <div class="col-lg-7">
          <div class="rounded-3 overflow-hidden border shadow-sm" style="height:500px;">
            <img src="{{asset('/assets/img/front-pages/landing-page/inv-module.png')}}" alt="Inventory Management" style="width:100%;height:100%;object-fit:cover;object-position:top left;display:block;" />
          </div>
        </div>
      </div>

      <!-- Feature 2: Manufacturing — image right -->
      <div class="row align-items-center g-8 g-lg-14 pt-12 mb-12 pb-12">
        <div class="col-lg-5">
          <span class="badge bg-label-success mb-4 px-3 py-2 rounded-pill" style="font-size:.8rem;letter-spacing:.5px;">Manufacturing</span>
          <h4 class="fw-extrabold mb-4">Plan and run production without the guesswork</h4>
          <p class="text-muted mb-6">Know if you have the materials before you start. Allocate, produce and record output — all in one connected flow.</p>
          <ul class="list-unstyled mb-0">
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Build multi-level BOMs and check material availability before confirming an order.</span>
            </li>
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Allocate raw materials, record production output and track scrap in one flow.</span>
            </li>
            <li class="d-flex align-items-start gap-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Full traceability from raw material receipt to finished goods shipment.</span>
            </li>
          </ul>
        </div>
        <div class="col-lg-7">
          {{-- TODO: replace with actual manufacturing screenshot --}}
          <div class="rounded-3 overflow-hidden border shadow-sm" style="height:500px;">
            <img src="{{asset('/assets/img/front-pages/landing-page/manufacturing-module.png')}}" alt="Manufacturning" style="width:100%;height:100%;object-fit:cover;object-position:top left;display:block;" />
          </div>
        </div>
      </div>

      <!-- Feature 3: Purchasing — image left -->
      <div class="row align-items-center g-8 g-lg-14 flex-lg-row-reverse pb-12 mb-12">
        <div class="col-lg-5">
          <span class="badge bg-label-warning mb-4 px-3 py-2 rounded-pill" style="font-size:.8rem;letter-spacing:.5px;">Purchasing</span>
          <h4 class="fw-extrabold mb-4">Every supplier order tracked from PO to shelf</h4>
          <p class="text-muted mb-6">Stop chasing suppliers on WhatsApp. Raise POs, track approvals and record receipts — stock updates the moment goods arrive.</p>
          <ul class="list-unstyled mb-0">
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Raise purchase orders, track status and record goods receipts with one click.</span>
            </li>
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Inventory updates automatically the moment goods are received — no manual entry.</span>
            </li>
            <li class="d-flex align-items-start gap-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Full vendor history and pending order visibility across all suppliers in one place.</span>
            </li>
          </ul>
        </div>
        <div class="col-lg-7">
          {{-- TODO: replace with actual purchasing screenshot --}}
          <div class="rounded-3 overflow-hidden border shadow-sm" style="height:500px;">
            <img src="{{asset('/assets/img/front-pages/landing-page/po-module.png')}}" alt="Purchasing" style="width:100%;height:100%;object-fit:cover;object-position:top left;display:block;" />
          </div>
        </div>
      </div>

      <!-- Feature 4: Sales Orders — image right -->
      <div class="row align-items-center g-8 g-lg-14 mb-12 pb-12">
        <div class="col-lg-5">
          <span class="badge bg-label-info mb-4 px-3 py-2 rounded-pill" style="font-size:.8rem;letter-spacing:.5px;">Sales Orders</span>
          <h4 class="fw-extrabold mb-4">Quote, confirm and deliver — without switching tools</h4>
          <p class="text-muted mb-6">From the first enquiry to the last delivery, every step is in one place. No missed orders, no over-selling, no surprises.</p>
          <ul class="list-unstyled mb-0">
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Create quotations, confirm sales orders and auto-reserve inventory in seconds.</span>
            </li>
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Manage partial deliveries and track fulfilment status per order line.</span>
            </li>
            <li class="d-flex align-items-start gap-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Stock reservation prevents over-selling — available qty always reflects real uncommitted stock.</span>
            </li>
          </ul>
        </div>
        <div class="col-lg-7">
          {{-- TODO: replace with actual purchasing screenshot --}}
          <div class="rounded-3 overflow-hidden border shadow-sm" style="height:500px;">
            <img src="{{asset('/assets/img/front-pages/landing-page/sales-module.png')}}" alt="Sales" style="width:100%;height:100%;object-fit:cover;object-position:top left;display:block;" />
          </div>
        </div>
      </div>

      <!-- Feature 5: CRM — image left -->
      <div class="row align-items-center g-8 g-lg-14 flex-lg-row-reverse">
        <div class="col-lg-5">
          <span class="badge bg-label-danger mb-4 px-3 py-2 rounded-pill" style="font-size:.8rem;letter-spacing:.5px;">CRM & Pipeline</span>
          <h4 class="fw-extrabold mb-4">Never lose a lead — no matter where it comes from</h4>
          <p class="text-muted mb-6">Leads from web forms, marketplaces or your own API flow straight into your pipeline. Respond faster, track every follow-up and convert to a quotation without leaving Zentraq.</p>
          <ul class="list-unstyled mb-0">
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Connect any lead source via API — enquiries auto-create in your CRM, zero manual entry.</span>
            </li>
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Kanban pipeline view — track every lead from first contact to closed deal.</span>
            </li>
            <li class="d-flex align-items-start gap-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Convert a lead directly to a quotation or sales order in one click.</span>
            </li>
          </ul>
        </div>
        <div class="col-lg-7">
          {{-- TODO: replace with actual CRM screenshot --}}
          <div class="rounded-3 overflow-hidden border shadow-sm" style="height:500px;">
            <img src="{{asset('/assets/img/front-pages/landing-page/crm-module.png')}}" alt="CRM" style="width:100%;height:100%;object-fit:cover;object-position:top left;display:block;" />
          </div>
        </div>
      </div>

      <!-- Feature 6: Reporting — image right -->
       <!--
      <div class="row align-items-center g-8 g-lg-14">
        <div class="col-lg-6">
          <span class="badge bg-label-secondary mb-4 px-3 py-2 rounded-pill" style="font-size:.8rem;letter-spacing:.5px;">Reporting & Audit Trail</span>
          <h4 class="fw-extrabold mb-4">See your entire business at a glance</h4>
          <p class="text-muted mb-6">Live reports across every module — no exports, no pivot tables. Every transaction is timestamped and traceable.</p>
          <ul class="list-unstyled mb-0">
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Inventory valuation, purchase history, sales performance and production output — all live.</span>
            </li>
            <li class="d-flex align-items-start gap-3 mb-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Every record shows who created it, when, and what changed — full accountability.</span>
            </li>
            <li class="d-flex align-items-start gap-3">
              <i class="bx bx-check-circle text-primary mt-1 flex-shrink-0" style="font-size:1.1rem;"></i>
              <span class="text-muted">Role-based access ensures the right people see exactly what they need — nothing more.</span>
            </li>
          </ul>
        </div>
        <div class="col-lg-6">
          {{-- TODO: replace with actual reporting screenshot --}}
          <div class="rounded-3 border d-flex align-items-center justify-content-center" style="height:380px;background:linear-gradient(145deg,#f5f5f5,#e8e8e8);">
            <div class="text-center text-secondary" style="opacity:.35;">
              <i class="bx bx-desktop" style="font-size:4rem;"></i>
              <div class="mt-2 fw-medium small">Reporting Screenshot</div>
            </div>
          </div>
        </div>
      </div>
    -->

    </div>
  </section>
  <!-- Features: End -->

  <!-- ===================== HOW IT WORKS ===================== -->
  <section id="landingHowItWorks" class="pt-0 section-py bg-body1">
    <div class="container">
      <h4 class="text-center mb-6 fw-extrabold fs-2rem">Up and running in three steps</h4>
      <p class="text-center text-muted mb-12 pb-6">No implementation consultants. No months of onboarding.</p>
      <div class="row g-6 g-lg-12 align-items-start">
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-label-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">1</span>
          </div>
          <h5 class="fw-semibold mb-2">Create Your Company</h5>
          <p class="text-muted">Set up your company, invite users and configure your locations and teams.</p>
        </div>
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-label-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">2</span>
          </div>
          <h5 class="fw-semibold mb-2">Import Products & Customers</h5>
          <p class="text-muted">Upload products, vendors and customer records using built-in import tools.</p>
        </div>
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-label-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">3</span>
          </div>
          <h5 class="fw-semibold mb-2">Start Managing Operations</h5>
          <p class="text-muted">Run purchasing, inventory, manufacturing and sales from one connected platform.</p>
        </div>
      </div>
    </div>
  </section>
  <!-- How it works: End -->

  <!-- ===================== CTA BANNER ===================== -->
  <section id="landingCTA" class="section-py position-relative overflow-hidden" style="background-image: linear-gradient(180deg, #e3ebff, #fff);">    
    <div class="container">
      <div class="row align-items-center g-6">
        <div class="col-lg-6">
          <h3 class="fw-extrabold mb-6 fs-2rem">Every day in spreadsheets is a day your stock data is wrong.</h3>
          <p class="text-muted mb-12">
            Switch to a system that updates in real time — and takes hours, not months, to go live.
          </p>
          <div class="d-flex flex-column flex-sm-row gap-3">
            <a href="/register/" class="btn btn-outline-primary">Start Free Trial</a>
            <a href="#landingContact" class="btn btn-outline-secondary">Talk to us</a>
          </div>
        </div>
        <div class="col-lg-6 text-center d-none d-lg-block">
          <img src="{{asset('/assets/img/front-pages/landing-page/dashboard-1.png')}}" alt="Zentraq dashboard preview" class="img-fluid" style="border-radius: 6px;" />          
        </div>
      </div>
    </div>
  </section>
  <!-- CTA Banner: End -->

  <!-- ===================== FAQ ===================== -->
  <section id="landingFAQ" class="section-py bg-body1">
    <div class="container">
      <h4 class="text-center fw-extrabold mb-4 fs-2rem">Frequently asked questions</h4>
      <p class="text-center text-muted mb-12">Can't find an answer? <a href="#landingContact">Contact us</a>.</p>

      <div class="row g-6">
        <div class="col-lg-12">
          <div class="accordion" id="faqAccordionLeft">

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Is Zentraq suitable for small manufacturers?</button>
              </h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">
                  Yes. Zentraq is designed specifically for small and mid-sized manufacturers that need better control over inventory, purchasing, production, and sales without the complexity or cost of traditional enterprise ERP systems. You can manage BOMs, manufacturing orders, material allocation, production tracking, and stock movements from a single platform.
                </div>
              </div>
            </div>

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">How long does it take to get started with Zentraq?</button>
              </h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">Most businesses can start using Zentraq within a few hours. You can import products, customers, and vendors, configure your locations, and begin managing inventory, sales, purchasing, and production without lengthy implementation projects.</div>
              </div>
            </div>

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">Can I control what users can access?</button>
              </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                  <div class="accordion-body text-muted">Yes. Zentraq includes role-based access control with customizable permissions. You can define exactly which users can view, create, edit, approve, or manage specific records and actions across the system.</div>
              </div>
            </div>

            <div class="accordion-item border rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Is Zentraq cloud-based?</button>
              </h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">Yes. Zentraq is a cloud-based ERP platform, so you can access your data securely from anywhere using a web browser. There is no server setup or infrastructure to maintain.</div>
              </div>
            </div>            

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">Does Zentraq integrate with Indiamart?</button>
              </h2>
              <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">Yes. Zentraq includes Indiamart lead integration, allowing enquiries from Indiamart to flow directly into your CRM pipeline. This helps your sales team respond faster, track lead progress, and convert enquiries into quotations and sales orders without manual data entry.</div>
              </div>
            </div>

            <div class="accordion-item border rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">How does stock reservation work?</button>
              </h2>
              <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">When a Sales Order or Manufacturing Order is confirmed, Zentraq automatically reserves the required inventory. This ensures that available stock reflects only what is truly uncommitted, helping prevent over-selling, stock shortages, and production delays. Reservations are released automatically when orders are completed or cancelled.</div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>
  <!-- FAQ: End -->

  <!-- ===================== CONTACT ===================== -->
  <section id="landingContact" class="section-py" style="background-image: url({{asset('/assets/img/hero-bg.svg')}});background-size: cover;background-position: top;">
    <div class="container">
      <h4 class="text-center fw-extrabold mb-4 fs-2rem">Talk to a Zentraq Specialist</h4>
      <p class="text-center text-muted mb-12">See how Zentraq can help streamline inventory, production, purchasing and sales operations.</p>
      <div class="row g-12 justify-content-center">
        <div class="col-lg-6">
          <div class="card shadow-none border h-100">
            <div class="card-body p-6">
              <form id="contactForm">
                <div class="mb-4">
                  <label class="form-label" for="contactName">Full Name</label>
                  <input type="text" id="contactName" name="name" class="form-control" placeholder="Your name" />
                </div>
                <div class="mb-4">
                  <label class="form-label" for="contactEmail">Work Email</label>
                  <input type="email" id="contactEmail" name="email" class="form-control" placeholder="email@zentraqone.com" />
                </div>
                <div class="mb-4">
                  <label class="form-label" for="contactCompany">Company</label>
                  <input type="text" id="contactCompany" name="company" class="form-control" placeholder="Your company name" />
                </div>
                <div class="mb-5">
                  <label class="form-label" for="contactMessage">Message</label>
                  <textarea id="contactMessage" name="message" class="form-control" rows="4" placeholder="Tell us about your business and what you're looking for…"></textarea>
                </div>
                <button type="submit" id="contactSubmitBtn" class="btn btn-primary w-100">Send message</button>
                <div id="contactFormMsg" class="mt-3 d-none"></div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-5 justify-content-center">
          <div class="d-flex align-items-start gap-4">
            <div class="avatar flex-shrink-0" style="width: 3rem;height: 3rem;">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-envelope"></i></span>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold">Email</h6>
              <p class="text-muted mb-0">hello@zentraqone.com</p>
            </div>
          </div>
          <div class="d-flex align-items-start gap-4">
            <div class="avatar flex-shrink-0" style="width: 3rem;height: 3rem;">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-phone"></i></span>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold">Phone</h6>
              <p class="text-muted mb-0">+91 74055 92302, +91 99748 73930</p>
            </div>
          </div>
          <div class="d-flex align-items-start gap-4">
            <div class="avatar flex-shrink-0" style="width: 3rem;height: 3rem;">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-time-five"></i></span>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold">Support Hours</h6>
              <p class="text-muted mb-0">Mon – Sat, 9 am – 6 pm</p>
            </div>
          </div>          
        </div>
      </div>
    </div>
  </section>
  <!-- Contact: End -->

</div>
@push('scripts')
<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var btn = document.getElementById('contactSubmitBtn');
    var msgBox = document.getElementById('contactFormMsg');

    btn.disabled = true;
    btn.textContent = 'Sending…';
    msgBox.className = 'mt-3 d-none';
    msgBox.textContent = '';

    fetch('/api/contact-inquiry', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
            name:    document.getElementById('contactName').value,
            email:   document.getElementById('contactEmail').value,
            company: document.getElementById('contactCompany').value,
            message: document.getElementById('contactMessage').value,
        })
    })
    .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
    .then(function(result) {
        if (result.ok) {
            msgBox.className = 'mt-3 alert alert-success py-2';
            msgBox.textContent = result.data.message || 'Message sent!';
            document.getElementById('contactForm').reset();
        } else {
            msgBox.className = 'mt-3 alert alert-danger py-2';
            msgBox.textContent = result.data.message || 'Something went wrong. Please try again.';
        }
    })
    .catch(function() {
        msgBox.className = 'mt-3 alert alert-danger py-2';
        msgBox.textContent = 'Network error. Please try again.';
    })
    .finally(function() {
        btn.disabled = false;
        btn.textContent = 'Send message';
    });
});
</script>
@endpush

@endsection
