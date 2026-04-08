<?php
require_once '../core/bootstrap.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root { --orange: #ff4f00; --dark: #1a1004; --cream: #fff8f0; --cream2: #fff0dc; --text: #3d2600; --muted: #8b6a44; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--cream); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card {
            background: #fff; border-radius: 32px; padding: 48px 40px; width: 100%; max-width: 460px;
            box-shadow: 0 20px 60px rgba(255,79,0,0.12);
        }
        .auth-logo { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--orange); text-align: center; margin-bottom: 8px; }
        .auth-logo span { color: var(--dark); }
        .auth-title { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--dark); text-align: center; margin-bottom: 6px; }
        .auth-subtitle { color: var(--muted); text-align: center; font-size: 0.92rem; margin-bottom: 24px; }
        
        /* Professional Flash Messages */
        .flash-msg {
            padding: 12px 18px; border-radius: 14px; font-size: 0.88rem; font-weight: 600; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .flash-error   { background: rgba(255,59,48,0.08); color: #cc2d25; border: 1px solid rgba(255,59,48,0.15); }
        .flash-success { background: rgba(52,199,89,0.08); color: #1a7a34; border: 1px solid rgba(52,199,89,0.15); }
        .flash-warning { background: rgba(255,184,48,0.12); color: #a06200; border: 1px solid rgba(255,184,48,0.2); }

        /* Role Toggle */
        .role-toggle { display: flex; gap: 10px; margin-bottom: 24px; }
        .role-btn {
            flex: 1; padding: 14px 10px; border: 2px solid var(--cream2); border-radius: 16px;
            background: var(--cream); cursor: pointer; transition: all 0.2s;
            font-family: 'DM Sans', sans-serif; font-size: 0.88rem; font-weight: 700;
            color: var(--muted); display: flex; flex-direction: column; align-items: center; gap: 6px;
        }
        .role-btn .role-icon { font-size: 1.5rem; }
        .role-btn.active { border-color: var(--orange); background: rgba(255,79,0,0.06); color: var(--orange); }
        
        /* Approval notice */
        .approval-notice {
            background: rgba(255,184,48,0.12); border: 1px solid rgba(255,184,48,0.3);
            border-radius: 14px; padding: 12px 16px;
            font-size: 0.82rem; color: #7a5700; font-weight: 500; margin-bottom: 20px;
            display: none;
        }
        .approval-notice strong { display: block; margin-bottom: 2px; font-weight: 700; }
        
        .auth-field { margin-bottom: 18px; }
        .auth-field label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--dark); margin-bottom: 6px; }
        .auth-field input, .auth-field select {
            width: 100%; padding: 14px 18px; border: 2px solid var(--cream2); border-radius: 16px;
            font-size: 0.95rem; color: var(--text); background: var(--cream); outline: none; transition: border-color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .auth-field input:focus, .auth-field select:focus { border-color: var(--orange); background: #fff; }
        .pwd-hint { font-size: 0.78rem; color: var(--muted); margin-top: 6px; }
        .auth-btn {
            width: 100%; padding: 16px; background: linear-gradient(135deg, var(--orange), #ff2400); color: #fff;
            border: none; border-radius: 18px; font-weight: 800; font-size: 1.05rem; cursor: pointer;
            transition: all 0.25s; box-shadow: 0 8px 30px rgba(255,79,0,0.3); margin-top: 8px;
            font-family: 'DM Sans', sans-serif;
        }
        .auth-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(255,79,0,0.4); }
        .auth-footer { text-align: center; margin-top: 24px; font-size: 0.92rem; color: var(--muted); }
        .auth-footer a { color: var(--orange); font-weight: 700; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }
        .auth-back { display: block; text-align: center; margin-top: 16px; color: var(--muted); font-size: 0.85rem; text-decoration: none; }
        .auth-back:hover { color: var(--orange); }
        .restaurant-fields { display: none; border-top: 1px dashed var(--cream2); margin-top: 4px; padding-top: 18px; }
        .section-label { font-size: 0.78rem; font-weight: 700; color: var(--orange); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-logo">Swift<span>Bite</span></div>
        <div class="auth-title">Create Account</div>
        <div class="auth-subtitle">Join SwiftBite — choose your account type</div>

        <?php echo renderFlash(); ?>

        <!-- Role Toggle -->
        <div class="role-toggle">
            <button type="button" class="role-btn <?php echo $selectedRole === 'user' ? 'active' : ''; ?>" id="btnUser" onclick="setRole('user')">
                <span class="role-icon">👤</span>
                Customer
            </button>
            <button type="button" class="role-btn <?php echo $selectedRole === 'restaurant' ? 'active' : ''; ?>" id="btnRestaurant" onclick="setRole('restaurant')">
                <span class="role-icon">🍽️</span>
                Restaurant Owner
            </button>
        </div>

        <!-- Approval notice (shown only for restaurant) -->
        <div class="approval-notice" id="approvalNotice" <?php echo $selectedRole === 'restaurant' ? 'style="display:block"' : ''; ?>>
            <strong>⏳ Pending Admin Approval</strong>
            Your restaurant account will be reviewed by an admin before you can log in.
        </div>

        <form action="../actions/register_action.php" method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($selectedRole); ?>">

            <div class="auth-field">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" placeholder="John Doe" required
                       value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
            </div>
            <div class="auth-field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" required
                       value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
            </div>
            <div class="auth-field">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required minlength="6">
                <div class="pwd-hint">At least 6 characters with letters and numbers</div>
            </div>

            <!-- Restaurant-only fields -->
            <div class="restaurant-fields" id="restaurantFields" <?php echo $selectedRole === 'restaurant' ? 'style="display:block"' : ''; ?>>
                <div class="section-label">🏪 Restaurant Details</div>
                <div class="auth-field">
                    <label for="rest_name">Restaurant Name</label>
                    <input type="text" name="rest_name" id="rest_name" placeholder="e.g. Burger Palace"
                           value="<?php echo htmlspecialchars($old['rest_name'] ?? ''); ?>">
                </div>
                <div class="auth-field">
                    <label for="rest_city">City</label>
                    <select name="rest_city" id="rest_city">
                        <option value="Kathmandu" <?php echo ($old['rest_city'] ?? '') === 'Kathmandu' ? 'selected' : ''; ?>>Kathmandu</option>
                        <option value="Lalitpur"  <?php echo ($old['rest_city'] ?? '') === 'Lalitpur'  ? 'selected' : ''; ?>>Lalitpur</option>
                        <option value="Bhaktapur" <?php echo ($old['rest_city'] ?? '') === 'Bhaktapur' ? 'selected' : ''; ?>>Bhaktapur</option>
                    </select>
                </div>
                <div class="auth-field">
                    <label for="rest_cuisine">Cuisine Type</label>
                    <select name="rest_cuisine" id="rest_cuisine">
                        <?php foreach (['Fast Food','Nepali','Italian','Chinese','Japanese','Healthy','Indian','Thai','Mixed'] as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo ($old['rest_cuisine'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="auth-field">
                    <label for="rest_phone">Restaurant Phone</label>
                    <input type="text" name="rest_phone" id="rest_phone" placeholder="01-4567890"
                           value="<?php echo htmlspecialchars($old['rest_phone'] ?? ''); ?>">
                </div>
            </div>

            <button class="auth-btn" type="submit" id="submitBtn">
                <?php echo $selectedRole === 'restaurant' ? '🍽️ Register Restaurant' : 'Create Account'; ?>
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
        <a href="../index.php" class="auth-back">← Back to SwiftBite</a>
    </div>

<script>
function setRole(role) {
    document.getElementById('roleInput').value = role;
    document.getElementById('restaurantFields').style.display = role === 'restaurant' ? 'block' : 'none';
    document.getElementById('approvalNotice').style.display = role === 'restaurant' ? 'block' : 'none';
    document.getElementById('btnUser').classList.toggle('active', role === 'user');
    document.getElementById('btnRestaurant').classList.toggle('active', role === 'restaurant');
    document.getElementById('submitBtn').textContent = role === 'restaurant' ? '🍽️ Register Restaurant' : 'Create Account';

    // Make restaurant fields required/not-required
    const restFields = document.querySelectorAll('#restaurantFields input, #restaurantFields select');
    restFields.forEach(f => { f.required = (role === 'restaurant') && f.id === 'rest_name'; });
}
// Init on load
setRole(document.getElementById('roleInput').value);
</script>
</body>
</html>

        <!-- Role Toggle -->
        <div class="role-toggle">
            <button type="button" class="role-btn <?php echo $selectedRole === 'user' ? 'active' : ''; ?>" id="btnUser" onclick="setRole('user')">
                <span class="role-icon">👤</span>
                Customer
            </button>
            <button type="button" class="role-btn <?php echo $selectedRole === 'restaurant' ? 'active' : ''; ?>" id="btnRestaurant" onclick="setRole('restaurant')">
                <span class="role-icon">🍽️</span>
                Restaurant Owner
            </button>
        </div>

        <!-- Approval notice (shown only for restaurant) -->
        <div class="approval-notice" id="approvalNotice" <?php echo $selectedRole === 'restaurant' ? 'style="display:block"' : ''; ?>>
            <strong>⏳ Pending Admin Approval</strong>
            Your restaurant account will be reviewed by an admin before you can log in.
        </div>

        <form action="../actions/register_action.php" method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($selectedRole); ?>">

            <div class="auth-field">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" placeholder="John Doe" required
                       value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
            </div>
            <div class="auth-field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" required
                       value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
            </div>
            <div class="auth-field">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required minlength="6">
                <div class="pwd-hint">At least 6 characters with letters and numbers</div>
            </div>

            <!-- Restaurant-only fields -->
            <div class="restaurant-fields" id="restaurantFields" <?php echo $selectedRole === 'restaurant' ? 'style="display:block"' : ''; ?>>
                <div class="section-label">🏪 Restaurant Details</div>
                <div class="auth-field">
                    <label for="rest_name">Restaurant Name</label>
                    <input type="text" name="rest_name" id="rest_name" placeholder="e.g. Burger Palace"
                           value="<?php echo htmlspecialchars($old['rest_name'] ?? ''); ?>">
                </div>
                <div class="auth-field">
                    <label for="rest_city">City</label>
                    <select name="rest_city" id="rest_city">
                        <option value="Kathmandu" <?php echo ($old['rest_city'] ?? '') === 'Kathmandu' ? 'selected' : ''; ?>>Kathmandu</option>
                        <option value="Lalitpur"  <?php echo ($old['rest_city'] ?? '') === 'Lalitpur'  ? 'selected' : ''; ?>>Lalitpur</option>
                        <option value="Bhaktapur" <?php echo ($old['rest_city'] ?? '') === 'Bhaktapur' ? 'selected' : ''; ?>>Bhaktapur</option>
                    </select>
                </div>
                <div class="auth-field">
                    <label for="rest_cuisine">Cuisine Type</label>
                    <select name="rest_cuisine" id="rest_cuisine">
                        <?php foreach (['Fast Food','Nepali','Italian','Chinese','Japanese','Healthy','Indian','Thai','Mixed'] as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo ($old['rest_cuisine'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="auth-field">
                    <label for="rest_phone">Restaurant Phone</label>
                    <input type="text" name="rest_phone" id="rest_phone" placeholder="01-4567890"
                           value="<?php echo htmlspecialchars($old['rest_phone'] ?? ''); ?>">
                </div>
            </div>

            <button class="auth-btn" type="submit" id="submitBtn">
                <?php echo $selectedRole === 'restaurant' ? '🍽️ Register Restaurant' : 'Create Account'; ?>
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
        <a href="../index.php" class="auth-back">← Back to SwiftBite</a>
    </div>

<script>
function setRole(role) {
    document.getElementById('roleInput').value = role;
    document.getElementById('restaurantFields').style.display = role === 'restaurant' ? 'block' : 'none';
    document.getElementById('approvalNotice').style.display = role === 'restaurant' ? 'block' : 'none';
    document.getElementById('btnUser').classList.toggle('active', role === 'user');
    document.getElementById('btnRestaurant').classList.toggle('active', role === 'restaurant');
    document.getElementById('submitBtn').textContent = role === 'restaurant' ? '🍽️ Register Restaurant' : 'Create Account';

    // Make restaurant fields required/not-required
    const restFields = document.querySelectorAll('#restaurantFields input, #restaurantFields select');
    restFields.forEach(f => { f.required = (role === 'restaurant') && f.id === 'rest_name'; });
}
// Init on load
setRole(document.getElementById('roleInput').value);
</script>
</body>
</html>
