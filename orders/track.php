<?php
require_once '../core/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h2 style='text-align:center;margin-top:50px;'>" . __('order_not_found', 'Order not found!') . "</h2>");
}

// Try to get restaurant coordinates if available
$restaurantLat = 27.7172; // Kathmandu default
$restaurantLng = 85.3240;
if (!empty($order['restaurant_id'])) {
    $rStmt = $pdo->prepare("SELECT lat, lng FROM restaurants WHERE id = ? LIMIT 1");
    $rStmt->execute([$order['restaurant_id']]);
    $rest = $rStmt->fetch(PDO::FETCH_ASSOC);
    if ($rest && $rest['lat'] && $rest['lng']) {
        $restaurantLat = (float)$rest['lat'];
        $restaurantLng = (float)$rest['lng'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('track_order', 'Track Order'); ?> #<?php echo t_num(str_pad($order['id'], 5, '0', STR_PAD_LEFT)); ?> — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --orange: #ff4f00; --dark: #1a0a00; --cream: #fff8f0; --white: #fff; --shadow: 0 10px 40px rgba(26,10,0,0.12); }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--cream); overflow: hidden; }

        #map { width: 100%; height: 100vh; position: absolute; top: 0; left: 0; z-index: 1; }

        /* ── Header Overlay ── */
        .track-header {
            position: absolute; top: 20px; left: 20px; right: 20px;
            z-index: 10; display: flex; justify-content: space-between;
            align-items: flex-start; pointer-events: none; gap: 12px;
        }
        .back-btn {
            background: var(--white); padding: 12px 20px; border-radius: 16px;
            text-decoration: none; color: var(--dark); font-weight: 700;
            box-shadow: var(--shadow); display: flex; align-items: center;
            gap: 8px; pointer-events: auto; transition: all 0.2s; white-space: nowrap;
        }
        .back-btn:hover { transform: translateX(-4px); color: var(--orange); }

        .order-status-card {
            background: var(--white); padding: 18px 22px; border-radius: 22px;
            box-shadow: var(--shadow); pointer-events: auto; max-width: 300px; width: 100%;
        }
        .status-pill {
            display: inline-block; padding: 5px 12px; border-radius: 999px;
            font-size: 0.72rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 10px;
            background: rgba(255,79,0,0.1); color: var(--orange);
        }
        .order-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 800; color: var(--dark); margin: 0 0 2px; }
        .order-subtitle { color: #8b6a44; font-size: 0.82rem; margin: 0 0 14px; }
        .rider-info { display: flex; align-items: center; gap: 10px; padding-top: 12px; border-top: 1px solid #f0e6d9; }
        .rider-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--orange); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: 'Syne', sans-serif; font-size: 1rem; flex-shrink: 0; }
        .rider-details h4 { margin: 0; font-size: 0.88rem; color: var(--dark); font-weight: 700; }
        .rider-details p { margin: 0; font-size: 0.75rem; color: #8b6a44; }

        /* ── Bottom Card ── */
        .bottom-card {
            position: absolute; bottom: 20px; left: 20px; right: 20px;
            background: var(--white); padding: 20px 24px; border-radius: 26px;
            box-shadow: var(--shadow); z-index: 10; display: flex; flex-direction: column; gap: 14px;
        }
        .eta-row { display: flex; justify-content: space-between; align-items: center; }
        .eta-label { font-size: 0.82rem; color: #8b6a44; font-weight: 600; margin-bottom: 4px; }
        .eta-time { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--orange); }

        /* ── Route legend ── */
        .route-legend {
            display: flex; gap: 16px; flex-wrap: wrap;
        }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #8b6a44; font-weight: 600; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }

        /* ── Progress Steps ── */
        .steps { display: flex; align-items: center; gap: 0; }
        .step { display: flex; flex-direction: column; align-items: center; flex: 1; }
        .step-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; border: 2px solid #e2d5c5; background: #f5ede3; transition: all 0.4s; }
        .step-icon.done { background: var(--orange); border-color: var(--orange); }
        .step-label { font-size: 0.65rem; color: #8b6a44; margin-top: 4px; font-weight: 600; text-align: center; }
        .step-line { flex: 1; height: 2px; background: #e2d5c5; margin-bottom: 20px; transition: background 0.4s; }
        .step-line.done { background: var(--orange); }

        /* ── Marker Styles ── */
        .rider-marker { font-size: 1.4rem; display: flex; align-items: center; justify-content: center; }
        .pulse { animation: pulse-anim 2s infinite; }
        @keyframes pulse-anim {
            0% { filter: drop-shadow(0 0 0 rgba(255,79,0,0.7)); }
            70% { filter: drop-shadow(0 0 8px rgba(255,79,0,0)); }
            100% { filter: drop-shadow(0 0 0 rgba(255,79,0,0)); }
        }

        @media (max-width: 600px) {
            .track-header { flex-direction: column; }
            .order-status-card { max-width: none; }
        }
    </style>
</head>
<body>
    <div id="map"></div>

    <div class="track-header">
        <a href="../user/order_details.php?id=<?php echo $order['id']; ?>" class="back-btn"><?php echo __('back_arrow', '← Back'); ?></a>
        <div class="order-status-card">
            <?php
            $status_key = 'status_' . $order['status'];
            $status_label = str_replace('_', ' ', $order['status']);
            ?>
            <div class="status-pill" id="status-label"><?php echo htmlspecialchars(__($status_key, $status_label)); ?></div>
            <h2 class="order-title"><?php echo __('order_title_hash', 'Order'); ?> #<?php echo t_num(str_pad($order['id'], 5, '0', STR_PAD_LEFT)); ?></h2>
            <p class="order-subtitle" id="last-updated"><?php echo __('connecting_to_rider_dots', 'Connecting to rider...'); ?></p>
            <div class="rider-info">
                <div class="rider-avatar" id="rider-initial"><?php echo strtoupper(substr($order['delivery_partner_name'] ?? 'S', 0, 1)); ?></div>
                <div class="rider-details">
                    <h4 id="rider-name"><?php echo htmlspecialchars($order['delivery_partner_name'] ?? __('assigning_rider_dots', 'Assigning rider...')); ?></h4>
                    <p>🟢 <?php echo __('swiftbite_delivery_partner', 'SwiftBite Delivery Partner'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-card">
        <!-- Steps -->
        <div class="steps" id="steps-row">
            <div class="step">
                <div class="step-icon done" id="step-confirmed"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i></div>
                <div class="step-label"><?php echo __('step_confirmed_label', 'Confirmed'); ?></div>
            </div>
            <div class="step-line" id="line-1"></div>
            <div class="step">
                <div class="step-icon" id="step-preparing">🧑‍🍳</div>
                <div class="step-label"><?php echo __('step_preparing_label', 'Preparing'); ?></div>
            </div>
            <div class="step-line" id="line-2"></div>
            <div class="step">
                <div class="step-icon" id="step-transit"><i class="fa-solid fa-motorcycle"></i></div>
                <div class="step-label"><?php echo __('step_transit_label', 'On the Way'); ?></div>
            </div>
            <div class="step-line" id="line-3"></div>
            <div class="step">
                <div class="step-icon" id="step-delivered"><i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i></div>
                <div class="step-label"><?php echo __('step_delivered_label', 'Delivered'); ?></div>
            </div>
        </div>

        <!-- ETA + Legend -->
        <div class="eta-row">
            <div>
                <div class="eta-label"><?php echo __('estimated_delivery', 'Estimated Delivery'); ?></div>
                <div class="eta-time" id="eta-val"><?php echo __('calculating_dots', 'Calculating...'); ?></div>
            </div>
            <div class="route-legend">
                <div class="legend-item"><div class="legend-dot" style="background:#ff4f00;"></div> <?php echo __('rider', 'Rider'); ?></div>
                <div class="legend-item"><div class="legend-dot" style="background:#34c759;"></div> <?php echo __('restaurant', 'Restaurant'); ?></div>
                <div class="legend-item"><div class="legend-dot" style="background:#007aff;"></div> <?php echo __('destination', 'Destination'); ?></div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map, riderMarker;
        let doneRouteLine = null, remainRouteLine = null;

        const orderId = <?php echo (int)$order['id']; ?>;
        let riderLat  = <?php echo (float)($order['delivery_lat'] ?: 27.7172); ?>;
        let riderLng  = <?php echo (float)($order['delivery_lng'] ?: 85.3240); ?>;
        const restLat = <?php echo $restaurantLat; ?>;
        const restLng = <?php echo $restaurantLng; ?>;
        // Destination offset (approx — replace with real coords if available)
        const destLat = riderLat + 0.008;
        const destLng = riderLng + 0.005;
        const activeLang = '<?php echo $activeLang; ?>';

        function t_num_js(numStr) {
            if (activeLang !== 'ne') return numStr;
            const nepDigits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
            return numStr.toString().replace(/\d/g, d => nepDigits[d]);
        }

        function t_status_js(status) {
            const statuses = {
                'confirmed': '<?php echo __('status_confirmed', 'Confirmed'); ?>',
                'preparing': '<?php echo __('status_preparing', 'Preparing'); ?>',
                'out_for_delivery': '<?php echo __('status_out_for_delivery', 'Out for Delivery'); ?>',
                'delivered': '<?php echo __('status_delivered', 'Delivered'); ?>'
            };
            return statuses[status] || status.replace(/_/g, ' ');
        }

        // ── Icons ──
        const riderIcon = L.divIcon({
            className: '',
            html: '<div style="width:44px;height:44px;background:#ff4f00;border:3px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;box-shadow:0 4px 14px rgba(255,79,0,0.5);"><i class="fa-solid fa-motorcycle"></i></div>',
            iconSize: [44, 44], iconAnchor: [22, 22]
        });
        const restIcon = L.divIcon({
            className: '',
            html: '<div style="width:38px;height:38px;background:#34c759;border:3px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:0 4px 10px rgba(52,199,89,0.5);">🏪</div>',
            iconSize: [38, 38], iconAnchor: [19, 19]
        });
        const destIcon = L.divIcon({
            className: '',
            html: '<div style="width:38px;height:38px;background:#007aff;border:3px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:0 4px 10px rgba(0,122,255,0.5);"><i class="fa-solid fa-location-dot"></i></div>',
            iconSize: [38, 38], iconAnchor: [19, 38]
        });

        // ── OSRM Road Route Fetcher ──
        async function fetchRoadRoute(from, to) {
            const url = `https://router.project-osrm.org/route/v1/driving/${from[1]},${from[0]};${to[1]},${to[0]}?overview=full&geometries=geojson`;
            try {
                const res  = await fetch(url);
                const data = await res.json();
                if (data.routes && data.routes[0]) {
                    // GeoJSON coords are [lng, lat], Leaflet needs [lat, lng]
                    return data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                }
            } catch (e) { console.warn('OSRM fallback to straight line', e); }
            return [from, to]; // fallback
        }

        async function drawRoadRoute(from, rider, to) {
            // Remove old lines
            if (doneRouteLine)   { map.removeLayer(doneRouteLine);   doneRouteLine = null; }
            if (remainRouteLine) { map.removeLayer(remainRouteLine); remainRouteLine = null; }

            // Fetch both segments in parallel
            const [donePath, remainPath] = await Promise.all([
                fetchRoadRoute(from, rider),
                fetchRoadRoute(rider, to)
            ]);

            doneRouteLine = L.polyline(donePath, {
                color: '#34c759', weight: 5, opacity: 0.7, dashArray: '10 8'
            }).addTo(map);

            remainRouteLine = L.polyline(remainPath, {
                color: '#ff4f00', weight: 5, opacity: 0.9
            }).addTo(map);
        }

        function initMap() {
            map = L.map('map', { center: [riderLat, riderLng], zoom: 14, zoomControl: false });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Place markers
            riderMarker = L.marker([riderLat, riderLng], { icon: riderIcon }).addTo(map);
            riderMarker.bindPopup('<b><i class="fa-solid fa-motorcycle"></i> <?php echo __('rider_is_here_popup', 'Rider is here'); ?></b>').openPopup();
            L.marker([restLat, restLng], { icon: restIcon }).addTo(map).bindPopup('<b>🏪 <?php echo __('restaurant_popup', 'Restaurant'); ?></b>');
            L.marker([destLat, destLng], { icon: destIcon }).addTo(map).bindPopup('<b><i class="fa-solid fa-location-dot"></i> <?php echo __('delivery_address_popup', 'Delivery Address'); ?></b>');

            // Draw real road route
            drawRoadRoute([restLat, restLng], [riderLat, riderLng], [destLat, destLng]);

            // Fit bounds
            map.fitBounds(L.latLngBounds([
                [restLat, restLng], [riderLat, riderLng], [destLat, destLng]
            ]), { padding: [80, 80] });

            startTracking();
        }

        function updateSteps(status) {
            const steps = {
                'confirmed':        ['step-confirmed'],
                'preparing':        ['step-confirmed', 'step-preparing', 'line-1'],
                'out_for_delivery': ['step-confirmed', 'step-preparing', 'step-transit', 'line-1', 'line-2'],
                'delivered':        ['step-confirmed', 'step-preparing', 'step-transit', 'step-delivered', 'line-1', 'line-2', 'line-3'],
            };
            const done = steps[status] || [];
            ['step-confirmed','step-preparing','step-transit','step-delivered','line-1','line-2','line-3'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.toggle('done', done.includes(id));
            });
        }

        async function updateLocation() {
            try {
                const res  = await fetch(`../actions/get_order_location.php?order_id=${orderId}`);
                const data = await res.json();
                if (!data.success) return;

                const lat = parseFloat(data.lat) || riderLat;
                const lng = parseFloat(data.lng) || riderLng;

                // Smoothly animate marker
                animateMarker(riderMarker, [lat, lng]);

                // Redraw road route via OSRM
                drawRoadRoute([restLat, restLng], [lat, lng], [destLat, destLng]);

                // Update UI
                document.getElementById('status-label').textContent  = t_status_js(data.status || '');
                document.getElementById('last-updated').textContent   = '🟢 <?php echo __('live_updates_dots', 'Live — updates every 5s'); ?>';
                document.getElementById('rider-name').textContent     = data.rider_name || '<?php echo __('assigning_rider_dots', 'Assigning rider...'); ?>';
                document.getElementById('rider-initial').textContent  = (data.rider_name || 'R').charAt(0).toUpperCase();

                updateSteps(data.status);

                if (data.status === 'out_for_delivery') {
                    document.getElementById('eta-val').textContent = '<i class="fa-solid fa-rocket"></i> <?php echo __('arriving_soon', 'Arriving Soon'); ?>';
                } else if (data.status === 'delivered') {
                    document.getElementById('eta-val').textContent = '✓ <?php echo __('status_delivered', 'Delivered!'); ?>';
                    clearInterval(trackingInterval);
                } else {
                    document.getElementById('eta-val').textContent = '<?php echo __('status_preparing', 'Preparing...'); ?>';
                }

                riderLat = lat; riderLng = lng;
            } catch (e) { console.error('Tracking error:', e); }
        }

        function animateMarker(marker, newLatLng) {
            const start  = marker.getLatLng();
            const steps  = 40;
            const delay  = 50;
            let   step   = 0;
            const timer  = setInterval(() => {
                step++;
                marker.setLatLng([
                    start.lat + (newLatLng[0] - start.lat) * (step / steps),
                    start.lng + (newLatLng[1] - start.lng) * (step / steps),
                ]);
                if (step >= steps) clearInterval(timer);
            }, delay);
        }

        let trackingInterval;
        function startTracking() {
            updateLocation();
            trackingInterval = setInterval(updateLocation, 5000);
        }

        window.onload = initMap;
    </script>
</body>
</html>
