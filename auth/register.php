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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecIs/bztOx154gcB1agC9atiA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
        .role-btn .ri{ font-size:1.2rem; }
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
        .photo-icon{ font-size:1.5rem; }
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
                <div class="section-label">🏪 Restaurant Details</div>
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
                            <option value="Motorcycle" <?php echo ($old['rider_vehicle']??'')==='Motorcycle'?'selected':''; ?>><i class="fa-solid fa-motorcycle"></i> Motorcycle</option>
                            <option value="Bicycle"    <?php echo ($old['rider_vehicle']??'')==='Bicycle'?'selected':''; ?>><i class="fa-solid fa-bicycle"></i> Bicycle</option>
                            <option value="Scooter"    <?php echo ($old['rider_vehicle']??'')==='Scooter'?'selected':''; ?>><i class="fa-solid fa-motorcycle"></i> Scooter</option>
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
                                <div class="photo-icon">📷</div>
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

<script>
const titles = {
    user:             'Create Account',
    restaurant:       '<i class="fa-solid fa-utensils"></i> Register Restaurant',
    delivery_partner: '<i class="fa-solid fa-motorcycle"></i> Apply as Rider'
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
    document.getElementById('formTitle').textContent  = titles[role];
    document.getElementById('submitBtn').textContent  = titles[role];

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
</script>
</body>
</html>
