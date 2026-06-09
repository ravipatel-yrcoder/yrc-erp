@extends('layouts.front')
@section('title', 'Welcome')

@section('content')
<div data-bs-spy="scroll" data-bs-target="#navbarSupportedContent" class="scrollspy-example">

  <!-- ===================== HERO ===================== -->
  <section id="landingHero">
    <div id="landingHero" class="section-py landing-hero position-relative">
      <img src="{{asset('/assets/img/front-pages/backgrounds/hero-bg.png')}}" alt="hero background" class="position-absolute top-0 start-50 translate-middle-x object-fit-cover w-100 h-100" />
      <div class="container">
        <div class="hero-text-box text-center position-relative">
          <h1 class="text-primary hero-title display-6 fw-extrabold mb-12 mt-10">
            Run your entire operation<br class="d-none d-lg-block" />from one platform
          </h1>
          <h2 class="hero-sub-title h6 mt-12 mb-8" style="letter-spacing: 0.5px;font-size: 17px;">
            Zentraq gives growing businesses real-time control over Sales, Inventory, Manufacturing,<br class="d-none d-lg-block" />
            Purchase Orders, and Customer Relationships — all in one place.
          </h2>
          <div class="mb-12 pb-4">
            <a href="/register/" class="btn btn-primary w-px-250 py-2">Get started free</a>
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

  <!-- ===================== TRUSTED BY ===================== -->
  <section class="section-py bg-body d-none">
    <div class="container">
      <p class="text-center text-muted fw-medium mb-6 small text-uppercase letter-spacing-1">Trusted by fast-growing businesses</p>
      <div class="swiper" id="swiper-clients" data-swiper='{
        "slidesPerView": 2,
        "spaceBetween": 32,
        "autoplay": {"delay": 2500, "disableOnInteraction": false},
        "loop": true,
        "breakpoints": {
          "576": {"slidesPerView": 3},
          "992": {"slidesPerView": 5}
        }
      }'>
        <div class="swiper-wrapper align-items-center">
          <div class="swiper-slide text-center">
            <img src="{{asset('/assets/img/front-pages/branding/logo_1-light.png')}}" class="client-logo img-fluid" alt="client" data-app-light-img="front-pages/branding/logo_1-light.png" data-app-dark-img="front-pages/branding/logo_1-dark.png" style="max-height:40px;opacity:.65;" />
          </div>
          <div class="swiper-slide text-center">
            <img src="{{asset('/assets/img/front-pages/branding/logo_2-light.png')}}" class="client-logo img-fluid" alt="client" data-app-light-img="front-pages/branding/logo_2-light.png" data-app-dark-img="front-pages/branding/logo_2-dark.png" style="max-height:40px;opacity:.65;" />
          </div>
          <div class="swiper-slide text-center">
            <img src="{{asset('/assets/img/front-pages/branding/logo_3-light.png')}}" class="client-logo img-fluid" alt="client" data-app-light-img="front-pages/branding/logo_3-light.png" data-app-dark-img="front-pages/branding/logo_3-dark.png" style="max-height:40px;opacity:.65;" />
          </div>
          <div class="swiper-slide text-center">
            <img src="{{asset('/assets/img/front-pages/branding/logo_4-light.png')}}" class="client-logo img-fluid" alt="client" data-app-light-img="front-pages/branding/logo_4-light.png" data-app-dark-img="front-pages/branding/logo_4-dark.png" style="max-height:40px;opacity:.65;" />
          </div>
          <div class="swiper-slide text-center">
            <img src="{{asset('/assets/img/front-pages/branding/logo_5-light.png')}}" class="client-logo img-fluid" alt="client" data-app-light-img="front-pages/branding/logo_5-light.png" data-app-dark-img="front-pages/branding/logo_5-dark.png" style="max-height:40px;opacity:.65;" />
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Trusted By: End -->

  <!-- ===================== FEATURES ===================== -->
  <section id="landingFeatures" class="section-py landing-features">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary">Core Modules</span>
      </div>
      <h4 class="text-center mb-2">
        <span class="position-relative fw-extrabold z-1">Everything your business needs
          <img src="{{asset('/assets/img/front-pages/icons/section-title-icon.png')}}" alt="" class="section-title-img position-absolute object-fit-contain bottom-0 z-n1" />
        </span>
      </h4>
      <p class="text-center mb-12 text-muted">
        One login. One platform. Every team aligned.
      </p>

      <div class="features-icon-wrapper row gx-0 gy-6 g-sm-12">

        <!-- Inventory -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-package" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Inventory Management</h5>
          <p class="features-icon-description">
            Track stock levels, serial &amp; lot numbers, locations, and movements in real time. Never oversell or run out unexpectedly.
          </p>
        </div>

        <!-- Purchase Orders -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-receipt" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Purchase Orders</h5>
          <p class="features-icon-description">
            Create, approve, and track POs from request to receipt. Auto-generated PO numbers, supplier history, and full audit trail.
          </p>
        </div>

        <!-- CRM -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-user-pin" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">CRM &amp; Leads</h5>
          <p class="features-icon-description">
            Manage leads through custom pipeline stages, log activities, and convert prospects into customers — all linked to your operations.
          </p>
        </div>

        <!-- Sales -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-cart-alt" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Sales &amp; Orders</h5>
          <p class="features-icon-description">
            Process customer orders, apply pricing rules, and automatically deduct from stock on fulfilment. Seamlessly connected to inventory.
          </p>
        </div>

        <!-- Multi-tenant -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-buildings" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Multi-Tenant</h5>
          <p class="features-icon-description">
            Each company gets its own isolated workspace. User roles, permissions, and data are fully separated — built for agencies and groups.
          </p>
        </div>

        <!-- Reporting -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-bar-chart-alt-2" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Reporting &amp; Analytics</h5>
          <p class="features-icon-description">
            Dashboards and reports that give you visibility into purchasing spend, inventory value, sales performance, and CRM pipeline health.
          </p>
        </div>

      </div>
    </div>
  </section>
  <!-- Features: End -->

  <!-- ===================== HOW IT WORKS ===================== -->
  <section id="landingHowItWorks" class="section-py bg-body">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary">How It Works</span>
      </div>
      <h4 class="text-center mb-2 fw-extrabold">Up and running in three steps</h4>
      <p class="text-center text-muted mb-12">No implementation consultants. No months of onboarding.</p>

      <div class="row g-6 g-lg-12 align-items-start">
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">1</span>
          </div>
          <h5 class="fw-semibold mb-2">Create your workspace</h5>
          <p class="text-muted">Register your company, invite your team, and set up user roles in minutes. Each company gets its own secure, isolated environment.</p>
        </div>
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">2</span>
          </div>
          <h5 class="fw-semibold mb-2">Configure your catalogue</h5>
          <p class="text-muted">Add your products, categories, and suppliers. Define units of measure, reorder points, and pricing — your way.</p>
        </div>
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">3</span>
          </div>
          <h5 class="fw-semibold mb-2">Start managing operations</h5>
          <p class="text-muted">Raise purchase orders, receive stock, process sales, and track leads — everything flowing through one connected platform.</p>
        </div>
      </div>
    </div>
  </section>
  <!-- How it works: End -->

  <!-- ===================== CTA BANNER ===================== -->
  <section id="landingCTA" class="section-py position-relative overflow-hidden">
    <img src="{{asset('/assets/img/front-pages/backgrounds/cta-bg-light.png')}}" alt="" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover z-n1" data-app-light-img="front-pages/backgrounds/cta-bg-light.png" data-app-dark-img="front-pages/backgrounds/cta-bg-dark.png" />
    <div class="container">
      <div class="row align-items-center g-6">
        <div class="col-lg-6">
          <h3 class="fw-extrabold mb-3">Ready to take control of your operations?</h3>
          <p class="text-muted mb-5">
            Join businesses that have replaced messy spreadsheets and disconnected tools with Zentraq. Get started today — it's free to try.
          </p>
          <div class="d-flex flex-column flex-sm-row gap-3">
            <a href="/register/" class="btn btn-primary btn-lg">Start for free</a>
            <a href="#landingContact" class="btn btn-outline-secondary btn-lg">Talk to us</a>
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
  <section id="landingFAQ" class="section-py bg-body">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary">FAQ</span>
      </div>
      <h4 class="text-center fw-extrabold mb-2">Frequently asked questions</h4>
      <p class="text-center text-muted mb-10">Can't find an answer? <a href="#landingContact">Contact us</a>.</p>

      <div class="row g-6">
        <div class="col-lg-6">
          <div class="accordion" id="faqAccordionLeft">

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                  Is Zentraq suitable for small businesses?
                </button>
              </h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">
                  Yes. Zentraq is designed to scale from a single-user operation up to multi-location enterprises. You only pay for what you need, and the interface is straightforward enough that you don't need a dedicated IT team.
                </div>
              </div>
            </div>

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                  Can I manage multiple companies in one account?
                </button>
              </h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">
                  Yes — Zentraq is built multi-tenant from the ground up. Each company has fully isolated data, users, and settings. Switch between companies from a single login.
                </div>
              </div>
            </div>

            <div class="accordion-item border rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                  Does it support serial and lot number tracking?
                </button>
              </h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                <div class="accordion-body text-muted">
                  Yes. Inventory can be tracked by serial number, lot/batch, or simple quantity, depending on the product type. Full traceability from purchase to sale.
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="col-lg-6">
          <div class="accordion" id="faqAccordionRight">

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                  How does purchasing connect to inventory?
                </button>
              </h2>
              <div id="faq4" class="accordion-collapse collapse show" data-bs-parent="#faqAccordionRight">
                <div class="accordion-body text-muted">
                  When you receive goods against a purchase order, inventory is automatically updated. No manual journal entries — everything is linked end-to-end.
                </div>
              </div>
            </div>

            <div class="accordion-item border mb-3 rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                  Is there an API?
                </button>
              </h2>
              <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordionRight">
                <div class="accordion-body text-muted">
                  Yes. Zentraq exposes a REST API with JWT authentication, allowing you to integrate with e-commerce platforms, accounting tools, and custom applications.
                </div>
              </div>
            </div>

            <div class="accordion-item border rounded">
              <h2 class="accordion-header">
                <button class="accordion-button fw-medium rounded collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                  How is my data kept secure?
                </button>
              </h2>
              <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordionRight">
                <div class="accordion-body text-muted">
                  All data is stored in isolated per-tenant databases. Access is protected by JWT tokens with short TTLs, and every API call is scoped to your company — other tenants cannot access your data.
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- FAQ: End -->

  <!-- ===================== CONTACT ===================== -->
  <section id="landingContact" class="section-py">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge bg-label-primary">Contact Us</span>
      </div>
      <h4 class="text-center fw-extrabold mb-2">Get in touch</h4>
      <p class="text-center text-muted mb-10">Have a question or want a personalised demo? We'd love to hear from you.</p>

      <div class="row g-6 justify-content-center">
        <div class="col-lg-5">
          <div class="card shadow-none border h-100">
            <div class="card-body p-6">
              <form id="contactForm">
                <div class="mb-4">
                  <label class="form-label" for="contactName">Full Name</label>
                  <input type="text" id="contactName" name="name" class="form-control" placeholder="John Smith" />
                </div>
                <div class="mb-4">
                  <label class="form-label" for="contactEmail">Work Email</label>
                  <input type="email" id="contactEmail" name="email" class="form-control" placeholder="john@company.com" />
                </div>
                <div class="mb-4">
                  <label class="form-label" for="contactCompany">Company</label>
                  <input type="text" id="contactCompany" name="company" class="form-control" placeholder="Acme Ltd." />
                </div>
                <div class="mb-5">
                  <label class="form-label" for="contactMessage">Message</label>
                  <textarea id="contactMessage" name="message" class="form-control" rows="4" placeholder="Tell us about your business and what you're looking for…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send message</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-5 justify-content-center">
          <div class="d-flex align-items-start gap-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-envelope"></i></span>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold">Email</h6>
              <p class="text-muted mb-0">hello@zentraqone.com/p>
            </div>
          </div>
          <div class="d-flex align-items-start gap-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-phone"></i></span>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold">Phone</h6>
              <p class="text-muted mb-0">+91 74055 92302</p>
            </div>
          </div>
          <div class="d-flex align-items-start gap-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-time-five"></i></span>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold">Support Hours</h6>
              <p class="text-muted mb-0">Mon – Sat, 9 am – 6 pm</p>
            </div>
          </div>
          <div class="d-flex align-items-start gap-4">
            <div class="avatar flex-shrink-0">
              <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-shield-check"></i></span>
            </div>
            <div>
              <h6 class="mb-1 fw-semibold">Data Security</h6>
              <p class="text-muted mb-0">Your data is always isolated and encrypted</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Contact: End -->

</div>
@endsection
