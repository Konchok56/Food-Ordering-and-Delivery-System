/**
 * SwiftBite — Client-Side Push Notifications
 */
(function() {
    // Prevent multiple initializations
    if (window.SwiftBiteNotifications) return;
    window.SwiftBiteNotifications = true;

    // Inject Toast CSS styles dynamically
    const styleEl = document.createElement('style');
    styleEl.textContent = `
        .sb-toast-container {
            position: fixed !important;
            top: 24px !important;
            right: 24px !important;
            z-index: 999999 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
            max-width: 380px !important;
            width: calc(100% - 48px) !important;
            pointer-events: none !important;
        }
        .sb-toast {
            background: rgba(26, 10, 0, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1.5px solid rgba(255, 79, 0, 0.3) !important;
            border-radius: 18px !important;
            padding: 16px !important;
            color: #fff !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35) !important;
            display: flex !important;
            gap: 14px !important;
            align-items: flex-start !important;
            pointer-events: auto !important;
            cursor: pointer !important;
            transform: translateX(120%) !important;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease !important;
            opacity: 0 !important;
            position: relative !important;
            overflow: hidden !important;
        }
        .sb-toast::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 6px !important;
            height: 100% !important;
            background: linear-gradient(to bottom, #ff4f00, #ff2400) !important;
        }
        .sb-toast.show {
            transform: translateX(0) !important;
            opacity: 1 !important;
        }
        .sb-toast-icon {
            font-size: 1.8rem !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(255, 79, 0, 0.15) !important;
            width: 44px !important;
            height: 44px !important;
            border-radius: 12px !important;
        }
        .sb-toast-content {
            flex-grow: 1 !important;
        }
        .sb-toast-title {
            font-family: 'Syne', sans-serif !important;
            font-weight: 800 !important;
            font-size: 0.95rem !important;
            margin: 0 0 4px 0 !important;
            color: #fff !important;
        }
        .sb-toast-msg {
            font-size: 0.84rem !important;
            color: #e8d5c0 !important;
            margin: 0 !important;
            line-height: 1.4 !important;
        }
        .sb-toast-close {
            background: none !important;
            border: none !important;
            color: #8b6a44 !important;
            font-size: 1.2rem !important;
            cursor: pointer !important;
            padding: 0 !important;
            line-height: 1 !important;
            margin-left: 4px !important;
            transition: color 0.2s !important;
        }
        .sb-toast-close:hover {
            color: #ff4f00 !important;
        }
    `;
    document.head.appendChild(styleEl);

    // Dynamic creation of Toast Container
    let container = document.querySelector('.sb-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'sb-toast-container';
        document.body.appendChild(container);
    }

    // Request native notification permission on user interaction
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            document.addEventListener('click', function askPerm() {
                Notification.requestPermission();
                document.removeEventListener('click', askPerm);
            }, { once: true });
        }
    }
    requestNotificationPermission();

    // Trigger local visual toast
    function showToast(notif) {
        const toast = document.createElement('div');
        toast.className = 'sb-toast';
        
        const iconHtml = notif.icon ? `<div class="sb-toast-icon">${notif.icon}</div>` : '<div class="sb-toast-icon">🔔</div>';
        
        toast.innerHTML = `
            ${iconHtml}
            <div class="sb-toast-content">
                <h4 class="sb-toast-title">${escapeHtml(notif.title)}</h4>
                <p class="sb-toast-msg">${escapeHtml(notif.message)}</p>
            </div>
            <button class="sb-toast-close" type="button">&times;</button>
        `;

        // Click on toast to follow link
        toast.addEventListener('click', function(e) {
            if (e.target.classList.contains('sb-toast-close')) {
                dismissToast(toast);
                return;
            }
            if (notif.link) {
                window.location.href = notif.link;
            }
        });

        container.appendChild(toast);
        
        // Trigger reflow to start transition
        toast.offsetHeight;
        toast.classList.add('show');

        // Browser Native Notification (no sound per user feedback)
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                const nativeNotif = new Notification(notif.title, {
                    body: notif.message,
                    icon: '/assets/img/logo.png',
                    silent: true // ensure it respects "no need audio"
                });
                nativeNotif.onclick = function() {
                    window.focus();
                    if (notif.link) window.location.href = notif.link;
                };
            } catch (err) {
                console.warn('Native notification failed', err);
            }
        }

        // Auto dismiss
        const timeoutId = setTimeout(() => {
            dismissToast(toast);
        }, 7000);

        toast.dataset.timeoutId = timeoutId;
    }

    function dismissToast(toast) {
        toast.classList.remove('show');
        clearTimeout(parseInt(toast.dataset.timeoutId, 10));
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 400);
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Polling Mechanism
    let seenNotifs = [];
    try {
        seenNotifs = JSON.parse(sessionStorage.getItem('sb_seen_notifications') || '[]');
    } catch(e) {}

    // Find base url from window config or floating menu attribute
    let baseUrl = '';
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        const src = scripts[i].getAttribute('src') || '';
        if (src.includes('assets/js/notifications.js')) {
            baseUrl = src.split('assets/js/notifications.js')[0];
            break;
        }
    }

    // Fallback: If baseUrl could not be resolved from script tags, default to config if present
    if (!baseUrl && window.SwiftBiteConfig && window.SwiftBiteConfig.baseUrl) {
        baseUrl = window.SwiftBiteConfig.baseUrl;
    }

    function checkNotifications() {
        fetch(baseUrl + 'actions/get_notification_count.php')
            .then(r => r.json())
            .then(data => {
                // Update badge if present on the page
                const notifBadge = document.getElementById('notifCountBadge');
                if (notifBadge) {
                    const c = parseInt(data.count, 10) || 0;
                    notifBadge.textContent = c > 0 ? c : '';
                    notifBadge.setAttribute('data-count', c);
                }

                // Check for new notifications to toast
                if (data.notifications && data.notifications.length > 0) {
                    let hasNew = false;
                    // Process old-to-new (reverse array) so toasts appear in order
                    const reversed = [...data.notifications].reverse();
                    reversed.forEach(notif => {
                        const notifId = parseInt(notif.id, 10);
                        if (!seenNotifs.includes(notifId)) {
                            seenNotifs.push(notifId);
                            hasNew = true;
                            // Show toast with a small delay if multiple to prevent overlapping visual popups
                            setTimeout(() => {
                                showToast(notif);
                            }, 300);
                        }
                    });

                    if (hasNew) {
                        try {
                            sessionStorage.setItem('sb_seen_notifications', JSON.stringify(seenNotifs));
                        } catch(e) {}
                    }
                }
            })
            .catch(err => console.warn('Error fetching notifications:', err));
    }

    // Initial check and start interval (poll every 6 seconds)
    setTimeout(checkNotifications, 1000);
    setInterval(checkNotifications, 6000);
})();
