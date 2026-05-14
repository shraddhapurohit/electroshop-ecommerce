// ============================================================
//  ElectroShop – Main JavaScript
// ============================================================

/* ── Toast notification helper ─────────────────────────────── */
function showToast(message, type = 'success') {
    let toast = document.getElementById('esToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'esToast';
        toast.className = 'es-toast';
        toast.innerHTML = `<span class="toast-icon"><i class="fas fa-check-circle"></i></span>
                           <span class="toast-msg"></span>`;
        document.body.appendChild(toast);
    }
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
    toast.className = `es-toast ${type}`;
    toast.querySelector('.toast-icon i').className = `fas ${icons[type] || icons.success}`;
    toast.querySelector('.toast-msg').textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

/* ── Add to Cart (AJAX) ─────────────────────────────────────── */
function addToCart(productId, button) {
    const base = getBase();
    fetch(`${base}cart.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            updateCartBadge(data.cart_count);
            if (button) {
                button.innerHTML = '<i class="fas fa-check me-1"></i> Added';
                setTimeout(() => { button.innerHTML = '<i class="fas fa-shopping-cart me-1"></i> Add to Cart'; }, 2000);
            }
        } else {
            showToast(data.message || 'Please login first', 'error');
            if (data.redirect) setTimeout(() => window.location.href = data.redirect, 1500);
        }
    })
    .catch(() => showToast('Something went wrong', 'error'));
}

/* ── Toggle Wishlist (AJAX) ─────────────────────────────────── */
function toggleWishlist(productId, btn) {
    const base = getBase();
    fetch(`${base}user/wishlist.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle&product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            if (btn) {
                btn.classList.toggle('wishlisted', data.in_wishlist);
                btn.querySelector('i').className = data.in_wishlist ? 'fas fa-heart' : 'far fa-heart';
            }
        } else {
            showToast(data.message || 'Please login first', 'error');
            if (data.redirect) setTimeout(() => window.location.href = data.redirect, 1500);
        }
    })
    .catch(() => showToast('Something went wrong', 'error'));
}

/* ── Update Cart Quantity ───────────────────────────────────── */
function updateCartQty(productId, change) {
    const base = getBase();
    fetch(`${base}cart.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update&product_id=${productId}&change=${change}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            if (data.removed) {
                const row = document.getElementById(`cart-row-${productId}`);
                if (row) row.remove();
                if (data.cart_count === 0) location.reload();
            } else {
                const qtyEl = document.getElementById(`qty-${productId}`);
                if (qtyEl) qtyEl.textContent = data.new_qty;
                const rowTotal = document.getElementById(`row-total-${productId}`);
                if (rowTotal) rowTotal.textContent = data.row_total;
            }
            // Update summary
            const sumTotal = document.getElementById('summary-total');
            if (sumTotal) sumTotal.textContent = data.cart_total;
            const sumSaving = document.getElementById('summary-saving');
            if (sumSaving) sumSaving.textContent = data.total_saving;
        }
    });
}

/* ── Remove from Cart ───────────────────────────────────────── */
function removeFromCart(productId) {
    const base = getBase();
    fetch(`${base}cart.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`cart-row-${productId}`);
            if (row) { row.style.opacity = '0'; row.style.transform = 'translateX(-20px)'; row.style.transition = '0.3s'; setTimeout(() => { row.remove(); if (data.cart_count === 0) location.reload(); }, 300); }
            updateCartBadge(data.cart_count);
            showToast('Removed from cart', 'info');
        }
    });
}

/* ── Update cart badge in navbar ────────────────────────────── */
function updateCartBadge(count) {
    let badge = document.querySelector('.cart-badge');
    if (count > 0) {
        if (!badge) {
            const iconLink = document.querySelector('.nav-icon-link');
            if (iconLink) { badge = document.createElement('span'); badge.className = 'cart-badge'; iconLink.appendChild(badge); }
        }
        if (badge) badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

/* ── Product image gallery ──────────────────────────────────── */
function switchImage(src, thumb) {
    const mainImg = document.getElementById('mainProductImg');
    if (mainImg) mainImg.src = src;
    document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
    if (thumb) thumb.classList.add('active');
}

/* ── Price range filter ─────────────────────────────────────── */
function initPriceRange() {
    const range = document.getElementById('priceRange');
    const display = document.getElementById('priceDisplay');
    if (range && display) {
        display.textContent = '₹' + Number(range.value).toLocaleString('en-IN');
        range.addEventListener('input', function () {
            display.textContent = '₹' + Number(this.value).toLocaleString('en-IN');
        });
    }
}

/* ── Determine base path ────────────────────────────────────── */
function getBase() {
    const p = window.location.pathname;
    return (p.includes('/admin/') || p.includes('/user/')) ? '../' : '';
}

/* ── DOM ready ──────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    initPriceRange();

    // Active nav link
    const links = document.querySelectorAll('.sidebar-nav a');
    links.forEach(link => {
        if (link.href === window.location.href) link.classList.add('active');
    });

    // Auto-dismiss PHP flash messages
    const alerts = document.querySelectorAll('.es-flash');
    alerts.forEach(a => { setTimeout(() => { a.style.opacity = '0'; setTimeout(() => a.remove(), 400); }, 4000); });

    // Image preview for file inputs
    document.querySelectorAll('input[type=file][data-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            if (preview && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});
