<?php
require_once '../core/bootstrap.php';

if (isLoggedIn()) { redirect('index.php'); }

$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_old']);
$selectedRole = $old['role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register — SwiftBite</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
    <script>(function(){var t=localStorage.getItem('sb-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <script src="../assets/js/theme.js"></script>
    <style>
        :root { --orange:#ff4f00; --dark:#1a1004; --cream:#fff8f0; --cream2:#ffe0c2; --text:#3d2600; --muted:#8b6a44; }
        *{ box-sizing:border-box; margin:0; padding:0; }

        body{
            font-family:'DM Sans',sans-serif;
            background: linear-gradient(135deg, #1a0a00 0%, #3d1500 50%, #1a0a00 100%);
            min-height:100vh;
            display:flex; align-items:center; justify-content:center;
            padding:16px;
        }

        /* ── Outer card ── */
        .auth-wrap{
            display:grid;
            grid-template-columns: 260px 1fr;
            width:100%; max-width:820px;
            background:var(--white);
            border-radius:28px;
            overflow:hidden;
            box-shadow:0 30px 80px rgba(0,0,0,0.35);
            min-height:0;
        }

        /* ── Left panel (brand + role toggle) ── */
        .auth-left{
            background: linear-gradient(160deg, #1a0a00, #3d1500);
            padding:32px 24px;
            display:flex; flex-direction:column;
        }
        .auth-logo{
            font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800;
            color:#ff6b1a; margin-bottom:4px;
        }
        .auth-logo span{ color:#fff; }
        .auth-tagline{ color:#c9a07d; font-size:0.8rem; margin-bottom:28px; }

        .role-label{ font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#8b6a44; margin-bottom:10px; }

        .role-btn{
            display:flex; align-items:center; gap:10px;
            width:100%; padding:11px 14px;
            border:1.5px solid rgba(255,255,255,0.08);
            border-radius:12px; background:transparent;
            color:#c9a07d; font-family:'DM Sans',sans-serif;
            font-size:0.88rem; font-weight:600;
            cursor:pointer; transition:all 0.2s; margin-bottom:8px;
            text-align:left;
        }
        .role-btn .ri{ 
            font-size:1.1rem; 
            width: 32px; height: 32px;
            background: rgba(255, 79, 0, 0.12);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #ff4f00 !important;
        }
        .role-btn.active .ri {
            background: rgba(255, 255, 255, 0.2);
            color: #fff !important;
        }
        .role-btn:hover{ background:rgba(255,79,0,0.12); color:#ff9a6b; border-color:rgba(255,79,0,0.3); }
        .role-btn.active{
            background:rgba(255,79,0,0.18);
            border-color:var(--orange);
            color:#fff;
            box-shadow:0 0 0 2px rgba(255,79,0,0.25);
        }
        .role-btn .role-check{ margin-left:auto; font-size:0.8rem; opacity:0; }
        .role-btn.active .role-check{ opacity:1; }

        .approval-notice{
            background:rgba(255,184,48,0.12); border:1px solid rgba(255,184,48,0.3);
            border-radius:10px; padding:10px 12px;
            font-size:0.76rem; color:#c9a07d; margin-top:auto;
            display:none; line-height:1.5;
        }
        .approval-notice strong{ display:block; color:#f0b429; margin-bottom:2px; }

        /* ── Right panel (form) ── */
        .auth-right{
            padding:28px 28px 24px;
            display:flex; flex-direction:column;
            overflow-y:auto;
            max-height:calc(100vh - 32px);
        }
        .auth-title{
            font-family:'Syne',sans-serif; font-size:1.4rem;
            font-weight:800; color:var(--dark); margin-bottom:4px;
        }
        .auth-subtitle{ color:var(--muted); font-size:0.82rem; margin-bottom:16px; }

        /* Flash */
        .flash-msg{
            padding:10px 14px; border-radius:10px; font-size:0.82rem;
            font-weight:600; margin-bottom:14px;
            display:flex; align-items:center; gap:8px;
        }
        .flash-error  { background:rgba(255,59,48,0.08);  color:#cc2d25; border:1px solid rgba(255,59,48,0.2); }
        .flash-success{ background:rgba(52,199,89,0.08);  color:#1a7a34; border:1px solid rgba(52,199,89,0.2); }

        /* Fields */
        .auth-field{ margin-bottom:11px; }
        .auth-field label{ display:block; font-weight:600; font-size:0.78rem; color:var(--dark); margin-bottom:4px; }
        .auth-field input,
        .auth-field select{
            width:100%; padding:10px 13px;
            border:1.5px solid var(--cream2); border-radius:10px;
            font-size:0.88rem; color:var(--text);
            background:var(--cream); outline:none;
            transition:border-color 0.2s; font-family:'DM Sans',sans-serif;
        }
        .auth-field input:focus,
        .auth-field select:focus{ border-color:var(--orange); background:#fff; }
        .pwd-hint{ font-size:0.72rem; color:var(--muted); margin-top:3px; }

        .field-row{ display:grid; grid-template-columns:1fr 1fr; gap:10px; }

        /* Divider */
        .section-label{
            font-size:0.7rem; font-weight:700; color:var(--orange);
            text-transform:uppercase; letter-spacing:0.5px;
            margin:4px 0 10px; padding-top:10px;
            border-top:1px dashed var(--cream2);
            display: flex; align-items: center; gap: 6px;
        }

        /* Photo upload */
        .photo-upload-box{
            border:1.5px dashed var(--cream2); border-radius:10px;
            padding:12px; text-align:center; cursor:pointer;
            transition:all 0.2s; position:relative; background:var(--cream);
        }
        .photo-upload-box:hover{ border-color:var(--orange); background:rgba(255,79,0,0.03); }
        .photo-upload-box input[type=file]{
            position:absolute; inset:0; opacity:0;
            cursor:pointer; width:100%; height:100%;
        }
        .photo-preview{
            width:48px; height:48px; border-radius:50%;
            object-fit:cover; margin:0 auto 4px;
            display:none; border:2px solid var(--orange);
        }
        .photo-icon{ font-size:1.5rem; color: var(--orange); }
        .photo-label{ font-size:0.78rem; font-weight:600; color:var(--muted); margin-top:2px; }
        .photo-hint{ font-size:0.7rem; color:#bbb; }

        /* Extra fields */
        .extra-fields{ display:none; }

        /* Button */
        .auth-btn{
            width:100%; padding:12px;
            background:linear-gradient(135deg,var(--orange),#ff2400);
            color:#fff; border:none; border-radius:12px;
            font-weight:800; font-size:0.95rem; cursor:pointer;
            transition:all 0.25s; box-shadow:0 6px 20px rgba(255,79,0,0.3);
            margin-top:10px; font-family:'DM Sans',sans-serif;
        }
        .auth-btn:hover{ transform:translateY(-2px); box-shadow:0 10px 30px rgba(255,79,0,0.4); }

        .auth-footer{ text-align:center; margin-top:12px; font-size:0.82rem; color:var(--muted); }
        .auth-footer a{ color:var(--orange); font-weight:700; text-decoration:none; }
        .auth-back{ display:block; text-align:center; margin-top:6px; color:#c9a07d; font-size:0.78rem; text-decoration:none; }
        .auth-back:hover{ color:var(--orange); }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .auth-wrap{ grid-template-columns: 1fr; max-width:420px; }
            .auth-left{ padding:20px; flex-direction:row; flex-wrap:wrap; gap:8px; align-items:center; }
            .auth-logo{ margin-bottom:0; }
            .auth-tagline{ display:none; }
            .role-label{ display:none; }
            .role-btn{ padding:8px 12px; margin-bottom:0; flex:1; justify-content:center; font-size:0.78rem; }
            .role-btn .role-check{ display:none; }
            .approval-notice{ display:none !important; }
            .auth-right{ max-height:none; padding:20px; }
        }

        /* ── Dark Theme Overrides ── */
        [data-theme="dark"] .auth-wrap { background: #120800; border: 1px solid rgba(255,255,255,0.05); }
        [data-theme="dark"] .auth-title { color: #fff; }
        [data-theme="dark"] .auth-subtitle { color: #c9a07d; }
        [data-theme="dark"] .auth-field label { color: #fff; }
        [data-theme="dark"] .auth-field input, 
        [data-theme="dark"] .auth-field select { 
            background: rgba(255,255,255,0.04); 
            border-color: rgba(255,255,255,0.1); 
            color: #fff; 
        }
        [data-theme="dark"] .auth-field input:focus, 
        [data-theme="dark"] .auth-field select:focus { 
            border-color: var(--orange); 
            background: rgba(255,255,255,0.07); 
        }
        [data-theme="dark"] .section-label { border-top-color: rgba(255,255,255,0.05); }
        [data-theme="dark"] .photo-upload-box { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.1); }
        [data-theme="dark"] .photo-label { color: #c9a07d; }
        [data-theme="dark"] .pwd-hint { color: #8b6a44; }
    </style>
</head>
<body>
<div class="auth-wrap">

    <!-- LEFT: Brand + Role Switcher -->
    <div class="auth-left">
        <div style="display:flex; align-items:center; width:100%; justify-content:space-between; margin-bottom:4px;">
            <div class="auth-logo">Swift<span>Bite</span></div>
            <!-- Theme Toggle -->
            <button id="theme-toggle" class="theme-toggle-btn" style="width:32px; height:32px; font-size:0.85rem; background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.2); color:#fff;" title="Toggle theme">
                <span class="theme-icon theme-icon-sun">&#9728;</span>
                <span class="theme-icon theme-icon-moon"><i class="fa-solid fa-moon"></i></span>
            </button>
        </div>
        <div class="auth-tagline">Fast. Fresh. Delivered.</div>

        <div class="role-label">Choose account type</div>

        <button type="button" class="role-btn <?php echo $selectedRole==='user'?'active':''; ?>" id="btnUser" onclick="setRole('user')">
            <span class="ri"><i class="fa-solid fa-user"></i></span> Customer
            <span class="role-check">✓</span>
        </button>
        <button type="button" class="role-btn <?php echo $selectedRole==='restaurant'?'active':''; ?>" id="btnRestaurant" onclick="setRole('restaurant')">
            <span class="ri"><i class="fa-solid fa-utensils"></i></span> Restaurant Owner
            <span class="role-check">✓</span>
        </button>
        <button type="button" class="role-btn <?php echo $selectedRole==='delivery_partner'?'active':''; ?>" id="btnRider" onclick="setRole('delivery_partner')">
            <span class="ri"><i class="fa-solid fa-motorcycle"></i></span> Delivery Rider
            <span class="role-check">✓</span>
        </button>

        <div class="approval-notice" id="approvalNotice"
             <?php echo in_array($selectedRole,['restaurant','delivery_partner']) ? 'style="display:block"' : ''; ?>>
            <strong><i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i> Pending Approval</strong>
            Your account will be reviewed by an admin before you can log in.
        </div>
    </div>

    <!-- RIGHT: Form -->
    <div class="auth-right">
        <div class="auth-title" id="formTitle">Create Account</div>
        <div class="auth-subtitle">Fill in the details below to get started</div>

        <?php echo renderFlash(); ?>

        <form action="../actions/register_action.php" method="POST" enctype="multipart/form-data">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($selectedRole); ?>">

            <!-- Common fields -->
            <div class="auth-field">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="John Doe" required
                       value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
            </div>
            <div class="auth-field">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@example.com" required
                       value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
            </div>
            <div class="auth-field">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required minlength="6">
                <div class="pwd-hint">At least 6 characters</div>
            </div>

            <!-- ── Restaurant fields ── -->
            <div class="extra-fields" id="restaurantFields"
                 <?php echo $selectedRole==='restaurant' ? 'style="display:block"' : ''; ?>>
                <div class="section-label"><i class="fa-solid fa-utensils"></i> Restaurant Details</div>
                <div class="auth-field">
                    <label>Restaurant Name</label>
                    <input type="text" name="rest_name" placeholder="e.g. Burger Palace"
                           value="<?php echo htmlspecialchars($old['rest_name'] ?? ''); ?>">
                </div>
                <div class="field-row">
                    <div class="auth-field">
                        <label>City</label>
                        <select name="rest_city">
                            <?php foreach (['Kathmandu','Lalitpur','Bhaktapur'] as $c): ?>
                                <option value="<?php echo $c; ?>" <?php echo ($old['rest_city']??'')===$c?'selected':''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="auth-field">
                        <label>Cuisine</label>
                        <select name="rest_cuisine">
                            <?php foreach (['Fast Food','Nepali','Italian','Chinese','Japanese','Healthy','Indian','Thai','Mixed'] as $c): ?>
                                <option value="<?php echo $c; ?>" <?php echo ($old['rest_cuisine']??'')===$c?'selected':''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Restaurant Phone</label>
                    <input type="text" name="rest_phone" placeholder="01-4567890"
                           value="<?php echo htmlspecialchars($old['rest_phone'] ?? ''); ?>">
                </div>
                <div class="auth-field">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <label style="margin-bottom:0;">Restaurant Address</label>
                        <button type="button" onclick="openRegMap()"
                            style="background:none;border:none;color:#ff4f00;font-size:0.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:4px;padding:0;">
                            <i class="fa-solid fa-map-location-dot"></i> Pick on Map
                        </button>
                    </div>
                    <input type="text" name="rest_address" id="regAddressInput" placeholder="Click 'Pick on Map' or type your address"
                           value="<?php echo htmlspecialchars($old['rest_address'] ?? ''); ?>">
                    <input type="hidden" name="rest_lat" id="regLatInput" value="">
                    <input type="hidden" name="rest_lng" id="regLngInput" value="">
                </div>
            </div>

            <!-- ── Rider fields ── -->
            <div class="extra-fields" id="riderFields"
                 <?php echo $selectedRole==='delivery_partner' ? 'style="display:block"' : ''; ?>>
                <div class="section-label"><i class="fa-solid fa-motorcycle"></i> Rider Details</div>
                <div class="field-row">
                    <div class="auth-field">
                        <label>Phone Number</label>
                        <input type="text" name="rider_phone" placeholder="98XXXXXXXX"
                               value="<?php echo htmlspecialchars($old['rider_phone'] ?? ''); ?>">
                    </div>
                    <div class="auth-field">
                        <label>Vehicle Type</label>
                        <select name="rider_vehicle">
                            <option value="Motorcycle" <?php echo ($old['rider_vehicle']??'')==='Motorcycle'?'selected':''; ?>>🏍️ Motorcycle</option>
                            <option value="Bicycle"    <?php echo ($old['rider_vehicle']??'')==='Bicycle'?'selected':''; ?>>🚲 Bicycle</option>
                            <option value="Scooter"    <?php echo ($old['rider_vehicle']??'')==='Scooter'?'selected':''; ?>>🛵 Scooter</option>
                            <option value="Car"        <?php echo ($old['rider_vehicle']??'')==='Car'?'selected':''; ?>>🚗 Car</option>
                        </select>
                    </div>
                </div>
                <div class="field-row">
                    <div class="auth-field">
                        <label>City / Area</label>
                        <input type="text" name="rider_address" placeholder="e.g. Baneshwor"
                               value="<?php echo htmlspecialchars($old['rider_address'] ?? ''); ?>">
                    </div>
                    <div class="auth-field">
                        <label>Profile Photo <span style="color:#bbb;font-weight:400;">(required)</span></label>
                        <div class="photo-upload-box" id="photoBox">
                            <input type="file" name="rider_photo" id="riderPhoto" accept="image/*" onchange="previewPhoto(this)">
                            <img class="photo-preview" id="photoPreview" src="" alt="">
                            <div id="photoContent">
                                <div class="photo-icon"><i class="fa-solid fa-camera"></i></div>
                                <div class="photo-label">Upload photo</div>
                                <div class="photo-hint">JPG/PNG · max 2MB</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="auth-btn" type="submit" id="submitBtn">Create Account</button>
        </form>

        <div class="auth-footer">Already have an account? <a href="login.php">Sign In</a></div>
        <a href="../index.php" class="auth-back">← Back to SwiftBite</a>
    </div>
</div>

<!-- ── Map Modal ── -->
<div id="regMapOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
  <div style="background:#fff;border-radius:24px;width:92%;max-width:680px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,0.35);display:flex;flex-direction:column;max-height:90vh;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ffe0c2;">
      <strong style="font-family:'Syne',sans-serif;font-size:1rem;color:#1a1004;"><i class="fa-solid fa-map-location-dot" style="color:#ff4f00;margin-right:6px;"></i>Set Restaurant Location</strong>
      <button onclick="closeRegMap()" style="width:32px;height:32px;border-radius:50%;border:none;background:#fff0dc;cursor:pointer;font-size:0.9rem;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div style="padding:10px 14px;display:flex;gap:8px;background:#fff8f0;border-bottom:1px solid #ffe0c2;flex-wrap:wrap;">
      <input type="text" id="regMapSearch" placeholder="Search address…" style="flex:1;min-width:140px;padding:8px 12px;border:1.5px solid #ffe0c2;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:0.85rem;outline:none;">
      <button onclick="doRegSearch()" style="padding:8px 14px;background:#1a1004;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.82rem;cursor:pointer;"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
      <button id="regGpsBtn" onclick="doRegGps()" style="padding:8px 14px;background:#ff4f00;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:0.82rem;cursor:pointer;"><i class="fa-solid fa-location-crosshairs"></i> GPS</button>
    </div>
    <div id="regLeafletMap" style="height:320px;width:100%;"></div>
    <div style="padding:12px 16px;display:flex;gap:10px;align-items:center;border-top:1px solid #ffe0c2;">
      <div id="regPickedAddr" style="flex:1;font-size:0.82rem;color:#3d2600;background:#fff8f0;padding:8px 12px;border-radius:10px;line-height:1.5;"><span style="color:#8b6a44;">Click on the map to set location.</span></div>
      <button id="regConfirmBtn" onclick="confirmRegMap()" disabled style="padding:10px 18px;background:linear-gradient(135deg,#ff4f00,#ff2400);color:#fff;border:none;border-radius:12px;font-weight:800;font-size:0.88rem;cursor:pointer;opacity:0.5;">
        <i class="fa-solid fa-check"></i> Confirm
      </button>
    </div>
  </div>
</div>

<script>
const titles = {
    user:             'Create Account',
    restaurant:       '<i class="fa-solid fa-utensils" style="color:var(--orange)"></i> Register Restaurant',
    delivery_partner: '<i class="fa-solid fa-motorcycle" style="color:var(--orange)"></i> Apply as Rider'
};
const btnMap = {
    user: 'btnUser', restaurant: 'btnRestaurant', delivery_partner: 'btnRider'
};

function setRole(role) {
    document.getElementById('roleInput').value = role;

    // Extra fields
    document.getElementById('restaurantFields').style.display = role === 'restaurant'       ? 'block' : 'none';
    document.getElementById('riderFields').style.display      = role === 'delivery_partner' ? 'block' : 'none';

    // Approval notice
    document.getElementById('approvalNotice').style.display =
        (role === 'restaurant' || role === 'delivery_partner') ? 'block' : 'none';

    // Active button
    Object.values(btnMap).forEach(id => document.getElementById(id).classList.remove('active'));
    document.getElementById(btnMap[role]).classList.add('active');

    // Titles & button label
    document.getElementById('formTitle').innerHTML  = titles[role];
    document.getElementById('submitBtn').innerHTML  = titles[role];

    // Rider photo required
    const rPhoto = document.getElementById('riderPhoto');
    if (rPhoto) rPhoto.required = (role === 'delivery_partner');
}

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('photoPreview');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('photoContent').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Init
setRole(document.getElementById('roleInput').value);

// ── Map Modal Logic (Registration) ──
let regMap = null, regMarker = null, regPickedAddr = '', regPickedLat = '', regPickedLng = '';

function openRegMap() {
    const ov = document.getElementById('regMapOverlay');
    ov.style.display = 'flex';
    setTimeout(() => {
        if (!regMap) {
            regMap = L.map('regLeafletMap').setView([27.7172, 85.3240], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap', maxZoom: 19
            }).addTo(regMap);
            regMap.on('click', e => regPlaceMarker(e.latlng.lat, e.latlng.lng));
        }
        regMap.invalidateSize();
        const cur = document.getElementById('regAddressInput').value.trim();
        if (cur) document.getElementById('regMapSearch').value = cur;
    }, 100);
}
function closeRegMap() { document.getElementById('regMapOverlay').style.display = 'none'; }
document.getElementById('regMapOverlay').addEventListener('click', e => { if (e.target === document.getElementById('regMapOverlay')) closeRegMap(); });

function regPlaceMarker(lat, lon) {
    if (regMarker) regMarker.setLatLng([lat, lon]);
    else regMarker = L.marker([lat, lon]).addTo(regMap);
    regPickedLat = lat; regPickedLng = lon;
    const addr = document.getElementById('regPickedAddr');
    addr.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Fetching address…';
    const btn = document.getElementById('regConfirmBtn');
    btn.disabled = true; btn.style.opacity = '0.5';
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`)
        .then(r => r.json()).then(d => {
            regPickedAddr = d.display_name || `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
            addr.textContent = regPickedAddr;
            btn.disabled = false; btn.style.opacity = '1';
        }).catch(() => {
            regPickedAddr = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
            addr.textContent = regPickedAddr;
            btn.disabled = false; btn.style.opacity = '1';
        });
}
function confirmRegMap() {
    if (!regPickedAddr) return;
    document.getElementById('regAddressInput').value = regPickedAddr;
    document.getElementById('regLatInput').value = regPickedLat;
    document.getElementById('regLngInput').value = regPickedLng;
    closeRegMap();
}
function doRegGps() {
    if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
    const btn = document.getElementById('regGpsBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    navigator.geolocation.getCurrentPosition(pos => {
        regMap.setView([pos.coords.latitude, pos.coords.longitude], 17);
        regPlaceMarker(pos.coords.latitude, pos.coords.longitude);
        btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> GPS';
    }, () => {
        alert('Could not get location.'); btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> GPS';
    }, { enableHighAccuracy: true, timeout: 10000 });
}
function doRegSearch() {
    const q = document.getElementById('regMapSearch').value.trim();
    if (!q) return;
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`)
        .then(r => r.json()).then(res => {
            if (res.length) { regMap.setView([+res[0].lat, +res[0].lon], 16); regPlaceMarker(+res[0].lat, +res[0].lon); }
            else alert('Address not found.');
        });
}
document.getElementById('regMapSearch').addEventListener('keydown', e => { if (e.key === 'Enter') doRegSearch(); });
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>
