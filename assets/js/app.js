// Report export helpers
function exportReportCSV(tableId, title) {
    var table = document.getElementById(tableId);
    if (!table) { alert('No table found to export.'); return; }
    var rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
    var csv = rows.map(function(row) {
        return Array.from(row.querySelectorAll('th, td')).map(function(cell) {
            return '"' + cell.innerText.replace(/"/g, '""').replace(/\r?\n/g, ' ').trim() + '"';
        }).join(',');
    });
    var blob = new Blob(['\uFEFF' + csv.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = title.replace(/[^a-z0-9]/gi, '_') + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
function exportReportPDF() { window.print(); }

// Mobile sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar')?.classList.toggle('open');
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (!e.altKey) return;
    const key = e.key.toLowerCase();
    if (key === 's') { e.preventDefault(); window.location.href = '?page=sales&action=create'; }
    if (key === 'p') { e.preventDefault(); window.location.href = '?page=purchases&action=create'; }
    if (key === 'a') { e.preventDefault(); window.location.href = '?page=accounts'; }
    if (key === 'e') { e.preventDefault(); window.location.href = '?page=expenses&new=1'; }
    if (key === 'i') { e.preventDefault(); window.location.href = '?page=payments&action=create&type=in'; }
    if (key === 'o') { e.preventDefault(); window.location.href = '?page=payments&action=create&type=out'; }
});

// Theme toggle
const htmlRoot = document.getElementById('htmlRoot');
const themeBtn = document.getElementById('themeToggleBtn');
const themeIcon = document.getElementById('themeIcon');

function applyTheme(theme) {
    htmlRoot?.setAttribute('data-theme', theme);
    localStorage.setItem('invt_theme', theme);
    if (themeIcon && themeBtn) {
        if (theme === 'light') {
            themeIcon.className = 'bi bi-sun-fill';
            themeBtn.title = 'Switch to dark mode';
        } else {
            themeIcon.className = 'bi bi-moon-stars-fill';
            themeBtn.title = 'Switch to light mode';
        }
    }
}

applyTheme(localStorage.getItem('invt_theme') || 'dark');
themeBtn?.addEventListener('click', () => {
    const current = htmlRoot?.getAttribute('data-theme') || 'dark';
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
let activeSearchController = null;
const searchInput = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');

searchInput?.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();

    if (q.length < 2) {
        if (activeSearchController) {
            activeSearchController.abort();
            activeSearchController = null;
        }
        if (searchResults) searchResults.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        if (activeSearchController) activeSearchController.abort();
        activeSearchController = new AbortController();

        fetch(`?page=dashboard&action=search&q=${encodeURIComponent(q)}`, { signal: activeSearchController.signal })
            .then(r => r.json())
            .then(data => {
                if (!searchResults) return;
                if (searchInput && searchInput.value.trim() !== q) return;

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
                activeSearchController = null;
            })
            .catch(err => {
                if (err && err.name === 'AbortError') return;
                activeSearchController = null;
            });
    }, 300);
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-box') && searchResults) {
        searchResults.style.display = 'none';
    }
});

// Initialize DataTables with default styling
function initDataTable(selector, options = {}) {
    return $(selector).DataTable({
        pageLength: 25,
        responsive: true,
        language: { search: '', searchPlaceholder: 'Filter...' },
        ...options
    });
}

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
