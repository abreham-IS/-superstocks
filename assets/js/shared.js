// ═══════════════════════════════════════════════════════════════
//  shared.js — SuperStock Shared Helpers (PHP/MySQL Backend Version)
//
//  This file is included by every HTML page.
//  It provides:
//    - API communication helpers (fetch wrappers)
//    - Authentication functions (login, logout, auth check)
//    - Header and sidebar injection
//    - Footer injection
//    - Toast notifications
//    - Product and settings API calls
//    - Date/status utility functions
// ═══════════════════════════════════════════════════════════════


// ── SECTION 1: Central API Fetch Helper ────────────────────────
//
// apiFetch() is the single function all other API calls use.
// It wraps the browser's fetch() and handles errors in one place.
//
// If the server returns an error (non-2xx status), it reads the
// JSON error message and shows it as a toast notification.
// It then throws an error so the calling function knows it failed.

async function apiFetch(url, options = {}) {
  // Prevent caching, especially for GET requests like auth_check.php
  if (!options.cache) {
    options.cache = 'no-store';
  }

  // Make the HTTP request
  const response = await fetch(url, options);

  // Parse the JSON body (all our PHP endpoints return JSON)
  const data = await response.json();

  // Check if the request was successful (status 200–299)
  if (!response.ok) {
    // Show the error message from the server in a red toast
    const errorMessage = data.error || 'An unknown error occurred.';
    showToast('❌ ' + errorMessage, true);
    // Throw an error so the calling code can stop execution
    throw new Error(errorMessage);
  }

  // Return the parsed data on success
  return data;
}


// ── SECTION 2: Authentication Functions ────────────────────────

/**
 * Checks if the user has an active session on the server.
 * If not, redirects to the login page.
 * Call this at the top of every protected page.
 *
 * Returns the current user object {id, username, role} on success.
 */
async function requireAuth() {
  try {
    const user = await apiFetch('api/auth_check.php');
    window._currentUser = user;
    return user;
  } catch (err) {
    window.location.href = 'index.html';
    return new Promise(() => {});
  }
}

/**
 * Handles the login form submission.
 * Sends username and password to login.php.
 * On success, redirects to home.html.
 */
async function doLogin(e) {
  e.preventDefault();

  const username = document.getElementById('login-user').value.trim();
  const password = document.getElementById('login-pass').value;

  // Build a FormData object to send as POST body
  const formData = new FormData();
  formData.append('username', username);
  formData.append('password', password);

  try {
    await apiFetch('api/login.php', { method: 'POST', body: formData });
    window.location.href = 'home.html';
  } catch (err) {
    const errorEl = document.getElementById('login-error');
    if (errorEl) {
      errorEl.textContent = '❌ ' + (err.message || 'Invalid username or password.');
      errorEl.classList.remove('hidden');
    }
  }
}

/**
 * Logs the user out by calling logout.php, then redirects to login.
 */
async function doLogout() {
  if (!confirm('Log out?')) return;

  try {
    await apiFetch('api/logout.php', { method: 'POST' });
  } catch (err) {
    // Even if logout fails on the server, redirect anyway
  }

  window.location.href = 'index.html';
}

/**
 * Returns the current user from memory (set by requireAuth).
 * Used by injectHeader() to display the username.
 */
function getUser() {
  return window._currentUser || null;
}


// ── SECTION 3: Header and Sidebar Injection ────────────────────

/**
 * Builds and inserts the top navigation header and sidebar.
 * Call this on every page after requireAuth() completes.
 *
 * @param {string} activePage - The filename of the current page (e.g. 'home.html')
 */
function injectHeader(activePage) {
  const user = getUser();

  // Navigation links for the sidebar
  const nav = [
    { href: 'home.html',         icon: '🏠', label: 'Home' },
    { href: 'dashboard.html',    icon: '📊', label: 'Dashboard' },
    { href: 'products.html',     icon: '📦', label: 'Products' },
    { href: 'add.html',          icon: '➕', label: 'Add Product' },
    { href: 'alerts.html',       icon: '🔔', label: 'Alerts', badge: true },
    { href: 'reports.html',      icon: '📈', label: 'Reports' },
    { href: 'importexport.html', icon: '📂', label: 'Import / Export' },
    { href: 'settings.html',     icon: '⚙️', label: 'Settings' },
    { href: 'about.html',        icon: 'ℹ️', label: 'About' },
  ];

  const links = nav.map(n => `
    <a class="slink ${n.href === activePage ? 'active' : ''}" href="${n.href}">
      ${n.icon} ${n.label}
      ${n.badge ? `<span class="badge" id="alert-badge">0</span>` : ''}
    </a>`).join('');

  document.getElementById('header-placeholder').innerHTML = `
    <header>
      <div class="header-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <a class="logo" href="home.html" style="text-decoration:none">🛒 SuperStock</a>
      </div>
      <div class="header-right">
        <span class="header-user">👤 ${user ? user.role + ': ' + user.username : 'Guest'}</span>
        <button class="nav-btn logout-btn" onclick="doLogout()">🚪 Logout</button>
      </div>
    </header>`;

  document.getElementById('sidebar-placeholder').innerHTML = `
    <aside id="sidebar" class="sidebar">
      <nav class="sidebar-nav">${links}</nav>
    </aside>`;
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('collapsed');
}


