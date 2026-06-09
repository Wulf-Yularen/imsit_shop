// ══════════════════════════════════════════
// ИМСИТ Shop — Main JavaScript
// ══════════════════════════════════════════

// ── Toast Notifications ──────────────────
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.style.background = type === 'error' ? '#c8372d' : '#0f0e17';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

// ── Add to Cart ──────────────────────────
function addToCart(productId, btn) {
  fetch('api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'add', product_id: productId, qty: 1 })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('✓ Добавлено в корзину');
      // Update cart badge
      const badges = document.querySelectorAll('.cart-badge');
      badges.forEach(b => { b.textContent = data.cart_count; b.style.display = 'flex'; });
      if (!badges.length) {
        const cartBtn = document.querySelector('.cart-btn');
        if (cartBtn) {
          const badge = document.createElement('span');
          badge.className = 'cart-badge';
          badge.textContent = data.cart_count;
          cartBtn.appendChild(badge);
        }
      }
      // Button feedback
      if (btn) {
        btn.classList.add('added');
        btn.innerHTML = '✓ Добавлено';
        setTimeout(() => {
          btn.classList.remove('added');
          btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> В корзину';
        }, 1800);
      }
    } else {
      showToast(data.message || 'Ошибка', 'error');
    }
  })
  .catch(() => showToast('Ошибка сети', 'error'));
}

// ── Cart Quantity Update ──────────────────
function updateQty(cartId, delta) {
  const numEl = document.getElementById('qty-' + cartId);
  if (!numEl) return;
  const newQty = Math.max(0, parseInt(numEl.textContent) + delta);
  fetch('api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'update', cart_id: cartId, qty: newQty })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (newQty === 0) {
        const row = document.getElementById('cart-item-' + cartId);
        if (row) row.remove();
      } else {
        numEl.textContent = newQty;
      }
      updateCartSummary(data.total, data.cart_count);
    }
  });
}

function removeCartItem(cartId) {
  fetch('api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'remove', cart_id: cartId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const row = document.getElementById('cart-item-' + cartId);
      if (row) { row.style.opacity = '0'; setTimeout(() => row.remove(), 300); }
      updateCartSummary(data.total, data.cart_count);
    }
  });
}

function updateCartSummary(total, count) {
  const totalEl = document.getElementById('cart-total');
  const countEl = document.getElementById('cart-count');
  const badges  = document.querySelectorAll('.cart-badge');
  if (totalEl) totalEl.textContent = total;
  if (countEl) countEl.textContent = count + ' товар(ов)';
  badges.forEach(b => { b.textContent = count; if(count == 0) b.style.display = 'none'; });
}

// ── Catalog Filters ───────────────────────
function filterByCategory(slug) {
  const params = new URLSearchParams(window.location.search);
  if (slug) params.set('category', slug);
  else params.delete('category');
  params.delete('page');
  window.location.search = params.toString();
}

// ── Smooth reveal on scroll ───────────────
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.style.opacity = '1';
      e.target.style.transform = 'translateY(0)';
    }
  });
}, { threshold: 0.1 });

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.product-card, .cat-card, .stat-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity .5s ease, transform .5s ease';
    observer.observe(el);
  });
});

// ── Admin: Confirm delete ─────────────────
function confirmDelete(url, msg) {
  if (confirm(msg || 'Вы уверены?')) window.location.href = url;
}

// ── Order status change ───────────────────
function changeOrderStatus(orderId, status) {
  fetch('admin/api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'update_status', order_id: orderId, status })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) showToast('Статус обновлён');
    else showToast('Ошибка обновления', 'error');
  });
}
