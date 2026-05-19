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

function escHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

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
                        <a href="${escHtml(r.url)}" class="search-result-item text-decoration-none d-flex align-items-center gap-2">
                            <span class="search-result-type">${escHtml(r.type)}</span>
                            <span style="color:var(--text-main)">${escHtml(r.label)}</span>
                            <small style="color:var(--text-muted);margin-left:auto">${escHtml(r.sub ?? '')}</small>
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

// Initialize DataTables — deferRender speeds first paint on large tables
function initDataTable(selector, options = {}) {
    return $(selector).DataTable({
        pageLength: 25,
        responsive: true,
        deferRender: true,
        autoWidth: false,
        language: { search: '', searchPlaceholder: 'Filter...' },
        ...options
    });
}

/** Statement / ledger reports: paginated table (never paging:false on large ledgers). */
function initReportLedgerDataTable(tableId) {
    if (typeof $ === 'undefined' || !$.fn.DataTable) return;
    const selector = '#' + tableId;
    const $t = $(selector);
    if (!$t.length || $t.find('tbody tr').length === 0) return;
    if ($.fn.DataTable.isDataTable($t)) return;
    initDataTable(selector, {
        pageLength: 50,
        lengthMenu: [[25, 50, 100, 250], [25, 50, 100, 250]],
        paging: true,
        order: [],
        language: { search: '', searchPlaceholder: 'Search in statement...' }
    });
}

document.addEventListener('click', function (e) {
    const csvBtn = e.target.closest('.js-export-report-csv');
    if (csvBtn) {
        exportReportCSV(csvBtn.dataset.tableId || '', csvBtn.dataset.title || 'Report');
        return;
    }
    if (e.target.closest('.js-export-report-pdf')) {
        exportReportPDF();
    }
});