// ── SECTION 4: Footer Injection ────────────────────────────────

function injectFooter() {
  const el = document.getElementById('footer-placeholder');
  if (!el) return;
  el.innerHTML = `
    <footer>
      <div class="footer-inner">
        <div class="footer-col">
          <div class="footer-logo">🛒 SuperStock</div>
          <p>Smart inventory management for modern supermarkets. Track products, monitor expiry dates and keep your store running smoothly.</p>
        </div>
        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="home.html">🏠 Home</a></li>
            <li><a href="dashboard.html">📊 Dashboard</a></li>
            <li><a href="products.html">📦 Products</a></li>
            <li><a href="reports.html">📈 Reports</a></li>
            <li><a href="about.html">ℹ️ About</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Contact Us</h4>
          <ul class="contact-list">
            <li>📧 <a href="mailto:group9project@gmail.com">group9project@gmail.com</a></li>
            <li>📞 <a href="tel:+251941168367">+251 941 168 367</a></li>
            <li>📍 Hawassa University, Hawassa, Ethiopia</li>
            <li>🕐 Mon–Fri: 8:00 AM – 6:00 PM</li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Follow Us</h4>
          <div class="social-links">
            <a href="#" class="social-btn">🐦 Twitter</a>
            <a href="#" class="social-btn">📘 Facebook</a>
            <a href="#" class="social-btn">📸 Instagram</a>
            <a href="#" class="social-btn">💼 LinkedIn</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2026 SuperStock Inventory Management. All rights reserved.</p>
        <div class="footer-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Service</a>
          <a href="#">Help Center</a>
        </div>
      </div>
    </footer>`;
}


// ── SECTION 5: Toast Notification ──────────────────────────────

/**
 * Shows a small notification message at the bottom-right of the screen.
 *
 * @param {string}  msg     - The message to display.
 * @param {boolean} isError - If true, shows a red border (error style).
 */
function showToast(msg, isError = false) {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    document.body.appendChild(toast);
  }
  toast.className = 'toast';
  toast.textContent = msg;
  toast.style.borderLeft = `3px solid ${isError ? '#f87171' : '#38bdf8'}`;
  clearTimeout(toast._t);
  // Hide the toast after 3 seconds
  toast._t = setTimeout(() => toast.classList.add('hidden'), 3000);
}


// ── SECTION 6: Products API Functions ──────────────────────────
//
// These functions replace the old getProducts() / setProducts()
// that used localStorage. Now they talk to the PHP backend.

/**
 * Fetches all products from the server.
 * @returns {Promise<Array>} Array of product objects.
 */
async function getProducts() {
  return await apiFetch('api/get_products.php');
}

async function addProduct(data) {
  const formData = new FormData();
  for (const [key, value] of Object.entries(data)) {
    formData.append(key, value);
  }
  return await apiFetch('api/add_product.php', { method: 'POST', body: formData });
}

async function updateProduct(data) {
  const formData = new FormData();
  for (const [key, value] of Object.entries(data)) {
    formData.append(key, value);
  }
  return await apiFetch('api/update_product.php', { method: 'POST', body: formData });
}

async function deleteProduct(id) {
  const formData = new FormData();
  formData.append('id', id);
  return await apiFetch('api/delete_product.php', { method: 'POST', body: formData });
}

async function bulkDeleteProducts(ids) {
  const formData = new FormData();
  formData.append('ids', JSON.stringify(ids));
  return await apiFetch('api/bulk_delete_products.php', { method: 'POST', body: formData });
}

async function bulkDisposeProducts(ids) {
  const formData = new FormData();
  formData.append('ids', JSON.stringify(ids));
  return await apiFetch('api/bulk_dispose_products.php', { method: 'POST', body: formData });
}

async function getCategories() {
  return await apiFetch('api/get_categories.php');
}

async function addCategory(name) {
  const formData = new FormData();
  formData.append('name', name);
  return await apiFetch('api/add_category.php', { method: 'POST', body: formData });
}

async function deleteCategory(id) {
  const formData = new FormData();
  formData.append('id', id);
  return await apiFetch('api/delete_category.php', { method: 'POST', body: formData });
}

async function getUsers() {
  return await apiFetch('api/get_users.php');
}

async function addUser(data) {
  const formData = new FormData();
  for (const [key, value] of Object.entries(data)) {
    formData.append(key, value);
  }
  return await apiFetch('api/add_user.php', { method: 'POST', body: formData });
}

async function deleteUser(id) {
  const formData = new FormData();
  formData.append('id', id);
  return await apiFetch('api/delete_user.php', { method: 'POST', body: formData });
}

async function testNotify() {
  return await apiFetch('api/test_notify.php', { method: 'POST' });
}


// ── SECTION 7: Settings API Functions ──────────────────────────

