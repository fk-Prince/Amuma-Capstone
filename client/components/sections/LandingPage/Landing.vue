  <template>
    <div class="landing">

      <!-- FLOATING HELP BUTTON -->
      <button class="help-btn">
        <span class="help-icon">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </span>
        <span class="help-label">Need help?</span>
      </button>

      <!-- HERO SECTION -->
      <section class="hero-section">
        <div class="blob blob-hero-1"></div>
        <div class="blob blob-hero-2"></div>
        <div class="hero-dot"></div>
        <div class="hero-inner">
          <div class="hero-left">
            <h1 class="hero-title">
              All-in-One <span class="accent">Caregiving</span><br />
              Management System
            </h1>
            <p class="hero-subtitle">
              Streamline home-care bookings, facility management, and patient
              monitoring — all from one unified platform built for modern care agencies.
            </p>
            <span class="subtitle-underline"></span>
            <div class="hero-cta">
              <button class="btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                Search Provider
              </button>
              <button class="btn-outline">Start free for agencies</button>
            </div>
            <div class="hero-badges">
              <span class="badge"><span class="badge-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>eMAR &amp; vitals</span>
              <span class="badge"><span class="badge-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>QR check-in</span>
              <span class="badge"><span class="badge-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>HIPAA Compliant</span>
              <span class="badge"><span class="badge-icon"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>24/7 Support</span>
            </div>
            <div class="hero-reviews">
              <div class="avatar-stack">
                <div class="avatar-placeholder" style="background:#f87171"></div>
                <div class="avatar-placeholder" style="background:#60a5fa"></div>
                <div class="avatar-placeholder" style="background:#34d399"></div>
                <div class="avatar-placeholder" style="background:#a78bfa"></div>
              </div>
              <div class="review-text">
                <div class="stars">★★★★★</div>
                <p><strong>4.9/5</strong> from 128+ reviews</p>
                <p class="muted">Loved by caregivers and agencies</p>
              </div>
            </div>
          </div>

          <!-- DASHBOARD IMAGE + OVERLAPPING CARDS -->
          <div class="hero-right">
            <div class="dash-wrap">
              <img :src="dashboardImg" alt="AMUMA dashboard" class="dashboard-img" />
              <div class="dash-overlay-cards">
                <div class="amuma-card">
                  <div class="home-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/></svg>
                  </div>
                  <div>
                    <div class="brand">Amuma</div>
                    <div class="tag">To nurture, to care, to love</div>
                  </div>
                </div>
                <div class="caregiver-card">
                  <div class="cg-top">
                    <div class="cg-avatar"></div>
                    <div>
                      <div class="cg-name">Bunny Wawa</div>
                      <div class="cg-role">Caregiver</div>
                      <span class="cg-badge">Available</span>
                    </div>
                  </div>
                  <div class="cg-rating"><span class="star">★</span> 4.9 (128 reviews)</div>
                  <div class="cg-btn-row">
                    <button class="cg-btn">View Agency</button>
                    <div class="cg-save-btn">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SOLUTIONS SECTION -->
      <section class="solutions-section">
        <div class="blob blob-solutions"></div>
        <p class="section-eyebrow">ONE PLATFORM</p>
        <h2 class="section-title">Modular Solutions for <span class="accent">Every Care</span> Model</h2>
        <p class="section-sub">Choose the modules your agency needs. Run home care, facility care, or both — AMUMA adapts to your exact workflow.</p>
        <div class="solutions-grid">
          <div class="solution-card">
            <div class="solution-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.8"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg></div>
            <h3>Home Care Services</h3>
            <p>Professional caregivers visit patients at home for personalized care, medication management, and daily living assistance.</p>
            <a class="explore-link">Explore <span class="arr">→</span></a>
          </div>
          <div class="solution-card">
            <div class="solution-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h6M3 15h6"/></svg></div>
            <h3>In-House Facility</h3>
            <p>Complete elderly care facility management with room assignments, 24/7 monitoring, and comprehensive patient care.</p>
            <a class="explore-link">Explore <span class="arr">→</span></a>
          </div>
          <div class="solution-card">
            <div class="solution-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <h3>Hybrid Solution</h3>
            <p>Seamlessly manage both home care services and facility operations from a single integrated platform.</p>
            <a class="explore-link">Explore <span class="arr">→</span></a>
          </div>
        </div>
      </section>

      <!-- FEATURES SECTION -->
      <section class="features-section">
        <div class="blob blob-features"></div>
        <div class="features-left">
          <p class="section-eyebrow">FEATURES</p>
          <h2 class="features-title">
            <span class="accent">Multi-<br/>branch</span><br />
            oversight<br />made simple.
          </h2>
          <p class="features-sub">Coordinate care across all locations from one dashboard</p>
          <a class="explore-link">Explore <span class="arr">→</span></a>
        </div>
        <div class="features-right">
          <div class="feature-card with-photo">
            <div class="feature-card-text">
              <div class="feature-tag">Schedule</div>
              <h4><span class="accent">Caregiver</span> scheduling that works</h4>
              <p>Assign shifts and manage availability seamlessly</p>
            </div>
            <div class="feature-card-photo">
              <img src="~/assets/images/Rectangle_12.png" alt="Caregiver scheduling" />
            </div>
          </div>
          <div class="feature-card with-photo">
            <div class="feature-card-text">
              <div class="feature-tag">Billing</div>
              <h4><span class="accent">Subscriptions</span> and billing plans</h4>
              <p>Handle payments and invoicing automatically</p>
            </div>
            <div class="feature-card-photo">
              <img src="~/assets/images/Rectangle_13.png" alt="Billing plans" />
            </div>
          </div>
          <div class="feature-card wide with-photo-row">
            <div class="feature-card-text">
              <div class="feature-tag">Secure</div>
              <h4><span class="accent">CCTV Solutions</span> coverage that captures</h4>
              <p>Integrated, seamless 24/7 security recording and retrieval</p>
            </div>
            <div class="feature-card-photo">
              <img src="~/assets/images/Rectangle_15.png" alt="CCTV care" />
            </div>
          </div>
        </div>
      </section>

      <!-- HOW IT WORKS -->
      <section class="how-section">
        <div class="blob blob-how"></div>
        <div class="how-left">
          <div class="step-card">
            <div class="step-num">01</div>
            <div class="step-content">
              <h4>Family books a visit</h4>
              <p>Via web portal. AMUMA auto-creates a patient profile.</p>
            </div>
          </div>
          <div class="step-card">
            <div class="step-num">02</div>
            <div class="step-content">
              <h4>Admission routes the request</h4>
              <p>Admission staff assigns a caregiver or admits into an available bed — with one click.</p>
            </div>
          </div>
          <div class="step-card">
            <div class="step-num">03</div>
            <div class="step-content">
              <h4>Care happens, billing follows</h4>
              <p>Nurses close shifts. Accounting auto-invoices. Family pays. Balance updates instantly.</p>
            </div>
          </div>
        </div>
        <div class="how-right">
          <p class="section-eyebrow">HOW IT WORKS</p>
          <h2>A <span class="accent">gentle<br />workflow</span> for<br />an intense<br />profession.</h2>
        </div>
      </section>

      <!-- TESTIMONIALS -->
      <section class="testimonials-section">
        <div class="blob blob-testimonials"></div>
        <div class="testimonials-header">
          <h2>Trusted by <span class="accent">Care Teams</span> Across the Philippines</h2>
          <p class="section-sub">— here's what agencies say after switching to AMUMA.</p>
        </div>
        <div class="testimonials-grid">
          <div class="testimonial-card" v-for="t in testimonials" :key="t.name">
            <div class="t-quote-icon">"</div>
            <div class="stars">★ ★ ★ ★ ★</div>
            <p class="quote" v-html="t.quote"></p>
            <div class="testimonial-footer">
              <div class="avatar-circle" :style="{ background: t.avatarColor }">
                <!-- <img v-if="t.avatar" :src="t.avatar" :alt="t.name" class="avatar-img" /> -->
                <!-- <span v-else>{{ t.initials }}</span> -->
              </div>
              <div>
                <p class="t-name">{{ t.name }}</p>
                <p class="t-role">{{ t.role }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- PRICING -->
      <section class="pricing-section">
        <div class="blob blob-pricing"></div>
        <p class="section-eyebrow">PRICING</p>
        <h2 class="section-title">One calm price. All the <span class="accent">features</span> you need.</h2>
        <div class="pricing-toggle">
          <span :class="{ active: !annually }">Monthly</span>
          <button class="toggle-btn" @click="annually = !annually">
            <span class="toggle-knob" :class="{ right: annually }"></span>
          </button>
          <span :class="{ active: annually }">Yearly</span>
          <span class="save-badge" v-if="annually">Save 20%</span>
        </div>
        <div class="pricing-grid">
          <div class="pricing-card">
            <span class="plan-badge">Plan A</span>
            <h3>Homecare Services</h3>
            <p class="plan-sub">Receive professional care and support services from the comfort of your home through scheduled home visits and personalized assistance.</p>
            <div class="plan-price">₱{{ annually ? '28000.00' : '2500.00' }}<span> / month</span></div>
            <button class="plan-btn-outline">Subscribe to Homecare Services →</button>
            <ul class="plan-features">
              <li>Daily home care visits</li>
              <li>Live-in caregivers</li>
              <li>Family portal</li>
            </ul>
          </div>
          <div class="pricing-card">
            <span class="plan-badge">Plan B</span>
            <h3>In-house Facility</h3>
            <p class="plan-sub">Access comprehensive healthcare and wellness services within our facility, equipped with professional staff and modern amenities.</p>
            <div class="plan-price">₱{{ annually ? '40000.00' : '3500.00' }}<span> / month</span></div>
            <button class="plan-btn-outline">Subscribe to In-house Facility →</button>
            <ul class="plan-features">
              <li>Facility Management</li>
              <li>Admission &amp; rooms</li>
              <li>eMAR &amp; vitals</li>
              <li>VIP CCTV access</li>
              <li>Family Portal</li>
              <li>VIP &amp; Common Rooms</li>
            </ul>
          </div>
          <div class="pricing-card featured">
            <div class="most-popular">Most Popular</div>
            <span class="plan-badge plan-badge-featured">Plan C</span>
            <h3>Hybrid</h3>
            <p class="plan-sub">Enjoy a complete care package that combines personalized homecare services with full access to our in-house healthcare facility.</p>
            <div class="plan-price">₱{{ annually ? '58000.00' : '4500.00' }}<span> / month</span></div>
            <button class="plan-btn-white">Subscribe to Hybrid →</button>
            <ul class="plan-features">
              <li>All Home Care features</li>
              <li>All Facility features</li>
              <li>All in one solution</li>
            </ul>
          </div>
        </div>
      </section>

      <!-- CTA BANNER -->
      <section class="cta-section">
        <div class="blob blob-cta"></div>
        <div class="cta-card">
          <div>
            <h2>Your team deserves<br />a calmer screen.</h2>
            <p>Start 14-days free trial today!</p>
          </div>
          <button class="cta-btn">
            Create your workspace
            <span class="cta-icon">↗</span>
          </button>
        </div>
      </section>

    </div>
  </template>

  <script setup lang="ts">
  import { ref } from 'vue'
  import dashboardImg from '~/assets/images/dashboard-preview.jpg'

  const annually = ref(false)

  const testimonials = [
    {
      name: 'Dr. Barbie Santos',
      role: 'Agency Director · CareFirst Home S...',
      initials: 'BS',
      avatarColor: '#f87171',
      quote: '"<span class=\'t-highlight\'>AMUMA</span> completely transformed how we run our home care operations. Scheduling used to take hours — now it takes minutes."'
    },
    {
      name: 'Aytisa Winner',
      role: 'Facility Administrator · Golden Year H...',
      initials: 'AW',
      avatarColor: '#60a5fa',
      quote: '"Managing 3 branches used to be a nightmare. With <span class=\'t-highlight\'>AMUMA</span>, I have a live dashboard for every location. Billing, admissions, staff — all in one place. I wish we found this sooner."'
    },
    {
      name: 'Azriel Makinis',
      role: 'Head Nurse · Malasakit Care Center',
      initials: 'AM',
      avatarColor: '#34d399',
      quote: '"As a nurse, I only see my patients and schedule — no clutter. The vitals logging and care notes are so easy to use, even on a busy shift. The team at <span class=\'t-highlight\'>AMUMA</span> really thought of everything."'
    },
    {
      name: 'Jack',
      role: 'Family Member',
      initials: 'J',
      avatarColor: '#a78bfa',
      quote: '"My mother is well taken care of. I can check her status, upcoming visits, and balance online anytime. Knowing our family is protected."'
    },
    {
      name: 'Maria Dela Cruz',
      role: 'Operations Manager · SunCare Agency',
      initials: 'MC',
      avatarColor: '#fb923c',
      quote: '"The QR check-in alone saved us so much paperwork. Our caregivers love it — they just scan and go. <span class=\'t-highlight\'>AMUMA</span> made our whole team faster."'
    },
    {
      name: 'Renz Paglinawan',
      role: 'Caregiver · HomeHeart Services',
      initials: 'RP',
      avatarColor: '#22d3ee',
      quote: '"I used to dread logging medications manually. With <span class=\'t-highlight\'>AMUMA</span>, eMAR is so fast and clear. I feel more confident giving care now."'
    },
    {
      name: 'Lorna Bautista',
      role: 'Billing Staff · Malasakit Care Center',
      initials: 'LB',
      avatarColor: '#4ade80',
      quote: '"Billing used to pile up at month-end. Now invoices go out automatically and families pay online. <span class=\'t-highlight\'>AMUMA</span> cut my overtime in half."'
    },
    {
      name: 'Engr. Ramon Sy',
      role: 'Owner · Tahanan Elderly Home',
      initials: 'RS',
      avatarColor: '#e879f9',
      quote: '"I was skeptical at first but our ROI was visible within 2 months. Less admin cost, fewer errors, happier staff. <span class=\'t-highlight\'>AMUMA</span> is worth every peso."'
    }
  ]
  </script>

  <style scoped>

  /* ── GLOBAL ── */
  .accent {
    background: linear-gradient(135deg, #60a5fa, #2563eb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .section-eyebrow {
    font-size: 0.72rem;
    letter-spacing: 0.14em;
    color: #3b82f6;
    font-weight: 700;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
  }
  .section-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #111827;
    margin-bottom: 1rem;
    line-height: 1.15;
  }
  .section-sub {
    color: #6b7280;
    font-size: 0.95rem;
    line-height: 1.7;
    margin-bottom: 3rem;
  }
  .explore-link {
    color: #3b82f6;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    text-decoration: none;
  }
  .explore-link .arr { display: inline-block; transition: transform 0.2s; }
  .explore-link:hover .arr { transform: translateX(4px); }

  /* ── BLOBS ── */
  .blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: 0.35;
    pointer-events: none;
    z-index: 0;
  }
  .blob-hero-1       { width: 520px; height: 520px; background: #93c5fd; top: -120px; right: -80px; }
  .blob-hero-2       { width: 320px; height: 320px; background: #c7d2fe; bottom: 0;   left: -60px; }
  .blob-solutions    { width: 600px; height: 400px; background: #bfdbfe; top: 50%; left: 50%; transform: translate(-50%,-50%); }
  .blob-features     { width: 500px; height: 500px; background: #a5f3fc; top: 0;    right: -150px; }
  .blob-how          { width: 400px; height: 400px; background: #c7d2fe; bottom: 0; left: -100px; }
  .blob-testimonials { width: 500px; height: 500px; background: #bfdbfe; top: 20%;  right: -150px; opacity: 0.25; }
  .blob-pricing      { width: 500px; height: 500px; background: #bfdbfe; top: 20%;  left: -150px; }
  .blob-cta          { width: 400px; height: 400px; background: #93c5fd; top: -50px; right: -100px; }

  /* ── FLOATING HELP ── */
.help-btn{
  position:fixed;
  bottom:24px;
  right:24px;
  z-index:999;
  width:52px;
  height:52px;
  border-radius:999px;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:0px;
  padding:0;
  border:1px solid rgba(255,255,255,.3);
  background:rgba(255,255,255,.9);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  box-shadow:
    0 8px 24px rgba(15,23,42,.10),
    0 2px 6px rgba(15,23,42,.06);
  overflow:hidden;
  cursor:pointer;
  transition:
    width .3s cubic-bezier(.4,0,.2,1),
    transform .25s ease,
    box-shadow .25s ease;
}

.help-btn:hover{
  width:145px;

  justify-content:flex-start;
  padding-left:9px;
  gap:8px;
  transform:translateY(-2px);

  box-shadow:
    0 12px 32px rgba(15,23,42,.14),
    0 4px 10px rgba(15,23,42,.08);
}


/* ICON */
.help-icon{
  width:34px;
  height:34px;
  min-width:34px;
  margin:0;
  border-radius:50%;
  background:linear-gradient(
    135deg,
    #60a5fa,
    #2563eb
  );

  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink: 0;
  color:white;
  box-shadow:
    0 4px 12px rgba(37,99,235,.25);
}

/* LABEL */
.help-label{
  white-space:nowrap;
  width:0; 
  font-size: 14px;
  font-weight: 500;
  letter-spacing: -0.02em;
  opacity:0;
  overflow:hidden;
  transform:translateX(8px);
  transition:all .25s ease;
}

.help-label{
  white-space:nowrap;
  width:0;
  opacity:0;
  overflow:hidden;
  transform:translateX(8px);
  transition:all .25s ease;
}

.help-btn:hover .help-label{
  width:80px;
  opacity:1;
  transform:translateX(0);
}

  /* ── SCROLLBAR HIDDEN ── */
  :global(html) { scrollbar-width: none; overflow-x: hidden; }
  :global(body) { overflow-x: hidden; }
  :global(html::-webkit-scrollbar) { display: none; }
  :global(body::-webkit-scrollbar) { display: none; }

  /* ── HERO ── */
  .hero-section {
    position: relative;
    overflow: visible;
    background: #F8FAFC;
    padding-top: 140px;
    padding-bottom: 160px;
  }
  .hero-dot {
    position: absolute;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #60a5fa;
    top: 9rem; left: 7%;
    z-index: 2;
    box-shadow: 0 0 25px rgba(96,165,250,.8);
    animation: pulseDot 2.8s infinite;
  }
  .hero-dot::before {
    content: '';
    position: absolute; inset: -12px;
    border-radius: 50%;
    background: rgba(96,165,250,.25);
    filter: blur(9px);
  }
  @keyframes pulseDot {
    0%,100% { transform: scale(1);   opacity: 1; }
    50%      { transform: scale(1.6); opacity: .65; }
  }

  .hero-inner {
    width: min(94%, 1600px);
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: clamp(2rem, 3vw, 4rem);
    padding-inline: 40px;
  }
  .hero-left  { flex: 0 0 50%; max-width: 600px; }
  .hero-right {
    flex: 1 1 55%;
    display: flex; justify-content: flex-end; align-items: flex-start;
    margin-top: -120px; overflow: visible;
  }

  .hero-title {
    font-size: clamp(3rem, 3.8vw, 3.8rem);
    font-weight: 900; line-height: .85;
    letter-spacing: -0.045em; max-width: 540px;
    margin-bottom: 1rem; margin-top: 2rem;
    text-shadow: 0 2px 10px rgba(15,23,42,.04);
  }
  .hero-subtitle { max-width: 580px; font-size: 0.8rem; line-height: 1.8; color: #64748b; }
  .subtitle-underline {
    display: block; width: 80px; height: 3px;
    background: #3b82f6; border-radius: 2px;
    margin: 0.85rem 0 1.75rem;
  }
  .hero-cta { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
  .btn-primary {
    display: flex; align-items: center; gap: 0.5rem;
    background: #3b82f6; color: white; border: none;
    padding: 0.75rem 1.5rem; border-radius: 10px;
    font-size: 0.92rem; font-weight: 700; cursor: pointer; transition: background 0.2s;
  }
  .btn-primary:hover { background: #2563eb; }
  .btn-outline {
    background: transparent; color: #3b82f6; border: 1px solid #3b82f6;
    padding: 0.75rem 1.5rem; border-radius: 10px;
    font-size: 0.92rem; font-weight: 700; cursor: pointer; transition: all 0.2s;
  }
  .btn-outline:hover { background: #3b82f6; color: #eff6ff; }

  .hero-badges { display: flex; flex-wrap: nowrap; gap: 0.2rem; margin-bottom: 2rem; }
  .badge {
    display: flex; align-items: center; gap: .60rem;
    background: transparent; padding: 0; border: none;
    box-shadow: none; color: #64748b; font-size: .7rem;
  }
  .badge-icon {
    width: 25px; height: 25px; border-radius: 50%;
    background: white; box-shadow: 0 8px 20px rgba(15,23,42,.08);
    display: flex; align-items: center; justify-content: center;
  }

  .hero-reviews { display: flex; align-items: center; gap: 0.85rem; }
  .avatar-stack { display: flex; }
  .avatar-placeholder {
    width: 36px; height: 36px; border-radius: 50%;
    border: 2.5px solid white; margin-left: -10px;
  }
  .avatar-placeholder:first-child { margin-left: 0; }
  .stars { color: #facc15; font-size: 1rem; margin-bottom: 0.15rem; }
  .review-text p { margin: 0; font-size: 0.83rem; color: #374151; }
  .review-text .muted { color: #9ca3af; font-size: 0.75rem; }

  /* ── DASHBOARD ── */
  .dash-wrap { position: relative; width: 100%; max-width: 1050px; overflow: visible; }
  .dashboard-img {
    width: 100%; top: 120%; border-radius: 20px; display: block;
    transform: perspective(1900px) rotateY(-15deg) rotateX(5deg);
    transform-origin: center;
    box-shadow: 0 25px 80px rgba(59,130,246,.15);
    position: relative; z-index: 1;
  }

  @keyframes floatA { 0%,100% { transform: translateY(0);    } 50% { transform: translateY(-10px); } }
  @keyframes floatB { 0%,100% { transform: translateY(0);    } 50% { transform: translateY(-14px); } }

  .dash-overlay-cards {
    position: absolute; left: 0; right: 0; bottom: -120px;
    display: flex; flex-direction: row; align-items: flex-end;
    gap: 12px; padding: 0 8px; z-index: 20;
  }

  .amuma-card {
    width: 190px; flex-shrink: 0; height: 220px;
    background: linear-gradient(135deg, #4f8dfd, #2563eb);
    border-radius: 22px; padding: 1.25rem;
    display: flex; flex-direction: column; justify-content: space-between;
    position: relative; overflow: hidden;
    box-shadow: 0 20px 50px rgba(37,99,235,.30);
    z-index: 20; animation: floatA 4s ease-in-out infinite;
  }
  .amuma-card::before {
    content: ''; position: absolute;
    width: 140px; height: 140px; border-radius: 50%;
    background: rgba(255,255,255,.08); top: -10px; right: -40px;
  }
  .amuma-card .home-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(10px);
  }
  .amuma-card .brand { color: white; font-weight: 800; font-size: 1.1rem; margin-top: 10px; }
  .amuma-card .tag   { color: rgba(255,255,255,0.75); font-size: 0.7rem; margin-top: 2px; }

  .caregiver-card {
    width: 240px; flex-shrink: 0;
    background: white; border: 1px solid #eef2f7;
    border-radius: 14px; padding: 16px;
    box-shadow: 0 12px 30px rgba(15,23,42,0.10);
    z-index: 25; animation: floatB 4.5s ease-in-out infinite; animation-delay: 0.4s;
  }
  .cg-top { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
  .cg-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #b45309);
    flex-shrink: 0; position: relative;
  }
  .cg-avatar::after {
    content: ''; position: absolute; bottom: -1px; right: -1px;
    width: 11px; height: 11px; border-radius: 50%;
    background: #22c55e; border: 2px solid white;
  }
  .cg-name  { font-size: 0.9rem; font-weight: 700; color: #0f172a; }
  .cg-role  { font-size: 0.72rem; color: #94a3b8; }
  .cg-badge {
    display: inline-block; margin-top: 3px;
    font-size: 0.65rem; font-weight: 700; color: #16a34a;
    background: #f0fdf4; padding: 2px 9px; border-radius: 20px;
  }
  .cg-rating { display: flex; align-items: center; gap: 5px; font-size: 0.78rem; color: #475569; margin-bottom: 10px; }
  .cg-rating .star { color: #facc15; }
  .cg-btn-row { display: flex; gap: 8px; }
  .cg-btn {
    flex: 1; background: #3b82f6; color: white; border: none;
    border-radius: 9px; padding: 9px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: background 0.2s;
  }
  .cg-btn:hover { background: #2563eb; }
  .cg-save-btn {
    width: 38px; flex-shrink: 0;
    border: 1px solid #e2e8f0; border-radius: 9px;
    background: white; color: #3b82f6;
    display: flex; align-items: center; justify-content: center; cursor: pointer;
  }

  /* ── SOLUTIONS ── */
  .solutions-section {
    position: relative; padding: 6rem 6%;
    text-align: center; background: white;
  }
  .solutions-section > *:not(.blob) { position: relative; z-index: 1; }
  .solutions-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.5rem; margin-top: 1rem; }
  .solution-card {
    border: 1px solid #e5e7eb; border-radius: 16px; padding: 2rem 1.5rem;
    text-align: left; transition: box-shadow 0.2s; background: white;
  }
  .solution-card:hover { box-shadow: 0 8px 32px rgba(59,130,246,0.1); }
  .solution-icon {
    width: 52px; height: 52px; background: #eff6ff; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;
  }
  .solution-card h3 { font-size: 1.05rem; font-weight: 700; color: #111827; margin-bottom: 0.75rem; }
  .solution-card p  { font-size: 0.88rem; color: #6b7280; line-height: 1.6; margin-bottom: 1.25rem; }

  /* ── FEATURES ── */
  .features-section {
    position: relative; display: flex; gap: 4rem;
    padding: 6rem 6%; background: #f1f5f9; align-items: center;
  }
  .features-section > *:not(.blob) { position: relative; z-index: 1; }
  .features-left { flex: 0 0 280px; }

  .features-title {
    font-size: clamp(2.8rem, 5vw, 5rem);
    font-weight: 900;
    color: #0f172a;
    line-height: 1.0;
    letter-spacing: -0.03em;
    margin-bottom: 1rem;
  }

  .features-sub { font-size: 0.9rem; color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6; }
  .features-right { flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; position: relative; }

  .feature-card {
    background: white; border-radius: 20px; border: 1px solid #e5e7eb;
    overflow: hidden; display: flex; flex-direction: column;
  }
  .feature-card.wide { grid-column: 1 / -1; }
  .feature-card.wide.with-photo-row { flex-direction: row; align-items: stretch; }

  .feature-card-text { padding: 1.5rem 1.5rem 1rem; flex-shrink: 0; }
  .feature-card.wide.with-photo-row .feature-card-text {
    flex: 0 0 46%; padding: 1.75rem;
    display: flex; flex-direction: column; justify-content: center;
  }

  .feature-card-photo { flex: 1; min-height: 185px; overflow: hidden; border-radius: 0 0 18px 18px; }
  .feature-card-photo img {
    width: 100%; height: 100%;
    object-fit: cover; object-position: center top; display: block;
  }
  .feature-card.wide.with-photo-row .feature-card-photo {
    border-radius: 0 18px 18px 0; min-height: 220px; min-width: 0;
  }
  .feature-card.wide.with-photo-row .feature-card-photo img { object-position: center center; }

  .feature-tag {
    font-size: 0.72rem; font-weight: 600; color: #6b7280;
    letter-spacing: 0.08em; margin-bottom: 0.75rem;
    display: flex; align-items: center; gap: 0.4rem;
  }
  .feature-tag::before {
    content: ''; display: inline-block;
    width: 8px; height: 8px; border-radius: 50%; background: #93c5fd;
    box-shadow: 0 0 0 0 rgba(59,130,246,.5);
    animation: pulseTagDot 2.4s ease-in-out infinite;
  }
  @keyframes pulseTagDot {
    0%,100% { box-shadow: 0 0 0 0 rgba(59,130,246,.5); background: #93c5fd; }
    50%      { box-shadow: 0 0 0 5px rgba(59,130,246,.0); background: #3b82f6; }
  }
  .feature-card h4 { font-size: 1.05rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
  .feature-card p  { font-size: 0.84rem; color: #6b7280; line-height: 1.6; margin: 0; }

  /* ── HOW IT WORKS ── */
  .how-section {
    position: relative; display: grid;
    grid-template-columns: 55% 45%;
    gap: 5rem; padding: 7rem 6%;
    background: #F8FAFC; align-items: center;
  }
  .how-section > *:not(.blob) { position: relative; z-index: 1; }

  .how-left { display: flex; flex-direction: column; gap: 1.25rem; }

  .how-right { display: flex; flex-direction: column; justify-content: center; }
  .how-right .section-eyebrow { margin-bottom: 1.25rem; }
  .how-right h2 {
    font-size: clamp(2.8rem, 5vw, 5rem);
    font-weight: 900; line-height: 1.0;
    color: #0f172a; letter-spacing: -0.03em;
  }

  .step-card {
    position: relative; overflow: hidden;
    display: flex; align-items: center; gap: 1.5rem;
    background: white; border: 1px solid #e8edf5;
    border-radius: 20px; padding: 1.75rem 2rem;
    transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
    box-shadow: 0 2px 12px rgba(15,23,42,.04);
  }
  .step-card:hover {
    transform: translateY(-4px);
    border-color: rgba(59,130,246,.3);
    box-shadow: 0 16px 40px rgba(59,130,246,.12);
  }
  .step-card::before {
    content: ''; position: absolute; inset: 0; border-radius: inherit;
    background: linear-gradient(135deg, rgba(59,130,246,.05), transparent 60%);
    opacity: 0; transition: opacity .3s ease; pointer-events: none;
  }
  .step-card:hover::before { opacity: 1; }

  .step-num {
    font-size: 3.5rem; font-weight: 900;
    color: #3b82f6; line-height: 1; flex-shrink: 0;
    min-width: 80px; text-align: center;
    opacity: 0.9;
  }
  .step-content h4 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.35rem; }
  .step-content p  { margin: 0; color: #64748b; line-height: 1.6; font-size: 0.9rem; }

  /* ── TESTIMONIALS ── */
  .testimonials-section {
    position: relative; padding: 6rem 6%;
    background: #F8FAFC;
  }
  .testimonials-section > *:not(.blob) { position: relative; z-index: 1; }

  .testimonials-header { margin-bottom: 3rem; }
  .testimonials-header h2 {
    font-size: clamp(1.8rem, 3vw, 2.5rem);
    font-weight: 900; color: #0f172a;
    margin-bottom: 0.5rem; line-height: 1.2;
  }
  .testimonials-header .section-sub { margin-bottom: 0; color: #64748b; }

  .testimonials-grid {
    display: flex;
    flex-direction: row;
    gap: 1.25rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 1rem;
    cursor: grab;
  }
  .testimonials-grid:active { cursor: grabbing; }
  .testimonials-grid::-webkit-scrollbar { display: none; }
  .testimonials-grid { scrollbar-width: none; }

  .testimonial-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 20px; padding: 1.75rem;
    display: flex; flex-direction: column; gap: 0.85rem;
    box-shadow: 0 2px 16px rgba(15,23,42,.05);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    flex: 0 0 300px;
    scroll-snap-align: start;
  }
  .testimonial-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(59,130,246,.10);
  }

  .t-quote-icon {
    font-size: 3rem; line-height: 1;
    color: #e2e8f0; font-family: Georgia, serif;
    margin-bottom: -0.5rem;
  }

  .testimonials-section .stars {
    color: #f59e0b; font-size: 0.85rem;
    letter-spacing: 0.15em;
  }

  .quote {
    font-size: 0.88rem; color: #374151;
    line-height: 1.75; flex: 1; margin: 0;
  }

  :deep(.t-highlight) {
    color: #3b82f6; font-weight: 700;
  }

  .testimonial-footer {
    background: #3b82f6; border-radius: 12px;
    padding: 0.85rem 1rem;
    display: flex; align-items: center; gap: 0.75rem;
    margin-top: auto;
  }
  .avatar-circle {
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.85rem; color: white;
    flex-shrink: 0; overflow: hidden;
    border: 2px solid rgba(255,255,255,0.3);
  }
  .avatar-img { width: 100%; height: 100%; object-fit: cover; }
  .t-name { font-size: 0.85rem; font-weight: 700; color: white; margin: 0; }
  .t-role { font-size: 0.7rem;  color: rgba(255,255,255,0.75); margin: 0; }

  /* ── PRICING ── */
  .pricing-section {
    position: relative; padding: 6rem 6%;
    background: white; text-align: center;
  }
  .pricing-section > *:not(.blob) { position: relative; z-index: 1; }
  .pricing-toggle {
    display: flex; align-items: center;
    justify-content: center; gap: 0.75rem;
    margin-bottom: 3rem; font-size: 0.9rem; color: #6b7280;
  }
  .pricing-toggle .active { color: #111827; font-weight: 600; }
  .toggle-btn {
    width: 44px; height: 24px; border-radius: 12px;
    background: #e5e7eb; border: none; cursor: pointer;
    position: relative; transition: background 0.2s; padding: 0;
  }
  .toggle-btn:has(+ span.active) { background: #3b82f6; }
  .toggle-knob {
    position: absolute; top: 3px; left: 3px;
    width: 18px; height: 18px; border-radius: 50%;
    background: white; transition: left 0.2s;
  }
  .toggle-knob.right { left: 23px; }
  .save-badge {
    background: #dcfce7; color: #16a34a;
    font-size: 0.72rem; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
  }
  
.pricing-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:24px;
  max-width:1280px;
  margin:0 auto;
  align-items:stretch;
}

.pricing-card{
  background:#fff;
  border:1px solid #e5e7eb;
  border-radius:20px;
  padding:1.75rem;
  display:flex;
  flex-direction:column;
  min-height:520px;
  transition:.25s ease;
  position:relative;
  box-shadow:none;
}

.pricing-card:hover{
  border-color:#cbd5e1;
  transform:translateY(-3px);
}

.pricing-card.featured{
  background:#3b82f6;
  border:none;
  color:white;
  box-shadow:
    0 25px 60px rgba(59,130,246,.25);
  transform:none;
}
  .pricing-card.featured:hover { transform: translateY(-5px); }

.most-popular{
  position:absolute;
  top:-14px;
  left:50%;
  transform:translateX(-50%);
  background:white;
  border-radius:999px;
  padding:6px 18px;
  font-size:.75rem;
  font-weight:600;
  color:#2563eb;
  box-shadow:
    0 8px 20px rgba(15,23,42,.08);
}

.plan-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 14px;
  height: 32px;
  border-radius: 999px;
  background: #eef5ff;
  color: #3b82f6;
  font-size: 0.85rem;
  font-weight: 700;
  width: fit-content;
  margin-bottom: 1.5rem;
}
.pricing-card.featured .plan-badge {
  background: rgba(255,255,255,0.18);
  color: white;
}

  .pricing-card h3 {
    font-size: 1.6rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    color: #0f172a;
  }

  .pricing-card.featured h3 {
    color: white;
  }

.plan-sub{
  color:#64748b;
  font-size:.95rem;
  line-height:1.7;
  margin-bottom:2rem; 
}

  .pricing-card.featured .plan-sub {
    color: rgba(255,255,255,0.85);
  }

 .plan-price{
  font-size:2rem;
  font-weight:800;
  color:#0f172a;
  margin-bottom:2rem;
}

.plan-price span{
  font-size:1rem;
  color:#94a3b8;
  font-weight:500;
}

  .plan-price span {
    font-size: 0.9rem;
    font-weight: 500;
    color: #94a3b8;
  }

  .pricing-card.featured .plan-price {
    color: white;
  }

  .pricing-card.featured .plan-price span {
    color: rgba(255,255,255,0.7);
  }

  .plan-btn-outline{
  width:100%;
  height:54px;
  border:1px solid #0f172a;
  border-radius:10px;
  background:white;
  font-weight:600;
  font-size:.95rem;
  transition:.2s ease;
  margin-bottom:2rem;
}

.plan-btn-outline:hover{
  border:1px solid #2563eb;
  border-radius:10px;
  background:#2563eb;
  color:white;
}

  .plan-btn-white {
    width: 100%;
    padding: 0.85rem;
    border-radius: 12px;
    border: none;
    background: white;
    color: #2563eb;
    font-weight: 700;
    margin-bottom: 1.5rem;
  }

  .plan-btn-white:hover {
    transform: translateY(-2px);
  }

 .plan-features{
  margin-top:auto;
  padding-top:1.5rem;
  border-top:1px solid #f1f5f9;
  gap:1rem;
}

.pricing-card.featured .plan-features {
    border-top: 1px solid rgba(255,255,255,0.2);
  }

.plan-features li {
    font-size: 0.85rem;
    display: flex;
    gap: 0.6rem;
    color: #374151;
  }
.plan-features li::before {
  content: '✓';
  color: #16a34a;
  font-weight: 700; font-size: 0.95rem; flex-shrink: 0;
}

  .pricing-card.featured .plan-features li {
    color: rgba(255,255,255,0.9);
  }

  /* ── CTA ── */
  .cta-section {
    position: relative; padding: 4rem 6%;
    background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
  }
  .cta-section > *:not(.blob) { position: relative; z-index: 1; }
  .cta-card {
    background: rgba(59,130,246,0.92);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 20px; padding: 3rem 3.5rem;
    display: flex; align-items: center; justify-content: space-between; gap: 2rem;
  }
  .cta-card h2 { font-size: 2rem; font-weight: 800; color: white; margin-bottom: 0.5rem; line-height: 1.2; }
  .cta-card p  { color: rgba(255,255,255,0.8); font-size: 0.95rem; margin: 0; }
  .cta-btn {
    background: white; color: #3b82f6; border: none; border-radius: 12px;
    padding: 0.85rem 1.75rem; font-weight: 700; font-size: 0.95rem; cursor: pointer;
    white-space: nowrap; display: inline-flex; align-items: center; gap: 0.5rem;
    transition: all 0.25s ease;
  }
  .cta-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(255,255,255,.35); }
  .cta-icon { display: inline-block; transition: transform 0.25s ease; }
  .cta-btn:hover .cta-icon { transform: translateX(6px) translateY(-2px); }

  /* ── RESPONSIVE ── */
  @media (max-width: 1024px) {
    .hero-inner { flex-direction: column; text-align: center; }
    .hero-left, .hero-right { flex: unset; width: 100%; max-width: 100%; }
    .hero-cta, .hero-badges, .hero-reviews { justify-content: center; }
    .dash-wrap { max-width: 100%; }
    .dashboard-img { transform: none; }
    .how-section { grid-template-columns: 1fr; }
    .testimonials-grid { gap: 1rem; }
    .testimonial-card { flex: 0 0 260px; }
  }

  @media (max-width: 768px) {
    .hero-inner, .features-section { flex-direction: column; }
    .how-section { grid-template-columns: 1fr; gap: 2rem; }
    .hero-left   { flex: none; width: 100%; padding-bottom: 2rem; }
    .hero-title  { font-size: 2rem; }
    .hero-badges { flex-wrap: wrap; }
    .testimonial-card { flex: 0 0 80vw; }
    .cta-card    { flex-direction: column; text-align: center; }
    .dash-overlay-cards { position: static; margin-top: 12px; flex-wrap: wrap; }
    .amuma-card, .caregiver-card { width: 100%; flex: unset; }
    .hero-right  { padding-bottom: 3rem; }
    .hero-section { padding-bottom: 4rem; }
    .features-right { grid-template-columns: 1fr; }
    .feature-card.wide.with-photo-row { flex-direction: column; }
    .feature-card.wide.with-photo-row .feature-card-text { flex: unset; }
    .feature-card.wide.with-photo-row .feature-card-photo { border-radius: 0 0 18px 18px; }
    .pricing-grid { grid-template-columns: 1fr; }
    .solutions-grid { grid-template-columns: 1fr; }
    .features-title { font-size: 2.5rem; }
  }
  </style>