<?php
session_start();
include('../core/db.php');
include('../core/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Get Order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h2 style='text-align:center;margin-top:50px;'>Order not found!</h2>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?> — SwiftBite</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=8">
    
    <!-- Leaflet CSS (FREE) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --orange: #ff4f00;
            --dark: #1a0a00;
            --cream: #fff8f0;
            --white: #ffffff;
            --shadow: 0 10px 40px rgba(26,10,0,0.1);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            margin: 0;
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 100vh;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        /* Overlay UI */
        .track-header {
            position: absolute;
            top: 24px; left: 24px; right: 24px;
            z-index: 10;
            display: flex; justify-content: space-between; align-items: flex-start;
            pointer-events: none;
        }

        .back-btn {
            background: var(--white); padding: 12px 20px; border-radius: 16px;
            text-decoration: none; color: var(--dark); font-weight: 700;
            box-shadow: var(--shadow); display: flex; align-items: center; gap: 8px;
            pointer-events: auto; transition: all 0.2s;
        }
        .back-btn:hover { transform: translateX(-4px); }

        .order-status-card {
            background: var(--white); padding: 20px 24px; border-radius: 24px;
            box-shadow: var(--shadow); pointer-events: auto; max-width: 320px; width: 100%;
        }

        .status-pill {
            display: inline-block; padding: 6px 14px; border-radius: 999px;
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 12px; background: rgba(255,79,0,0.1); color: var(--orange);
        }

        .order-title { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--dark); margin: 0 0 4px; }
        .order-subtitle { color: #8b6a44; font-size: 0.9rem; margin: 0 0 20px; }

        .rider-info { display: flex; align-items: center; gap: 12px; padding-top: 16px; border-top: 1px solid var(--cream); }
        .rider-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--orange); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: 'Syne', sans-serif; }
        .rider-details h4 { margin: 0; font-size: 0.95rem; color: var(--dark); }
        .rider-details p { margin: 0; font-size: 0.8rem; color: #8b6a44; }

        .bottom-card {
            position: absolute; bottom: 24px; left: 24px; right: 24px;
            background: var(--white); padding: 24px; border-radius: 28px;
            box-shadow: var(--shadow); z-index: 10; display: flex; flex-direction: column; gap: 16px;
        }

        .eta-box { display: flex; justify-content: space-between; align-items: center; }
        .eta-label { font-size: 0.9rem; color: #8b6a44; font-weight: 600; }
        .eta-time { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--orange); }

        .progress-track { height: 6px; background: var(--cream); border-radius: 3px; overflow: hidden; position: relative; }
        .progress-fill { height: 100%; background: var(--orange); width: 20%; transition: width 1s ease; }

        /* Leaflet Marker Styling */
        .rider-marker {
            background: var(--orange);
            border: 3px solid white;
            border-radius: 50%;
            width: 40px !important;
            height: 40px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(255,79,0,0.4);
            font-size: 1.2rem;
            margin-top: -20px;
            margin-left: -20px;
        }
        
        .pulse {
            animation: pulse-animation 2s infinite;
        }
        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0px rgba(255, 79, 0, 0.7); }
            100% { box-shadow: 0 0 0 20px rgba(255, 79, 0, 0); }
        }
    </style>
</head>
<body>

    <div id="map"></div>

    <div class="track-header">
        <a href="../user/order_details.php?id=<?php echo $order['id']; ?>" class="back-btn">
            <span>←</span> Back
        </a>

        <div class="order-status-card">
            <div class="status-pill" id="status-label"><?php echo str_replace('_', ' ', $order['status']); ?></div>
            <h2 class="order-title">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h2>
            <p class="order-subtitle" id="last-updated">Waiting for location...</p>
            
            <div class="rider-info">
                <div class="rider-avatar" id="rider-initial">
                    <?php echo strtoupper(substr($order['delivery_partner_name'] ?? 'S', 0, 1)); ?>
                </div>
                <div class="rider-details">
                    <h4 id="rider-name"><?php echo htmlspecialchars($order['delivery_partner_name'] ?? 'Searching for rider...'); ?></h4>
                    <p>SwiftBite Delivery Partner</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-card">
        <div class="eta-box">
            <div>
                <div class="eta-label">Estimated Delivery</div>
                <div class="eta-time" id="eta-val">Calculating...</div>
            </div>
            <div style="font-size: 2.5rem;">🛵</div>
        </div>
        <div class="progress-track">
            <div class="progress-fill" id="progress-bar" style="width: 20%;"></div>
        </div>
    </div>

    <!-- Leaflet JS (FREE) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let map, marker;
        const orderId = <?php echo (int)$order['id']; ?>;
        
        let currentLat = <?php echo (float)($order['delivery_lat'] ?: 27.7172); ?>;
        let currentLng = <?php echo (float)($order['delivery_lng'] ?: 85.3240); ?>;

        function initMap() {
            // Initialize Leaflet Map with OpenStreetMap tiles
            map = L.map('map', {
                center: [currentLat, currentLng],
                zoom: 16,
                zoomControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Custom Rider Icon
            const riderIcon = L.divIcon({
                className: 'rider-marker pulse',
                html: '🛵',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });

            marker = L.marker([currentLat, currentLng], { icon: riderIcon }).addTo(map);

            startTracking();
        }

        async function updateLocation() {
            try {
                const response = await fetch(`../actions/get_order_location.php?order_id=${orderId}`);
                const data = await response.json();
                
                if (data.success && data.lat && data.lng) {
                    const lat = parseFloat(data.lat);
                    const lng = parseFloat(data.lng);
                    
                    // Update Marker Position
                    marker.setLatLng([lat, lng]);
                    map.panTo([lat, lng]);
                    
                    // Update UI
                    document.getElementById('status-label').textContent = data.status.replace('_', ' ');
                    document.getElementById('last-updated').textContent = "Live updates every 5s";
                    document.getElementById('rider-name').textContent = data.rider_name || 'Assigned Rider';
                    document.getElementById('rider-initial').textContent = (data.rider_name || 'S').charAt(0).toUpperCase();

                    const statusMap = { 'pending': 10, 'confirmed': 25, 'preparing': 45, 'out_for_delivery': 75, 'delivered': 100 };
                    document.getElementById('progress-bar').style.width = (statusMap[data.status] || 20) + '%';
                    
                    if (data.status === 'out_for_delivery') {
                        document.getElementById('eta-val').textContent = 'Arriving Soon';
                    } else if (data.status === 'delivered') {
                        document.getElementById('eta-val').textContent = 'Delivered';
                        clearInterval(trackingInterval);
                    }
                }
            } catch (error) {
                console.error("Tracking Error:", error);
            }
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
