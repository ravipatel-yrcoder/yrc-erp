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
      <h4 class="text-center mb-6 mt-6 fs-2rem">
        <span class="position-relative fw-extrabold z-1">Everything your business needs
          <img src="{{asset('/assets/img/front-pages/icons/section-title-icon.png')}}" alt="" class="section-title-img position-absolute object-fit-contain bottom-0 z-n1" />
        </span>
      </h4>
      <p class="text-center mb-12 pb-6 text-muted">One login. One platform. Every team aligned.</p>

      <div class="features-icon-wrapper row gx-0 gy-6 g-sm-12">

        <!-- Inventory -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-package" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Inventory Management</h5>
          <p class="features-icon-description">
            Track quantity, serial numbers and batches across warehouses with real-time availability and reservations.
          </p>
        </div>

        <!-- Manufacturing -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-package" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Manufacturing</h5>
          <p class="features-icon-description">
            Create BOMs, allocate materials, record production and maintain complete material traceability.
          </p>
        </div>

        <!-- Purchasing -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-receipt" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Purchasing</h5>
          <p class="features-icon-description">
            Manage vendors, purchase orders and goods receipts with full stock integration.
          </p>
        </div>

        <!-- Sales -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-cart-alt" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Sales Orders</h5>
          <p class="features-icon-description">
            Create quotations, process orders, reserve stock automatically and manage deliveries.
          </p>
        </div>

        <!-- CRM & Pipeline -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-user-pin" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">CRM & Pipeline</h5>
          <p class="features-icon-description">
            Capture leads, manage opportunities and convert prospects into customers.
          </p>
        </div>

        <!-- Multi-tenant -->
        <div class="col-lg-4 col-sm-6 text-center features-icon-box">
          <div class="mb-4 text-primary text-center">
            <i class="icon-base bx bx-buildings" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Reporting & Audit Trail</h5>
          <p class="features-icon-description">
            Monitor business performance and maintain a complete history of every transaction.
          </p>
        </div>        

      </div>
    </div>
  </section>
  <!-- Features: End -->

  <!-- ===================== HOW IT WORKS ===================== -->
  <section id="landingHowItWorks" class="section-py bg-body1">
    <div class="container">
      <h4 class="text-center mb-6 fw-extrabold fs-2rem">Up and running in three steps</h4>
      <p class="text-center text-muted mb-12 pb-6">No implementation consultants. No months of onboarding.</p>
      <div class="row g-6 g-lg-12 align-items-start">
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">1</span>
          </div>
          <h5 class="fw-semibold mb-2">Create Your Company</h5>
          <p class="text-muted">Set up your company, invite users and configure your locations and teams.</p>
        </div>
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">2</span>
          </div>
          <h5 class="fw-semibold mb-2">Import Products & Customers</h5>
          <p class="text-muted">Upload products, vendors and customer records using built-in import tools.</p>
        </div>
        <div class="col-md-4 text-center">
          <div class="mb-4">
            <span class="badge bg-primary rounded-circle p-3" style="font-size:1.25rem;width:3rem;height:3rem;display:inline-flex;align-items:center;justify-content:center;">3</span>
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
          <h3 class="fw-extrabold mb-6 fs-2rem">Stop Managing Your Business<br>Across Spreadsheets and WhatsApp</h3>
          <p class="text-muted mb-12">
            Bring inventory, purchasing, manufacturing, sales and CRM into one connected platform.
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
              <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordionRight">
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
              <p class="text-muted mb-0">+91 74055 92302</p>
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
