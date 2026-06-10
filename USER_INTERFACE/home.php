<?php
session_start();
require_once 'db.php';
$loggedIn  = isset($_SESSION['user_id']);
$userName  = $loggedIn ? htmlspecialchars($_SESSION['user_name']) : '';
$userInitial = $loggedIn ? strtoupper($userName[0]) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CoWork Space — Home</title>
<link rel="stylesheet" href="style.css">
<style>
/* Inline extras specific to homepage only */
.contact-section { background: var(--dark-2); color: #fff; padding: 80px 5%; }
.contact-section h2 { color: #fff; margin-bottom: 16px; }
.contact-section p  { color: rgba(255,255,255,0.65); max-width: 500px; margin-bottom: 32px; }
.contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 20px; max-width: 700px; }
.contact-item { display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: rgba(255,255,255,0.8); }
.contact-item .ci { font-size: 1.3rem; color: var(--brown-light); }
</style>
</head>
<body>

<!-- ─── NAVIGATION BAR ─────────────────────────── -->
<nav class="navbar">
  <div class="navbar-logo">CO<span>WORK</span></div>
  <ul class="navbar-links">
    <li><a href="home.php">Home</a></li>
    <li><a href="workspace_list.php">Workspaces</a></li>
    <li><a href="booking_form.php">Book Now</a></li>
    <?php if ($loggedIn): ?>
    <li><a href="my_booking.php">My Bookings</a></li>
    <?php endif; ?>
    <li><a href="#features">Features</a></li>
    <li><a href="#gallery">Gallery</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <div class="navbar-actions">
    <?php if ($loggedIn): ?>
      <div class="user-pill">
        <div class="avatar"><?= $userInitial ?></div>
        <?= $userName ?>
      </div>
      <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
    <?php else: ?>
      <a href="login.php"    class="btn btn-outline btn-sm">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ─── INTRO ──────────────────────────── -->
<section class="intro">
  <div class="intro-bg"></div>
  <div class="intro-overlay"></div>
  <div class="intro-content">
    <h1>Working from home feels like this?</h1>
    <ul class="intro-bullets">
      <li>Constant distractions</li>
      <li>Endless unfinished tasks</li>
      <li>Work bleeding into your evenings</li>
    </ul>
    <p class="intro-tagline">CoWork Space is built to fix exactly that</p>
    <div class="intro-btns">
      <a href="booking_form.php" class="btn btn-primary btn-lg">Book Now</a>
      <a href="workspace_list.php" class="btn btn-outline btn-lg" style="color:#fff;border-color:#fff;">View Workspaces</a>
    </div>
    <div class="intro-info">
      <div class="intro-info-item">
        <span class="icon">📍</span>
        <p><strong>JohorBahru, Malaysia</strong></p>
      </div>
      <div class="intro-info-item">
        <span class="icon">🕐</span>
        <p>
          <strong>Working Hours</strong>
          10:00AM – 10:00PM (Sun–Mon, Wed–Thu)<br>
          10:00AM – 11:00PM (Fri–Sat)<br>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ─── FEATURES ──────────────────────── -->
<section class="section" id="features">
  <div class="section-center">
    <h2>Designed for <span class="accent">Deep Work</span></h2>
    <p>Every detail at CoWork Space is crafted to help you enter flow state and accomplish your most important work.</p>
  </div>
  <div class="cards-grid">
    <div class="card">
      <div class="card-icon">🔇</div>
      <h3>Quiet Zones Only</h3>
      <p>No phone calls, no loud conversations. Just pure focus and productivity in a peaceful environment.</p>
    </div>
    <div class="card featured">
      <div class="card-icon">📶</div>
      <h3 style="color:var(--olive)">High-Speed Wi-Fi &amp; Amenities</h3>
      <p>Gigabit internet, free snacks and beverages, and everything you need to stay productive all day long.</p>
    </div>
    <div class="card">
      <div class="card-icon">💼</div>
      <h3>Professional Workspace</h3>
      <p>Modern facilities, comfortable seating, and a productive environment designed for serious work.</p>
    </div>
  </div>
</section>

<!-- ─── WORKSPACE PREVIEW ──────────────── -->
<section class="section section-bg">
  <div class="section-center">
    <h2>Choose Your <span class="accent">Workspace</span></h2>
    <p>Three dedicated floors, each designed for different working styles and team sizes.</p>
  </div>
  <div class="cards-grid">
    <div class="card" style="text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;">🪑</div>
      <h3>Single Room</h3>
      <p>Floor 1 · 20 rooms · Perfect for focused solo work</p>
      <div style="font-size:1.5rem;font-weight:800;color:var(--brown);margin:12px 0">RM10 <small style="font-size:0.9rem;font-weight:500;color:var(--text-muted)">/slot (4 hrs)</small></div>
      <a href="booking_form.php?type=single" class="btn btn-primary" style="width:100%">Book Now</a>
    </div>
    <div class="card" style="text-align:center;box-shadow:var(--shadow-md)">
      <div style="font-size:2.5rem;margin-bottom:12px;">👥</div>
      <h3>Discussion Room</h3>
      <p>Floor 2 · 10 rooms · Great for small team meetings</p>
      <div style="font-size:1.5rem;font-weight:800;color:var(--brown);margin:12px 0">RM30 <small style="font-size:0.9rem;font-weight:500;color:var(--text-muted)">/slot (4 hrs)</small></div>
      <a href="booking_form.php?type=discussion" class="btn btn-primary" style="width:100%">Book Now</a>
    </div>
    <div class="card" style="text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;">🏢</div>
      <h3>Private Office</h3>
      <p>Floor 3 · 5 offices · Ideal for long-term rentals</p>
      <div style="font-size:1.1rem;font-weight:800;color:var(--brown);margin:12px 0;line-height:1.7">
        RM400/week · RM1,000/month<br><small style="font-weight:500;color:var(--text-muted)">RM11,000/year</small>
      </div>
      <a href="booking_form.php?type=office" class="btn btn-primary" style="width:100%">Book Now</a>
    </div>
  </div>
</section>

<!-- ─── GALLERY ────────────────────────── -->
<section class="section" id="gallery">
  <div class="section-center">
    <h2>CoWork <span class="accent">Gallery</span></h2>
    <p>Real moments from inside our space — built for quiet focus and steady flow.</p>
  </div>
  <div class="gallery-wrap">
    <div class="gallery-slide" id="gallerySlide">
      <img id="galleryImg" src="assets/gallery1.jpg" alt="Gallery" style="background:var(--cream-dark)">
      <div class="gallery-caption">
        <h4 id="galleryTitle">Focus starts with calm</h4>
        <p id="galleryDesc">Our space is designed for quiet, individual work. Meetings and calls aren't allowed — so the space stays peaceful for everyone.</p>
      </div>
    </div>
    <button class="gallery-btn prev" onclick="changeSlide(-1)">&#8249;</button>
    <button class="gallery-btn next" onclick="changeSlide(1)">&#8250;</button>
    <div class="gallery-dots" id="galleryDots"></div>
  </div>
</section>

<!-- ─── CONTACT ────────────────────────── -->
<section class="contact-section" id="contact">
  <h2>Get in Touch</h2>
  <p>Have questions? Our team is ready to help you find the perfect workspace.</p>
  <div class="contact-grid">
    <div class="contact-item"><span class="ci">📍</span><span>Cyberjaya, Selangor, Malaysia</span></div>
    <div class="contact-item"><span class="ci">📧</span><span>hello@coworkspace.my</span></div>
    <div class="contact-item"><span class="ci">📞</span><span>+60 3-8888 1234</span></div>
    <div class="contact-item"><span class="ci">🕐</span><span>Open daily except Tuesdays</span></div>
  </div>
</section>

<!-- ─── FOOTER ─────────────────────────── -->
<footer>
  <div>
    <div class="logo">CO<span style="color:var(--brown-light)">WORK</span></div>
    <p style="margin-top:8px;font-size:0.8rem">Your productive escape from home</p>
  </div>
  <div>
    <p>&copy; <?= date('Y') ?> CoWork Space. All rights reserved.</p>
  </div>
</footer>

<script>
const slides = [
  { img: 'assets/gallery1.jpg', title: 'Focus starts with calm',      desc: 'Our space is designed for quiet individual work — meetings and calls aren\'t allowed.' },
  { img: 'assets/gallery2.jpg', title: 'Single desks, serious work',  desc: 'Floor 1 single rooms offer dedicated personal desks with power outlets at every seat.' },
  { img: 'assets/gallery3.jpg', title: 'Collaborate in Discussion',   desc: 'Floor 2 discussion rooms are perfect for small-team sessions and brainstorming.' },
  { img: 'assets/gallery4.jpg', title: 'Private offices, your rules', desc: 'Floor 3 private offices are available weekly, monthly, or yearly for full flexibility.' },
  { img: 'assets/gallery5.jpg', title: 'Bright, open lounge',         desc: 'Decompress between sessions in our naturally lit lounge area.' },
];

let cur = 0;
const img   = document.getElementById('galleryImg');
const title = document.getElementById('galleryTitle');
const desc  = document.getElementById('galleryDesc');
const dots  = document.getElementById('galleryDots');

function buildDots() {
  slides.forEach((_, i) => {
    const d = document.createElement('button');
    d.className = 'gallery-dot' + (i === 0 ? ' active' : '');
    d.onclick = () => goTo(i);
    dots.appendChild(d);
  });
}

function goTo(n) {
  cur = (n + slides.length) % slides.length;
  img.src   = slides[cur].img;
  title.textContent = slides[cur].title;
  desc.textContent  = slides[cur].desc;
  dots.querySelectorAll('.gallery-dot').forEach((d, i) => d.classList.toggle('active', i === cur));
}

function changeSlide(dir) { goTo(cur + dir); }

buildDots();
setInterval(() => changeSlide(1), 5000);
</script>
</body>
</html>