// Cache the settings in memory so we don't fetch them on every call
// to getStatus() and daysLabel() (which are called many times per page).
let _settingsCache = null;

/**
 * Fetches application settings from the server.
 * Uses an in-memory cache to avoid repeated requests on the same page.
 * @returns {Promise<Object>} The settings object.
 */
async function getSettings() {
  if (_settingsCache !== null) {
    return _settingsCache;
  }
  _settingsCache = await apiFetch('api/get_settings.php');
  return _settingsCache;
}

async function saveSettings(data) {
  const formData = new FormData();
  for (const [key, value] of Object.entries(data)) {
    formData.append(key, value);
  }
  _settingsCache = null;
  return await apiFetch('api/update_settings.php', { method: 'POST', body: formData });
}


// ── SECTION 8: Date and Status Utility Functions ────────────────
//
// These functions are used by multiple pages to calculate
// expiry status and format dates for display.
// They now read the threshold from the settings cache.

/**
 * Calculates how many days until (or since) a product expires.
 * Returns a negative number if the product has already expired.
 *
 * @param {string} expiryDate - Date string in YYYY-MM-DD format.
 * @returns {number} Days until expiry (negative = already expired).
 */
function daysUntilExpiry(expiryDate) {
  const now = new Date();
  now.setHours(0, 0, 0, 0); // compare dates only, not times
  const exp = new Date(expiryDate);
  exp.setHours(0, 0, 0, 0);
  return Math.round((exp - now) / 86400000); // 86400000 ms = 1 day
}

/**
 * Returns the expiry status of a product: 'expired', 'expiring', or 'good'.
 * Uses the threshold from the settings cache (falls back to 7 days).
 *
 * @param {string} expiryDate - Date string in YYYY-MM-DD format.
 * @returns {string} 'expired' | 'expiring' | 'good'
 */
function getStatus(expiryDate) {
  // Use cached settings threshold, or fall back to 7 days
  const threshold = _settingsCache ? _settingsCache.expiry_threshold_days : 7;
  const days = daysUntilExpiry(expiryDate);
  if (days < 0)            return 'expired';
  if (days <= threshold)   return 'expiring';
  return 'good';
}

/**
 * Returns an HTML badge element for a product's expiry status.
 * @param {string} status - 'expired' | 'expiring' | 'good'
 * @returns {string} HTML string for the badge.
 */
function statusBadge(status) {
  const labels = {
    good:     '✅ Good',
    expiring: '⚠️ Expiring Soon',
    expired:  '🚫 Expired',
  };
  return `<span class="status-badge status-${status}">${labels[status]}</span>`;
}

/**
 * Returns a coloured HTML span showing days remaining.
 * @param {number} days - Days until expiry (negative = expired).
 * @returns {string} HTML string.
 */
function daysLabel(days) {
  const threshold = _settingsCache ? _settingsCache.expiry_threshold_days : 7;
  if (days < 0)           return `<span class="days-critical">${Math.abs(days)}d ago</span>`;
  if (days <= threshold)  return `<span class="days-warning">${days}d left</span>`;
  return `<span class="days-ok">${days}d left</span>`;
}

/**
 * Formats a YYYY-MM-DD date string as DD/MM/YYYY for display.
 * @param {string} dateStr - Date in YYYY-MM-DD format.
 * @returns {string} Date in DD/MM/YYYY format.
 */
function formatDate(dateStr) {
  if (!dateStr) return '—';
  const [y, m, d] = dateStr.split('-');
  return `${d}/${m}/${y}`;
}


// ── SECTION 9: Low Stock Helpers ───────────────────────────────

/**
 * Returns true if a product's quantity is below the low-stock threshold.
 * Uses the settings cache (falls back to 10).
 *
 * @param {Object} product - A product object with a 'quantity' field.
 * @returns {boolean}
 */
function isLowStock(product) {
  const threshold = _settingsCache ? _settingsCache.low_stock_threshold : 10;
  // Support both 'quantity' (from PHP API) and 'qty' (legacy field name)
  const qty = product.quantity !== undefined ? product.quantity : product.qty;
  return qty < threshold;
}

/**
 * Returns an HTML badge for low-stock products.
 * @returns {string} HTML string.
 */
function lowStockBadge() {
  return `<span class="status-badge status-lowstock">📉 Low Stock</span>`;
}


// ── SECTION 10: Alert Badge ─────────────────────────────────────

/**
 * Updates the red number badge on the "Alerts" sidebar link.
 * Counts products that are expired/expiring or low on stock.
 * Must be called after getProducts() and getSettings() have resolved.
 *
 * @param {Array} products - Array of product objects from the API.
 */
function updateAlertBadge(products) {
  const badge = document.getElementById('alert-badge');
  if (!badge) return;

  // Guard: if products is not a valid array, just show 0
  if (!Array.isArray(products)) return;

  const expiryIssues   = products.filter(p => getStatus(p.expiry_date) !== 'good').length;
  const lowStockIssues = products.filter(p => isLowStock(p)).length;

  badge.textContent = expiryIssues + lowStockIssues;
}
