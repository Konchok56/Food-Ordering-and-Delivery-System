<?php
session_start();
include('../core/db.php');

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();
if ($role !== 'admin') { echo "<h2 style='color:red;text-align:center;margin-top:50px;'>⛔ Access Denied.</h2>"; exit; }

// All active orders that have a location
$riders = $pdo->query("
    SELECT o.id, o.status, o.delivery_lat, o.delivery_lng, o.location_updated_at,
           o.customer_name, o.delivery_address, o.delivery_city,
           o.delivery_partner_name, o.total
    FROM orders o
    WHERE o.status IN ('out_for_delivery', 'preparing', 'confirmed')
      AND o.delivery_lat IS NOT NULL AND o.delivery_lng IS NOT NULL
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$totalActive   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','preparing','out_for_delivery')")->fetchColumn();
$totalTransit  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='out_for_delivery'")->fetchColumn();
$totalToday    = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered' AND DATE(updated_at)=CURDATE()")->fetchColumn();
$totalRiders   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='delivery_partner' AND is_approved=1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Rider Map — Admin | SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --orange: #ff4f00; --dark: #1a0a00; --white: #ffffff; --shadow: 0 8px 32px rgba(26,10,0,0.15); }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #0f0500; color: #e8d5c0; overflow: hidden; }

        #map { position: absolute; inset: 0; z-index: 1; }

        /* ── Top Bar ── */
        .admin-topbar {
            position: absolute; top: 0; left: 0; right: 0; z-index: 20;
            background: rgba(26,10,0,0.92); backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,79,0,0.2);
            padding: 14px 28px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
        }
        .topbar-brand { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800; color: var(--orange); display: flex; align-items: center; gap: 8px; }
        .topbar-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: #fff; }
        .topbar-sub { font-size: 0.8rem; color: #8b6a44; }

        .stat-chips { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
        .chip { display: flex; align-items: center; gap: 6px; background: rgba(255,79,0,0.12); border: 1px solid rgba(255,79,0,0.25); border-radius: 999px; padding: 6px 14px; font-size: 0.82rem; font-weight: 700; color: #fff; }
        .chip span { color: var(--orange); font-family: 'Syne', sans-serif; font-size: 1rem; }

        .back-link { color: #c9a07d; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: color .2s; }
        .back-link:hover { color: var(--orange); }

        /* ── Sidebar Panel ── */
        .side-panel {
            position: absolute; top: 70px; right: 20px; bottom: 20px; z-index: 10;
            width: 320px; background: rgba(26,10,0,0.92); backdrop-filter: blur(12px);
            border: 1px solid rgba(255,79,0,0.2); border-radius: 22px;
            overflow-y: auto; display: flex; flex-direction: column; gap: 0;
        }
        .panel-header { padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.07); }
        .panel-header h3 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800; color: #fff; margin: 0 0 2px; }
        .panel-header p { font-size: 0.78rem; color: #8b6a44; margin: 0; }

        .rider-card {
            padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.05);
            cursor: pointer; transition: background .2s;
        }
        .rider-card:hover { background: rgba(255,79,0,0.08); }
        .rider-card.active-card { background: rgba(255,79,0,0.12); border-left: 3px solid var(--orange); }

        .rc-header { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .rc-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--orange); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: 'Syne', sans-serif; font-size: 0.95rem; flex-shrink: 0; }
        .rc-name { font-weight: 700; color: #fff; font-size: 0.9rem; }
        .rc-order { font-size: 0.75rem; color: #8b6a44; }

        .rc-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .rc-transit { background: rgba(255,79,0,0.15); color: var(--orange); }
        .rc-preparing { background: rgba(175,82,222,0.15); color: #bf5af2; }
        .rc-confirmed { background: rgba(0,122,255,0.12); color: #5ac8fa; }

        .rc-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 8px; font-size: 0.78rem; color: #8b6a44; }
        .rc-meta strong { color: #e8d5c0; display: block; font-size: 0.8rem; }

        .no-riders { padding: 40px 20px; text-align: center; color: #8b6a44; }
        .no-riders .icon { font-size: 2.5rem; margin-bottom: 12px; }
        .no-riders h4 { font-family: 'Syne', sans-serif; color: #fff; margin-bottom: 6px; }

        /* Marker */
        .map-marker-wrap {
            width: 44px; height: 44px;
            background: #ff4f00;
            border: 3px solid #fff;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(255,79,0,0.5);
            cursor: pointer;
        }
        .map-marker-wrap.pulse { animation: pa 2s infinite; }
        @keyframes pa {
            0%   { box-shadow: 0 0 0 0 rgba(255,79,0,0.7); }
            70%  { box-shadow: 0 0 0 14px rgba(255,79,0,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,79,0,0); }
        }

        /* Scrollbar */
        .side-panel::-webkit-scrollbar { width: 4px; }
        .side-panel::-webkit-scrollbar-thumb { background: rgba(255,79,0,0.3); border-radius: 4px; }
    </style>
</head>
<body>
    <div id="map"></div>

    <!-- Top Bar -->
    <div class="admin-topbar">
        <div class="topbar-brand">🗺️ SwiftBite</div>
        <div>
            <div class="topbar-title">Live Rider Map</div>
            <div class="topbar-sub">Real-time view of all active deliveries</div>
        </div>

        <div class="stat-chips">
            <div class="chip">🟡 Active <span><?php echo (int)$totalActive; ?></span></div>
            <div class="chip"><i class="fa-solid fa-motorcycle"></i> In Transit <span><?php echo (int)$totalTransit; ?></span></div>
            <div class="chip"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Today <span><?php echo (int)$totalToday; ?></span></div>
            <div class="chip"><i class="fa-solid fa-user"></i> Riders <span><?php echo (int)$totalRiders; ?></span></div>
        </div>

        <a href="dashboard.php" class="back-link">← Admin Panel</a>
    </div>

    <!-- Side Panel -->
    <div class="side-panel">
        <div class="panel-header">
            <h3><i class="fa-solid fa-motorcycle"></i> Active Riders</h3>
            <p><?php echo count($riders); ?> rider(s) with live location</p>
        </div>

        <?php if (empty($riders)): ?>
            <div class="no-riders">
                <div class="icon">📡</div>
                <h4>No Live Locations</h4>
                <p>Riders will appear here once they update their GPS position.</p>
            </div>
        <?php else: ?>
            <?php foreach ($riders as $r):
                $badgeClass = $r['status'] === 'out_for_delivery' ? 'rc-transit' : ($r['status'] === 'preparing' ? 'rc-preparing' : 'rc-confirmed');
                $badgeLabel = str_replace('_', ' ', $r['status']);
            ?>
            <div class="rider-card" onclick="flyToRider(<?php echo (float)$r['delivery_lat']; ?>, <?php echo (float)$r['delivery_lng']; ?>, <?php echo (int)$r['id']; ?>)">
                <div class="rc-header">
                    <div class="rc-avatar"><?php echo strtoupper(substr($r['delivery_partner_name'] ?? 'R', 0, 1)); ?></div>
                    <div>
                        <div class="rc-name"><?php echo htmlspecialchars($r['delivery_partner_name'] ?? 'Unassigned'); ?></div>
                        <div class="rc-order">Order #<?php echo str_pad($r['id'], 5, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <span class="rc-badge <?php echo $badgeClass; ?>" style="margin-left:auto;"><?php echo $badgeLabel; ?></span>
                </div>
                <div class="rc-meta">
                    <div><strong><?php echo htmlspecialchars($r['customer_name']); ?></strong>Customer</div>
                    <div><strong>Rs. <?php echo number_format((float)$r['total'], 0); ?></strong>Order Total</div>
                    <div style="grid-column:1/-1;"><strong><?php echo htmlspecialchars($r['delivery_address']); ?>, <?php echo htmlspecialchars($r['delivery_city']); ?></strong>Destination</div>
                    <?php if ($r['location_updated_at']): ?>
                    <div style="grid-column:1/-1;color:#8b6a44;">📡 Updated: <?php echo date('h:i A', strtotime($r['location_updated_at'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const ridersData = <?php echo json_encode(array_values($riders)); ?>;

        const map = L.map('map', {
            center: [27.7172, 85.3240],
            zoom: 13,
            zoomControl: true
        });

        // Dark-style tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            className: 'map-tiles'
        }).addTo(map);

        const markerMap = {};

        ridersData.forEach(rider => {
            if (!rider.delivery_lat || !rider.delivery_lng) return;

            const lat = parseFloat(rider.delivery_lat);
            const lng = parseFloat(rider.delivery_lng);
            const isTransit = rider.status === 'out_for_delivery';

            const icon = L.divIcon({
                className: '',
                html: `<div class="map-marker-wrap${isTransit ? ' pulse' : ''}">${isTransit ? '<i class="fa-solid fa-motorcycle"></i>' : '<i class="fa-solid fa-motorcycle"></i>'}</div>`,
                iconSize: [44, 44],
                iconAnchor: [22, 22]
            });

            const popup = `
                <div style="font-family:'DM Sans',sans-serif;min-width:200px;">
                    <strong style="font-size:1rem;">${rider.delivery_partner_name || 'Rider'}</strong><br>
                    <span style="color:#8b6a44;font-size:0.82rem;">Order #${String(rider.id).padStart(5,'0')}</span><br><br>
                    <i class="fa-solid fa-user"></i> ${rider.customer_name}<br>
                    <i class="fa-solid fa-location-dot"></i> ${rider.delivery_address}, ${rider.delivery_city}<br>
                    <i class="fa-solid fa-coins"></i> Rs. ${parseFloat(rider.total).toLocaleString()}<br>
                    <span style="display:inline-block;margin-top:6px;padding:3px 10px;background:rgba(255,79,0,0.1);color:#ff4f00;border-radius:999px;font-size:0.75rem;font-weight:700;text-transform:uppercase;">${rider.status.replace(/_/g,' ')}</span>
                </div>`;

            const marker = L.marker([lat, lng], { icon }).addTo(map);
            marker.bindPopup(popup);
            markerMap[rider.id] = marker;
        });

        // Auto-fit bounds if we have riders
        if (ridersData.length > 0) {
            const validRiders = ridersData.filter(r => r.delivery_lat && r.delivery_lng);
            if (validRiders.length > 0) {
                const bounds = L.latLngBounds(validRiders.map(r => [parseFloat(r.delivery_lat), parseFloat(r.delivery_lng)]));
                map.fitBounds(bounds, { padding: [100, 380] });
            }
        }

        function flyToRider(lat, lng, id) {
            map.flyTo([lat, lng], 16, { duration: 1.2 });
            if (markerMap[id]) markerMap[id].openPopup();
            document.querySelectorAll('.rider-card').forEach(c => c.classList.remove('active-card'));
            event.currentTarget.classList.add('active-card');
        }

        // Auto-refresh every 15 seconds
        setTimeout(() => location.reload(), 15000);
    </script>
</body>
</html>
