/**
 * SwiftBite — Global AJAX Cart System
 * Handles add-to-cart, toast notifications, and cart badge updates on ALL pages.
 */
(function () {
    'use strict';

    // ── Toast HTML (injected once) ──────────────────────────
    if (!document.getElementById('sbCartToast')) {
        const toastHTML = `
        <div class="sb-toast" id="sbCartToast">
            <span class="sb-toast-icon">🛒</span>
            <span class="sb-toast-msg" id="sbCartToastMsg">Added to cart!</span>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', toastHTML);
    }

    const toast = document.getElementById('sbCartToast');
    const toastMsg = document.getElementById('sbCartToastMsg');
    let toastTimer;

    function showToast(message, type) {
        toastMsg.textContent = message;
        toast.className = 'sb-toast show';
        if (type === 'error') toast.classList.add('error');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 2800);
    }

    // ── Update ALL cart count badges on the page ────────────
    function updateCartBadges(count) {
        document.querySelectorAll('#cartCount, .cart-count, [data-cart-count]').forEach(el => {
            el.textContent = count;
        });
    }

    // ── AJAX Add to Cart ────────────────────────────────────
    function handleAddToCart(form, btn) {
        const data = new FormData(form);
        const foodName = btn?.getAttribute('data-name') || data.get('food_name') || 'Item';

        // Animate button
        if (btn) {
            btn.classList.add('sb-adding');
            btn.dataset.originalText = btn.textContent;
            btn.textContent = '✓';
        }

        fetch('actions/add_to_cart.php', {
            method: 'POST',
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                showToast('"' + foodName + '" added to cart!', 'success');
                updateCartBadges(json.cart_count);

                if (btn) {
                    btn.classList.remove('sb-adding');
                    btn.classList.add('sb-added');
                    setTimeout(() => {
                        btn.classList.remove('sb-added');
                        btn.textContent = btn.dataset.originalText || '+';
                    }, 1200);
                }
            } else if (json.redirect) {
                window.location.href = json.redirect;
            } else {
                showToast(json.message || 'Error adding to cart', 'error');
                resetBtn(btn);
            }
        })
        .catch(() => {
            // Fallback: regular form submit
            form.submit();
        });
    }

    function resetBtn(btn) {
        if (btn) {
            btn.classList.remove('sb-adding', 'sb-added');
            btn.textContent = btn.dataset.originalText || '+';
        }
    }

    // ── Intercept ALL add-to-cart forms ─────────────────────
    document.addEventListener('submit', function (e) {
        const form = e.target;
        // Match forms that POST to add_to_cart.php
        if (form.tagName === 'FORM' && form.action && form.action.includes('add_to_cart.php')) {
            e.preventDefault();
            e.stopPropagation();
            const btn = form.querySelector('.add-btn, .menu-add-btn, .detail-add-btn, [type="submit"]');
            handleAddToCart(form, btn);
        }
    }, true); // capture phase to beat inline onclick handlers

    // ── Fetch real cart count on page load ──────────────────
    fetch('actions/get_cart_count.php', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(json => {
        if (json.success) updateCartBadges(json.count);
    })
    .catch(() => {}); // silent fail

    // ── Expose globally ────────────────────────────────────
    window.SwiftBiteCart = {
        showToast,
        updateCartBadges,
        handleAddToCart,
    };
})();
