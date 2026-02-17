<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$base = rtrim(BASE_URL, '/');

// Fetch open RFQs for the public preview
$openRfqs = [];
try {
    $pdo = db('proc');
    $st = $pdo->query("
        SELECT r.id, r.rfq_no, r.title, r.due_at, r.currency,
               (SELECT COUNT(*) FROM rfq_items ri WHERE ri.rfq_id = r.id) AS item_count
        FROM rfqs r
        WHERE LOWER(r.status) IN ('sent','open','published')
          AND (r.due_at IS NULL OR r.due_at >= NOW())
        ORDER BY r.due_at ASC
        LIMIT 6
    ");
    $openRfqs = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $openRfqs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Vendor Portal | ViaHale TNVS</title>
  <meta name="description" content="Join ViaHale's trusted vendor network. Access RFQs, manage purchase orders, and grow your business with TNVS.">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

  <style>
    :root {
      --brand: #6532C9;
      --brand-deep: #4311A5;
      --brand-accent: #7c3aed;
      --brand-light: #f4f2ff;
      --brand-glow: rgba(101,50,201,.15);
      --text-dark: #2b2349;
      --text-muted: #6f6c80;
      --border: #e3dbff;
      --bg: #FBFBFF;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      color: var(--text-dark);
      background: var(--bg);
      line-height: 1.6;
    }

    /* ---- Navbar ---- */
    .nav-bar {
      position: sticky; top: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 1rem 2rem;
      background: rgba(251,251,255,.85);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
    }
    .nav-brand {
      font-family: 'Bricolage Grotesque', sans-serif;
      font-weight: 800; font-size: 1.25rem;
      color: var(--brand-deep);
      text-decoration: none;
    }
    .nav-actions { display: flex; gap: .75rem; }
    .btn {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .65rem 1.4rem; border-radius: .6rem;
      font-weight: 600; font-size: .9rem;
      text-decoration: none; border: none; cursor: pointer;
      transition: transform .15s, box-shadow .2s;
    }
    .btn:hover { transform: translateY(-1px); }
    .btn-outline {
      background: #fff; color: var(--brand);
      border: 1.5px solid var(--border);
    }
    .btn-outline:hover { border-color: var(--brand-accent); background: var(--brand-light); }
    .btn-primary {
      background: linear-gradient(135deg, var(--brand), var(--brand-accent));
      color: #fff;
      box-shadow: 0 4px 14px var(--brand-glow);
    }
    .btn-primary:hover { box-shadow: 0 6px 22px rgba(101,50,201,.25); }
    .btn-lg { padding: .85rem 2rem; font-size: 1rem; border-radius: .7rem; }

    /* ---- Hero ---- */
    .hero {
      text-align: center;
      padding: 5rem 1.5rem 4rem;
      background:
        radial-gradient(60% 55% at 50% 0%, rgba(124,58,237,.08), transparent),
        radial-gradient(45% 60% at 80% 20%, rgba(101,50,201,.06), transparent),
        var(--bg);
    }
    .hero-badge {
      display: inline-flex; align-items: center; gap: .4rem;
      background: var(--brand-light); border: 1px solid var(--border);
      border-radius: 999px; padding: .4rem 1rem;
      font-size: .8rem; font-weight: 600; color: var(--brand-deep);
      margin-bottom: 1.5rem;
    }
    .hero h1 {
      font-family: 'Bricolage Grotesque', sans-serif;
      font-size: clamp(2rem, 5vw, 3.2rem);
      font-weight: 800; line-height: 1.15;
      color: var(--brand-deep);
      max-width: 720px; margin: 0 auto .75rem;
    }
    .hero h1 span { color: var(--brand-accent); }
    .hero p {
      font-size: 1.1rem; color: var(--text-muted);
      max-width: 560px; margin: 0 auto 2rem;
    }
    .hero-cta { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
    .hero-stats {
      display: flex; gap: 2.5rem; justify-content: center;
      margin-top: 3rem; flex-wrap: wrap;
    }
    .hero-stats .stat { text-align: center; }
    .hero-stats .stat-num {
      font-family: 'Bricolage Grotesque', sans-serif;
      font-size: 1.8rem; font-weight: 800; color: var(--brand);
    }
    .hero-stats .stat-label { font-size: .8rem; color: var(--text-muted); font-weight: 500; }

    /* ---- Section ---- */
    .section {
      padding: 4.5rem 1.5rem;
      max-width: 1100px; margin: 0 auto;
    }
    .section-header {
      text-align: center; margin-bottom: 3rem;
    }
    .section-header .kicker {
      font-size: .78rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .8px; color: var(--brand-accent); margin-bottom: .4rem;
    }
    .section-header h2 {
      font-family: 'Bricolage Grotesque', sans-serif;
      font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 700;
      color: var(--text-dark);
    }

    /* ---- Features Grid ---- */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }
    .feature-card {
      background: #fff;
      border: 1px solid rgba(101,50,201,.07);
      border-radius: 1rem;
      padding: 1.75rem;
      box-shadow: 0 4px 16px rgba(67,17,165,.04);
      transition: transform .2s, box-shadow .2s;
    }
    .feature-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(67,17,165,.1);
    }
    .feature-icon {
      width: 50px; height: 50px; border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem; margin-bottom: 1rem;
      background: var(--brand-light); color: var(--brand);
      border: 1px solid var(--border);
    }
    .feature-card h3 {
      font-size: 1.05rem; font-weight: 700; margin-bottom: .4rem;
    }
    .feature-card p {
      font-size: .9rem; color: var(--text-muted); margin: 0;
    }

    /* ---- Steps ---- */
    .steps-section { background: var(--brand-light); border-radius: 0; }
    .steps-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 2rem;
      counter-reset: step;
    }
    .step-card {
      text-align: center; padding: 2rem 1.5rem;
      counter-increment: step;
    }
    .step-num {
      width: 56px; height: 56px; border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      font-family: 'Bricolage Grotesque', sans-serif;
      font-size: 1.3rem; font-weight: 800;
      background: linear-gradient(135deg, var(--brand), var(--brand-accent));
      color: #fff; margin-bottom: 1rem;
      box-shadow: 0 6px 18px var(--brand-glow);
    }
    .step-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: .4rem; }
    .step-card p { font-size: .88rem; color: var(--text-muted); }

    /* ---- CTA Banner ---- */
    .cta-banner {
      text-align: center;
      padding: 4rem 1.5rem;
      max-width: 700px; margin: 0 auto;
    }
    .cta-banner h2 {
      font-family: 'Bricolage Grotesque', sans-serif;
      font-size: clamp(1.4rem, 3vw, 1.9rem);
      font-weight: 700; margin-bottom: .75rem;
    }
    .cta-banner p { color: var(--text-muted); margin-bottom: 1.5rem; font-size: 1rem; }
    .cta-banner .btn-group { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }

    /* ---- Footer ---- */
    .landing-footer {
      text-align: center; padding: 1.5rem;
      border-top: 1px solid var(--border);
      font-size: .82rem; color: var(--text-muted);
    }
    .landing-footer a { color: var(--brand); text-decoration: none; }
    .landing-footer a:hover { text-decoration: underline; }

    /* ---- Opportunities ---- */
    .opp-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.25rem;
    }
    .opp-card {
      background: #fff;
      border: 1px solid rgba(101,50,201,.08);
      border-radius: 1rem;
      padding: 1.5rem;
      box-shadow: 0 4px 14px rgba(67,17,165,.04);
      transition: transform .2s, box-shadow .2s;
      display: flex; flex-direction: column; gap: .75rem;
    }
    .opp-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 28px rgba(67,17,165,.1);
    }
    .opp-rfq {
      font-size: .75rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .5px; color: var(--brand-accent);
    }
    .opp-title {
      font-size: 1.05rem; font-weight: 700; color: var(--text-dark);
      line-height: 1.35;
    }
    .opp-meta {
      display: flex; gap: 1rem; flex-wrap: wrap;
      font-size: .82rem; color: var(--text-muted);
    }
    .opp-meta span { display: inline-flex; align-items: center; gap: .3rem; }
    .opp-badge {
      display: inline-flex; align-items: center; gap: .3rem;
      background: #edfcf2; color: #059669; border: 1px solid #bbf7d0;
      border-radius: 999px; padding: .2rem .7rem;
      font-size: .72rem; font-weight: 600; width: fit-content;
    }
    .opp-empty {
      text-align: center; padding: 3rem 1rem;
      color: var(--text-muted);
    }
    .opp-empty ion-icon { font-size: 2.5rem; color: var(--border); margin-bottom: .5rem; display: block; }

    @media (max-width: 600px) {
      .nav-bar { padding: .8rem 1rem; }
      .hero { padding: 3rem 1rem 2.5rem; }
      .hero-stats { gap: 1.5rem; }
      .opp-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="nav-bar">
    <a href="<?= $base ?>/login.php" class="nav-brand">ViaHale</a>
    <div class="nav-actions">
      <a href="<?= $base ?>/login.php" class="btn btn-outline">Sign In</a>
      <a href="<?= $base ?>/vendor_portal/vendor/register.php" class="btn btn-primary">Register</a>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-badge">
      <ion-icon name="shield-checkmark-outline"></ion-icon>
      Trusted Vendor Network
    </div>
    <h1>Grow Your Business with <span>ViaHale TNVS</span></h1>
    <p>Join our vendor portal to access procurement opportunities, respond to RFQs, manage purchase orders, and track compliance — all in one place.</p>
    <div class="hero-cta">
      <a href="<?= $base ?>/vendor_portal/vendor/register.php" class="btn btn-primary btn-lg">
        <ion-icon name="person-add-outline"></ion-icon> Register Now
      </a>
      <a href="<?= $base ?>/login.php" class="btn btn-outline btn-lg">
        <ion-icon name="log-in-outline"></ion-icon> Vendor Sign In
      </a>
    </div>
    <div class="hero-stats">
      <div class="stat">
        <div class="stat-num">100+</div>
        <div class="stat-label">Active Vendors</div>
      </div>
      <div class="stat">
        <div class="stat-num">500+</div>
        <div class="stat-label">RFQs Issued</div>
      </div>
      <div class="stat">
        <div class="stat-num">99%</div>
        <div class="stat-label">On-Time Payments</div>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="section">
    <div class="section-header">
      <div class="kicker">Why Partner With Us</div>
      <h2>Everything You Need to Succeed</h2>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><ion-icon name="document-text-outline"></ion-icon></div>
        <h3>RFQ Access</h3>
        <p>Receive and respond to Requests for Quotation directly from your dashboard — no paperwork needed.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#eef6ff; color:#1d4ed8; border-color:#d0e2ff;">
          <ion-icon name="cart-outline"></ion-icon>
        </div>
        <h3>Purchase Order Tracking</h3>
        <p>View, acknowledge, and manage purchase orders with real-time status updates and delivery schedules.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#edfcf2; color:#059669; border-color:#bbf7d0;">
          <ion-icon name="shield-checkmark-outline"></ion-icon>
        </div>
        <h3>Compliance Dashboard</h3>
        <p>Upload KYC documents, track approval status, and maintain compliance — all from one place.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#fef9ec; color:#b45309; border-color:#fde68a;">
          <ion-icon name="notifications-outline"></ion-icon>
        </div>
        <h3>Real-Time Notifications</h3>
        <p>Stay informed with instant alerts for new RFQs, PO updates, award notifications, and compliance deadlines.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#fdf2f8; color:#be185d; border-color:#fbcfe8;">
          <ion-icon name="bar-chart-outline"></ion-icon>
        </div>
        <h3>Performance Analytics</h3>
        <p>Track your response rates, award history, and delivery performance with easy-to-read analytics.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#f0fdfa; color:#0d9488; border-color:#99f6e4;">
          <ion-icon name="lock-closed-outline"></ion-icon>
        </div>
        <h3>Secure & Private</h3>
        <p>Your data is encrypted and protected. Only authorized procurement staff can view your submissions.</p>
      </div>
    </div>
  </section>

  <!-- Current Opportunities -->
  <section class="section" style="padding-top:0;">
    <div class="section-header">
      <div class="kicker">Current Opportunities</div>
      <h2>Items Open for Bidding</h2>
    </div>
    <?php if (!empty($openRfqs)): ?>
      <div class="opp-grid">
        <?php foreach ($openRfqs as $rfq): ?>
          <div class="opp-card">
            <div class="opp-rfq"><?= htmlspecialchars($rfq['rfq_no'] ?? 'RFQ') ?></div>
            <div class="opp-title"><?= htmlspecialchars($rfq['title'] ?? 'Untitled') ?></div>
            <div class="opp-meta">
              <span><ion-icon name="cube-outline"></ion-icon> <?= (int)$rfq['item_count'] ?> item<?= (int)$rfq['item_count'] !== 1 ? 's' : '' ?></span>
              <?php if (!empty($rfq['due_at'])): ?>
                <span><ion-icon name="time-outline"></ion-icon> Due <?= date('M j, Y', strtotime($rfq['due_at'])) ?></span>
              <?php endif; ?>
              <?php if (!empty($rfq['currency'])): ?>
                <span><ion-icon name="cash-outline"></ion-icon> <?= htmlspecialchars($rfq['currency']) ?></span>
              <?php endif; ?>
            </div>
            <div class="opp-badge"><ion-icon name="radio-button-on-outline"></ion-icon> Open for Bidding</div>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="text-align:center; margin-top:1.5rem;">
        <a href="<?= $base ?>/vendor_portal/vendor/register.php" class="btn btn-primary">
          <ion-icon name="person-add-outline"></ion-icon> Register to Bid
        </a>
      </div>
    <?php else: ?>
      <div class="opp-empty">
        <ion-icon name="document-text-outline"></ion-icon>
        <p>No open RFQs at the moment. Register now to get notified when new opportunities are posted.</p>
        <a href="<?= $base ?>/vendor_portal/vendor/register.php" class="btn btn-outline" style="margin-top:1rem;">
          <ion-icon name="person-add-outline"></ion-icon> Register Now
        </a>
      </div>
    <?php endif; ?>
  </section>

  <!-- How It Works -->
  <section class="steps-section">
    <div class="section" style="padding-top:4rem; padding-bottom:4rem;">
      <div class="section-header">
        <div class="kicker">How It Works</div>
        <h2>Get Started in Three Easy Steps</h2>
      </div>
      <div class="steps-grid">
        <div class="step-card">
          <div class="step-num">1</div>
          <h3>Register Your Business</h3>
          <p>Fill out your company details, upload required documents, and create your vendor account in minutes.</p>
        </div>
        <div class="step-card">
          <div class="step-num">2</div>
          <h3>Get Approved</h3>
          <p>Our procurement team reviews your application and documents. You'll be notified once approved.</p>
        </div>
        <div class="step-card">
          <div class="step-num">3</div>
          <h3>Start Bidding</h3>
          <p>Access RFQs, submit competitive quotes, and win purchase orders through our transparent process.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta-banner">
    <h2>Ready to Join Our Vendor Network?</h2>
    <p>Registration is quick and free. Start receiving procurement opportunities today.</p>
    <div class="btn-group">
      <a href="<?= $base ?>/vendor_portal/vendor/register.php" class="btn btn-primary btn-lg">
        <ion-icon name="rocket-outline"></ion-icon> Get Started
      </a>
      <a href="<?= $base ?>/login.php" class="btn btn-outline btn-lg">
        Already Registered? Sign In
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="landing-footer">
    &copy; <?= date('Y') ?> ViaHale TNVS — <a href="<?= $base ?>/login.php">Staff Login</a>
  </footer>

</body>
</html>
