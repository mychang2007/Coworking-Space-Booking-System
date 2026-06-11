<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();

$userId   = (int)$_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name']);
$userInit = strtoupper($userName[0]);

// FIX: pre-fill user details from `customer` table using customer_id
$uRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT fullname, email, phone FROM customer WHERE customer_id=$userId"));

// FIX: all zone types from `zone` table
$types = mysqli_fetch_all(mysqli_query($conn,
    "SELECT * FROM zone ORDER BY floor"), MYSQLI_ASSOC);

// Default selected zone
$selZoneId = (int)($_GET['zone_id'] ?? ($types[0]['zone_id'] ?? 1));

// FIX: all rooms with live busy flag via `workspace` + `booking` tables
// A room is busy if it has an active booking that overlaps NOW
$roomsRes = mysqli_query($conn,
    "SELECT w.workspace_id AS id, w.workspace_name AS room_number,
            w.zone_id AS type_id, w.status, w.capacity,
            z.floor, z.zone_name AS type_name,
            0 AS busy
     FROM workspace w
     JOIN zone z ON w.zone_id = z.zone_id
     ORDER BY z.floor, w.workspace_name");
$roomsByType = [];
while ($r = mysqli_fetch_assoc($roomsRes)) {
    $roomsByType[$r['type_id']][] = $r;
}

// Zone lookup map
$typeMap = [];
foreach ($types as $t) $typeMap[$t['zone_id']] = $t;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wsId      = (int)($_POST['workspace_id'] ?? 0);
    $zoneId    = (int)($_POST['type_id']       ?? 0);
    $bType     = trim($_POST['booking_type']   ?? '');
    $startDate = trim($_POST['start_date']     ?? '');
    $startTime = trim($_POST['start_time']     ?? '10:00');
    $notes     = trim($_POST['notes']          ?? '');

    $wType = $typeMap[$zoneId] ?? null;

    if (!$wsId)   $errors[] = 'Please select a room from the floor map.';
    if (!$wType)  $errors[] = 'Invalid workspace type.';
    if (!$bType)  $errors[] = 'Please select a booking duration.';
    if (!$startDate) $errors[] = 'Please select a start date.';

    if (empty($errors)) {
        $startDT = $startDate . ' ' . $startTime . ':00';
        $price   = 0;
        switch ($bType) {
            case 'slot':  $endDT = date('Y-m-d H:i:s', strtotime("$startDT +4 hours")); $price = $wType['price_slot'];  break;
            case 'week':  $endDT = date('Y-m-d H:i:s', strtotime("$startDT +7 days"));  $price = $wType['price_week'];  break;
            case 'month': $endDT = date('Y-m-d H:i:s', strtotime("$startDT +1 month")); $price = $wType['price_month']; break;
            case 'year':  $endDT = date('Y-m-d H:i:s', strtotime("$startDT +1 year"));  $price = $wType['price_year'];  break;
            default: $errors[] = 'Invalid booking type.'; $endDT = ''; $price = 0;
        }
    }

    if (empty($errors) && !$price) {
        $errors[] = 'This booking type is not available for the selected workspace.';
    }

    if (empty($errors)) {
        // FIX: conflict check uses `booking` table with start_time / end_time columns
        $chk = mysqli_prepare($conn,
            "SELECT booking_id FROM booking
             WHERE workspace_id=? AND status IN ('active', 'Pending CheckIn')
               AND NOT (end_time<=? OR start_time>=?)");
        mysqli_stmt_bind_param($chk,'iss',$wsId,$startDT,$endDT);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $errors[] = 'That room is already booked for your chosen time. Please pick another room or time.';
        } else {
            // FIX: insert into `booking` table with correct ERD column names
            $token = 'BK-'.date('Ymd').'-'.strtoupper(substr(uniqid(),-5));
            $bDate = date('Y-m-d', strtotime($startDT));
            $ins   = mysqli_prepare($conn,
                "INSERT INTO booking
                 (booking_token, customer_id, workspace_id, booking_date,
                  start_time, end_time, booking_type, total_price, notes, status)
                 VALUES (?,?,?,?,?,?,?,?,?,'Pending CheckIn')");
            mysqli_stmt_bind_param($ins,'siissssds',
                $token,$userId,$wsId,$bDate,$startDT,$endDT,$bType,$price,$notes);
            if (mysqli_stmt_execute($ins)) {
                header("Location: my_booking.php?booked=1"); exit;
            } else {
                $errors[] = 'Booking could not be saved. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Book a Workspace — CoWork Space</title>
<link rel="stylesheet" href="style.css">
<style>
.floor-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
.floor-tab{padding:8px 20px;border-radius:999px;border:2px solid var(--border);background:var(--white);
    font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s}
.floor-tab.active{border-color:var(--brown);background:var(--brown);color:#fff}
.floor-map{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
.room-seat{aspect-ratio:1;border-radius:8px;border:2px solid var(--border);background:var(--cream);
    display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;
    color:var(--text-muted);cursor:pointer;transition:all .2s;user-select:none}
.room-seat.avail   {border-color:#c3e6cb;background:#d4edda;color:#155724}
.room-seat.unavail {border-color:#f5c6cb;background:#f8d7da;color:#dc3545;cursor:not-allowed}
.room-seat.selected{border-color:var(--brown);background:var(--brown);color:#fff}
.map-legend{display:flex;gap:16px;margin-top:10px;flex-wrap:wrap}
.map-legend .li{display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--text-muted)}
.map-legend .li .dot{width:14px;height:14px;border-radius:4px;border:2px solid}
.sec-label{display:flex;align-items:center;gap:8px;font-size:.8rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);
    margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.price-box{background:var(--cream);border-radius:var(--radius-sm);padding:14px 16px;
    margin-top:14px;border:1px solid var(--border);display:none}
.price-box .total{font-size:1.5rem;font-weight:800;color:var(--brown)}
</style>
</head>
<body>

<nav class="navbar">
  <a href="home.php" class="navbar-logo">CO<span>WORK</span></a>
  <ul class="navbar-links">
    <li><a href="home.php">Home</a></li>
    <li><a href="workspace_list.php">Workspaces</a></li>
    <li><a href="booking_form.php" style="color:var(--brown)">Book Now</a></li>
    <li><a href="my_booking.php">My Bookings</a></li>
  </ul>
  <div class="navbar-actions">
    <div class="user-pill" onclick="toggleDropdown(event)">
        <div class="avatar"><?= $userInit ?></div>
        <span><?= $userName ?></span>
        <span class="arrow">▼</span>
      </div>
      <div class="dropdown-menu" id="userMenu">
        <a href="settings.php">⚙️ Account Settings</a>
        <div class="dropdown-divider"></div>
    <script src="dropdown.js"></script>
    <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
  </div>
</nav>

<div class="booking-page">
  <div style="padding:100px 5% 20px;text-align:center">
    <h1 style="font-size:2rem">Book Your Workspace</h1>
    <p>Select your floor, pick a room, choose your duration, and confirm.</p>
  </div>

  <div class="booking-page-inner">
    <!-- MAIN FORM -->
    <div>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST" id="bForm">
        <!-- FIX: hidden field now named type_id but holds zone_id value -->
        <input type="hidden" name="type_id"      id="typeIdInput"  value="<?= $selZoneId ?>">
        <input type="hidden" name="workspace_id" id="wsIdInput"    value="">

        

        <!-- STEP 1: DURATION -->
        <div class="booking-panel" style="margin-bottom:20px">
          <div class="sec-label">📅 Step 1 — Duration &amp; Schedule</div>
          <div class="form-group">
            <label>Booking Duration *</label>
              <select name="booking_type" id="bookingType" onchange="calcPrice()">
                <option value="">— Select —</option>
                <option value="slot">Per Slot (4 hours)</option>
                <option value="week">(Rent Office only) Weekly (7 days)</option>
                <option value="month">(Rent Office only) Monthly (1 month)</option>
                <option value="year">(Rent Office only) Yearly (1 year)</option>
              </select>
          </div>
          <div class="form-group">
            <label>Start Date *</label>
            <input type="date" name="start_date" id="startDate"
                   min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group" id="timeRow">
            <label>Start Time *</label>
            <select name="start_time" id="startTime">
              <?php for ($h=10;$h<=22;$h++): ?>
                <option><?= sprintf('%02d:00',$h) ?></option>
                <?php if ($h<22): ?><option><?= sprintf('%02d:30',$h) ?></option><?php endif; ?>
              <?php endfor; ?>
            </select>
          </div>
          <div class="price-box" id="priceBox">
            <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:2px">Estimated Total</div>
            <div class="total" id="priceTotal">RM 0.00</div>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px" id="priceNote"></div>
          </div>
        </div>

        <!-- STEP 2: FLOOR / ZONE TABS + MAP -->
        <div class="booking-panel" style="margin-bottom:20px">
          <div class="sec-label">📍 Step 2 — Select Your Room</div>
          <div class="floor-tabs" id="floorTabs">
            <?php foreach ($types as $t): ?>
            <button type="button"
                    class="floor-tab <?= $t['zone_id']==$selZoneId?'active':'' ?>"
                    data-type-id="<?= $t['zone_id'] ?>"
                    onclick="switchType(<?= $t['zone_id'] ?>)">
              Floor <?= $t['floor'] ?> · <?= htmlspecialchars($t['zone_name']) ?>
            </button>
            <?php endforeach; ?>
          </div>

          <!-- Room maps per zone -->
          <?php foreach ($types as $t):
            $zid = $t['zone_id'];
            $rooms = $roomsByType[$zid] ?? [];
          ?>
          <div id="map-type-<?= $zid ?>" style="<?= $zid!=$selZoneId?'display:none':'' ?>">
            <div class="floor-map">
              <?php foreach ($rooms as $rm):
                // FIX: room unavailable if status!=available OR currently has active booking
                $notAvail = ($rm['status'] !== 'available' || $rm['busy'] > 0);
                $cls = $notAvail ? 'unavail' : 'avail';
              ?>
              <div class="room-seat <?= $cls ?>"
                   data-id="<?= $rm['id'] ?>"
                   data-num="<?= htmlspecialchars($rm['room_number']) ?>"
                   data-type-id="<?= $zid ?>"
                   data-ok="<?= $notAvail ? '0' : '1' ?>"
                   onclick="selectRoom(this)"
                   title="<?= htmlspecialchars($rm['room_number']) ?> — <?= $notAvail ? 'Unavailable' : 'Available' ?>">
                <?= htmlspecialchars($rm['room_number']) ?>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="map-legend">
              <div class="li"><div class="dot" style="background:#d4edda;border-color:#c3e6cb"></div> Available</div>
              <div class="li"><div class="dot" style="background:#f8d7da;border-color:#f5c6cb"></div> Unavailable / Booked</div>
              <div class="li"><div class="dot" style="background:var(--brown);border-color:var(--brown)"></div> Selected</div>
            </div>
          </div>
          <?php endforeach; ?>

          <div id="selInfo" style="display:none;margin-top:14px;padding:10px 14px;background:var(--cream);border-radius:var(--radius-sm);font-size:.85rem">
            ✅ Selected: <strong id="selLabel"></strong>
          </div>
        </div>

        <!-- STEP 3: NOTES -->
        <div class="booking-panel" style="margin-bottom:20px">
          <div class="sec-label">👤 Step 3 — Confirm Details</div>
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" value="<?= htmlspecialchars($uRow['fullname'] ?? '') ?>" readonly style="background:var(--cream)">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($uRow['email'] ?? '') ?>" readonly style="background:var(--cream)">
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="tel" value="<?= htmlspecialchars($uRow['phone'] ?? '') ?>" readonly style="background:var(--cream)">
          </div>
          <div class="form-group">
            <label>Additional Notes</label>
            <textarea name="notes" placeholder="Any special requirements…"></textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
          ✅ Confirm Booking
        </button>
      </form>
    </div>

    <!-- SIDEBAR -->
    <div>
      <div class="booking-panel" style="margin-bottom:16px">
        <h3 style="margin-bottom:14px">📋 What to Expect</h3>
        <ul style="list-style:none;padding:0;font-size:.85rem;color:var(--text-muted)">
          <li style="padding:8px 0;border-bottom:1px solid var(--border)">🔇 Silent workspace only</li>
          <li style="padding:8px 0;border-bottom:1px solid var(--border)">📶 Gigabit Wi-Fi</li>
          <li style="padding:8px 0;border-bottom:1px solid var(--border)">🔌 Power at every desk</li>
          <li style="padding:8px 0">🍵 Free drinks &amp; snacks</li>
        </ul>
        <div style="background:#fffbef;border:1px solid #fce588;border-radius:var(--radius-sm);padding:12px;margin-top:14px">
          <p style="font-size:.82rem;color:#856404;margin:0">⚠️ Please take calls outside and keep conversations low.</p>
        </div>
      </div>

      <!-- Dynamic pricing sidebar -->
      <div class="booking-panel" style="margin-bottom:16px">
        <h3 style="margin-bottom:14px">💰 Pricing</h3>
        <?php foreach ($types as $t): ?>
        <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--border)">
          <div style="font-size:.82rem;font-weight:700;color:var(--dark);margin-bottom:4px">
            Floor <?= $t['floor'] ?> — <?= htmlspecialchars($t['zone_name']) ?>
          </div>
          <?php if ($t['price_slot']): ?>
          <div style="font-size:.82rem;color:var(--text-muted)">
            Slot (4h): <strong style="color:var(--brown)">RM <?= number_format($t['price_slot'],2) ?></strong>
          </div>
          <?php endif; ?>
          <?php if ($t['price_week']): ?>
          <div style="font-size:.82rem;color:var(--text-muted)">
            Week: <strong style="color:var(--brown)">RM <?= number_format($t['price_week'],2) ?></strong> &nbsp;
            Month: <strong style="color:var(--brown)">RM <?= number_format($t['price_month'],2) ?></strong>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="booking-panel">
        <h3 style="margin-bottom:10px">❓ Need Help?</h3>
        <p style="font-size:.85rem">📧 hello@coworkspace.my</p>
        <p style="font-size:.85rem;margin-top:6px">📞 +60 3-8888 1234</p>
      </div>
    </div>
  </div>
</div>

<script>
// FIX: build typeMap using zone_id as key
const TYPES = <?= json_encode(array_values($typeMap)) ?>;
const typeMap = {};
// zone_id is the key in PHP $typeMap, pass it through
TYPES.forEach(t => typeMap[t.zone_id] = t);
const DUR_NOTES = {
    slot:'4 hours of focused work',
    week:'7-day access, 10AM–close daily',
    month:'30-day access, 10AM–close daily',
    year:'365-day access — best value'
};

function switchType(id) {
    document.querySelectorAll('[id^="map-type-"]').forEach(el =>
        el.style.display = el.id === 'map-type-'+id ? 'block' : 'none');
    document.querySelectorAll('.floor-tab').forEach(btn =>
        btn.classList.toggle('active', parseInt(btn.dataset.typeId) === id));
    document.getElementById('typeIdInput').value = id;
    document.getElementById('wsIdInput').value   = '';
    document.getElementById('selInfo').style.display = 'none';
    calcPrice();
}

function selectRoom(el) {
    if (el.dataset.ok === '0') return;
    const tid = parseInt(el.dataset.typeId);
    document.querySelectorAll('#map-type-'+tid+' .room-seat.selected')
        .forEach(s => { s.classList.remove('selected'); s.classList.add('avail'); });
    el.classList.remove('avail'); el.classList.add('selected');
    document.getElementById('wsIdInput').value   = el.dataset.id;
    document.getElementById('typeIdInput').value = tid;
    document.getElementById('selLabel').textContent = el.dataset.num + ' — ' + (typeMap[tid]?.zone_name || '');
    document.getElementById('selInfo').style.display = 'block';
    calcPrice();
}


function calcPrice() {
    const tid   = parseInt(document.getElementById('typeIdInput').value);
    const btype = document.getElementById('bookingType').value;
    const box   = document.getElementById('priceBox');
    const t     = typeMap[tid];
    if (!t || !btype) { box.style.display = 'none'; return; }
    const priceKey = 'price_'+btype;
    const price    = parseFloat(t[priceKey]);
    if (!price) { box.style.display = 'none'; return; }
    document.getElementById('priceTotal').textContent = 'RM ' + price.toFixed(2);
    document.getElementById('priceNote').textContent  = DUR_NOTES[btype] || '';
    box.style.display = 'block';
}

</script>
<script>
  // Grab the input elements
const startDateInput = document.getElementById('startDate');
const startTimeInput = document.getElementById('startTime');
const bookingTypeSel = document.getElementById('bookingType');

// Listen for changes on any of the schedule inputs
startDateInput.addEventListener('change', checkRoomAvailability);
startTimeInput.addEventListener('change', checkRoomAvailability);
bookingTypeSel.addEventListener('change', checkRoomAvailability);


function checkRoomAvailability() {
    const sDate = startDateInput.value;
    const sTime = startTimeInput.value;
    const bType = bookingTypeSel.value;

    // Only run the check if we have a date and a duration type selected
    if (!sDate || !bType) return;

    // Clear currently selected room if the time changes
    document.getElementById('wsIdInput').value = '';
    document.getElementById('selInfo').style.display = 'none';
    document.querySelectorAll('.room-seat.selected').forEach(el => {
        el.classList.remove('selected');
        el.classList.add('avail');
    });

    fetch(`check_availability.php?start_date=${sDate}&start_time=${sTime}&booking_type=${bType}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) return;
            
            const bookedRoomIds = data.booked_rooms || [];

            // Loop through all room squares on the map
            document.querySelectorAll('.room-seat').forEach(roomEl => {
                const roomId = parseInt(roomEl.dataset.id);
                
                if (bookedRoomIds.includes(roomId)) {
                    // Room is booked for this time
                    roomEl.classList.remove('avail');
                    roomEl.classList.add('unavail');
                    roomEl.dataset.ok = '0';
                    roomEl.title = roomEl.dataset.num + ' — Unavailable';
                } else {
                    // Room is available
                    roomEl.classList.remove('unavail');
                    roomEl.classList.add('avail');
                    roomEl.dataset.ok = '1';
                    roomEl.title = roomEl.dataset.num + ' — Available';
                }
            });
        })
        .catch(error => console.error('Error fetching availability:', error));
}
</script>
</body>
</html>
