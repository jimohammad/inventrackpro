// Keyboard shortcuts
// Mobile sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('open');
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (!e.altKey) return;
    const key = e.key.toLowerCase();
    if (key === 's') { e.preventDefault(); window.location.href = '?page=sales&action=create'; }
    if (key === 'p') { e.preventDefault(); window.location.href = '?page=purchases&action=create'; }
    if (key === 'e') { e.preventDefault(); window.location.href = '?page=expenses&new=1'; }
    if (key === 'i') { e.preventDefault(); window.location.href = '?page=payments&action=create&type=in'; }
    if (key === 'o') { e.preventDefault(); window.location.href = '?page=payments&action=create&type=out'; }
});

// ── Theme Toggle ──
const htmlRoot   = document.getElementById('htmlRoot');
const themeBtn   = document.getElementById('themeToggleBtn');
const themeIcon  = document.getElementById('themeIcon');

function applyTheme(theme) {
    htmlRoot.setAttribute('data-theme', theme);
    localStorage.setItem('invt_theme', theme);
    if (theme === 'light') {
        themeIcon.className = 'bi bi-sun-fill';
        themeBtn.title = 'Switch to dark mode';
    } else {
        themeIcon.className = 'bi bi-moon-stars-fill';
        themeBtn.title = 'Switch to light mode';
    }
}

// Set correct icon on load
applyTheme(localStorage.getItem('invt_theme') || 'dark');

themeBtn?.addEventListener('click', () => {
    const current = htmlRoot.getAttribute('data-theme') || 'dark';
    applyTheme(current === 'dark' ? 'light' : 'dark');
});

// Auto-dismiss flash messages
setTimeout(() => {
    document.querySelectorAll('.flash-msg .alert').forEach(el => {
        new bootstrap.Alert(el).close();
    });
}, 4000);

// Global Search (AJAX)
let searchTimeout;
const searchInput  = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');

searchInput?.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();

    if (q.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`?page=dashboard&action=search&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.results || data.results.length === 0) {
                    searchResults.innerHTML = '<div class="search-result-item text-muted">No results found</div>';
                } else {
                    searchResults.innerHTML = data.results.map(r => `
                        <a href="${r.url}" class="search-result-item text-decoration-none d-flex align-items-center gap-2">
                            <span class="search-result-type">${r.type}</span>
                            <span style="color:var(--text-main)">${r.label}</span>
                            <small style="color:var(--text-muted);margin-left:auto">${r.sub ?? ''}</small>
                        </a>
                    `).join('');
                }
                searchResults.style.display = 'block';
            });
    }, 300);
});

// Close search when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-box')) {
        searchResults.style.display = 'none';
    }
});

// Initialize DataTables with dark styling
function initDataTable(selector, options = {}) {
    return $(selector).DataTable({
        pageLength: 25,
        responsive: true,
        language: { search: '', searchPlaceholder: 'Filter...' },
        ...options
    });
}
var _pinCallback = null;
var _pinVerified = false;

function requirePin(callback) {
    if (_pinVerified) { callback(); return; }
    _pinCallback = callback;
    document.getElementById('pinModal').style.display = 'flex';
    document.getElementById('pinError').textContent = '';
    ['pin1','pin2','pin3','pin4'].forEach(id => { document.getElementById(id).value = ''; document.getElementById(id).classList.remove('err'); });
    setTimeout(() => document.getElementById('pin1').focus(), 100);
}

function pinNext(n) {
    if (n < 4 && document.getElementById('pin'+n).value) document.getElementById('pin'+(n+1)).focus();
    if (n === 4 && document.getElementById('pin4').value) submitPin();
}

function pinBack(e, n) {
    if (e.key === 'Backspace' && !document.getElementById('pin'+n).value && n > 1) document.getElementById('pin'+(n-1)).focus();
    if (e.key === 'Escape') closePin();
}

function closePin() {
    document.getElementById('pinModal').style.display = 'none';
    _pinCallback = null;
}

function submitPin() {
    var pin = document.getElementById('pin1').value + document.getElementById('pin2').value +
              document.getElementById('pin3').value + document.getElementById('pin4').value;
    if (pin.length < 4) { document.getElementById('pinError').textContent = 'Enter all 4 digits'; return; }

    document.getElementById('pinSubmitBtn').textContent = '...';
    fetch('?page=settings&action=verifyPin', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'pin=' + encodeURIComponent(pin)
    })
        .then(r => r.json())
        .then(data => {
            document.getElementById('pinSubmitBtn').textContent = 'Verify';
            if (data.valid) {
                _pinVerified = true;
                document.getElementById('pinModal').style.display = 'none';
                if (_pinCallback) _pinCallback();
            } else {
                document.getElementById('pinError').textContent = 'Wrong PIN';
                ['pin1','pin2','pin3','pin4'].forEach(id => {
                    document.getElementById(id).classList.add('err');
                    document.getElementById(id).value = '';
                });
                setTimeout(() => document.getElementById('pin1').focus(), 200);
            }
        })
        .catch(() => {
            document.getElementById('pinSubmitBtn').textContent = 'Verify';
            document.getElementById('pinError').textContent = 'Error — try again';
        });
}

// Close on overlay click / Escape
document.getElementById('pinModal').addEventListener('click', function(e) { if (e.target === this) closePin(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && document.getElementById('pinModal').style.display === 'flex') closePin(); });

// Universal PIN protection — intercepts all .pin-protect links and buttons
document.addEventListener('click', function(e) {
    var el = e.target.closest('.pin-protect');
    if (!el) return;
    if (_pinVerified) return;

    e.preventDefault();
    e.stopPropagation();

    if (el.tagName === 'A') {
        requirePin(function() { window.location = el.href; });
        return;
    }

    if (el.tagName === 'BUTTON' && el.type === 'submit') {
        var form = el.closest('form');
        if (form) {
            var isCancel = form.action && form.action.indexOf('cancel') !== -1;
            requirePin(function() {
                if (isCancel && !confirm('Are you sure you want to cancel this?')) return;
                form.submit();
            });
        }
        return;
    }
}, true);

// Rounded top corners on all card tables
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.card table').forEach(function(table) {
        table.style.borderCollapse = 'separate';
        table.style.borderSpacing = '0';
        var thead = table.querySelector('thead');
        if (!thead) return;
        var firstRow = thead.querySelector('tr');
        if (!firstRow) return;
        var cells = firstRow.querySelectorAll('th, td');
        if (cells.length > 0) {
            cells[0].style.borderTopLeftRadius = '12px';
            cells[cells.length - 1].style.borderTopRightRadius = '12px';
        }
    });
});
