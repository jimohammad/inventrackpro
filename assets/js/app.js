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

// Mobile nav toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    const nav = document.querySelector('.sidebar');
    const btn = document.getElementById('sidebarToggle');
    const open = !nav?.classList.contains('open');
    nav?.classList.toggle('open', open);
    document.body.classList.toggle('nav-open', open);
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
});

// Nav dropdowns: click to open one group; click outside / Escape to close
(function () {
    var nav = document.querySelector('.sidebar-nav');
    if (!nav) return;

    function closeAll(except) {
        nav.querySelectorAll('.sidebar-group.is-open').forEach(function (g) {
            if (except && g === except) return;
            g.classList.remove('is-open');
            var b = g.querySelector('.sidebar-group-toggle');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    }

    nav.addEventListener('click', function (e) {
        var btn = e.target.closest('.sidebar-group-toggle');
        if (!btn || !nav.contains(btn)) return;
        var group = btn.closest('.sidebar-group');
        if (!group) return;
        var open = !group.classList.contains('is-open');
        closeAll(group);
        group.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.sidebar-group') || e.target.closest('#sidebarToggle')) return;
        closeAll();
        var bar = document.querySelector('.sidebar');
        if (bar && bar.classList.contains('open') && !e.target.closest('.sidebar') && !e.target.closest('.app-topbar')) {
            bar.classList.remove('open');
            document.body.classList.remove('nav-open');
            var t = document.getElementById('sidebarToggle');
            if (t) t.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeAll();
        var bar = document.querySelector('.sidebar');
        if (bar) bar.classList.remove('open');
        document.body.classList.remove('nav-open');
        var t = document.getElementById('sidebarToggle');
        if (t) t.setAttribute('aria-expanded', 'false');
    });

    nav.querySelectorAll('.sidebar-group').forEach(function (group) {
        group.addEventListener('mouseenter', function () {
            if (window.matchMedia('(min-width: 769px)').matches) closeAll(group);
        });
    });
})();

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
    if (key === 't') {
        e.preventDefault();
        // Already on Accounts with transfer UI available — open modal without reload
        if (typeof window.openTransferModal === 'function') {
            window.openTransferModal();
            return;
        }
        window.location.href = '?page=accounts&transfer=1';
    }
});

// Auto-dismiss flash messages
setTimeout(() => {
    document.querySelectorAll('.flash-msg .alert').forEach(el => {
        new bootstrap.Alert(el).close();
    });
}, 4000);

// Initialize DataTables — deferRender speeds first paint on large tables
function initDataTable(selector, options = {}) {
    const dt = $(selector).DataTable({
        pageLength: 25,
        responsive: true,
        deferRender: true,
        autoWidth: false,
        language: { search: '', searchPlaceholder: 'Filter...' },
        ...options
    });
    // DataTables rebinds window.resize and retunes columns; that plus a body
    // scrollbar toggle is a known whole-page shake loop.
    try {
        const el = document.querySelector(selector);
        if (el && el.id) $(window).off('resize.DT-' + el.id);
    } catch (e) {}
    return dt;
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

// ── Unsaved changes guard (edit / create forms) ──
(function () {
    const WARN_MSG = 'You have unsaved changes. Please save the page before leaving.';
    let isDirty = false;
    let allowLeave = false;

    function isGuardForm(form) {
        if (!form || form.tagName !== 'FORM') return false;
        if (form.hasAttribute('data-unsaved-ignore')) return false;
        if (form.classList.contains('js-unsaved-ignore')) return false;

        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') return false;

        // Skip tiny action forms (delete / status toggles / one-click posts)
        const controls = form.querySelectorAll(
            'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]), select, textarea'
        );
        if (controls.length === 0 && !form.hasAttribute('data-unsaved-guard')) return false;

        // Instant file-upload forms (PO docs, etc.): only a file input + hiddens
        const nonFile = form.querySelectorAll(
            'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="file"]), select, textarea'
        );
        const files = form.querySelectorAll('input[type="file"]');
        if (nonFile.length === 0 && files.length > 0 && !form.hasAttribute('data-unsaved-guard')) {
            return false;
        }

        return true;
    }

    function markDirty() {
        isDirty = true;
    }

    function clearDirty() {
        isDirty = false;
    }

    function confirmLeave() {
        if (!isDirty || allowLeave) return true;
        return window.confirm(WARN_MSG);
    }

    document.addEventListener('input', function (e) {
        const form = e.target.closest && e.target.closest('form');
        if (form && isGuardForm(form)) markDirty();
    }, true);

    document.addEventListener('change', function (e) {
        const form = e.target.closest && e.target.closest('form');
        if (form && isGuardForm(form)) markDirty();
    }, true);

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && isGuardForm(form)) {
            allowLeave = true;
            clearDirty();
        }
    }, true);

    window.addEventListener('beforeunload', function (e) {
        if (!isDirty || allowLeave) return;
        e.preventDefault();
        e.returnValue = WARN_MSG;
        return WARN_MSG;
    });

    document.addEventListener('click', function (e) {
        if (!isDirty || allowLeave) return;

        const link = e.target.closest && e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.hasAttribute('download')) return;
        if (link.target === '_blank') return;
        if (link.closest('form') && isGuardForm(link.closest('form'))) return;

        if (!confirmLeave()) {
            e.preventDefault();
            e.stopPropagation();
        } else {
            allowLeave = true;
            clearDirty();
        }
    }, true);

    // Alt+ shortcuts in this file navigate away — block when dirty
    document.addEventListener('keydown', function (e) {
        if (!isDirty || allowLeave || !e.altKey) return;
        const key = e.key.toLowerCase();
        if (!['s', 'p', 'a', 'e', 'i', 'o', 't'].includes(key)) return;
        if (!confirmLeave()) {
            e.preventDefault();
            e.stopImmediatePropagation();
        } else {
            allowLeave = true;
            clearDirty();
        }
    }, true);

    // Expose for pages that save via AJAX / custom leave
    window.UnsavedGuard = {
        markDirty: markDirty,
        clearDirty: clearDirty,
        isDirty: function () { return isDirty; }
    };
})();

// Desktop notifications for customer order requests (from /apps)
(function () {
    var badge = document.getElementById('orderReqBadge');
    var topBadge = document.getElementById('topOrderReqBadge');
    if (!badge && !topBadge) return;

    var STORAGE_KEY = 'iqbal_erp_order_req_latest';
    var lastKnownId = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10) || 0;
    var primed = false;

    function setBadges(count) {
        var show = count > 0;
        [badge, topBadge].forEach(function (el) {
            if (!el) return;
            el.textContent = String(count);
            el.style.display = show ? '' : 'none';
        });
    }

    function notifyNew(latest) {
        if (!latest || !('Notification' in window)) return;
        var title = 'New order request';
        var body = (latest.request_no || '') + ' — ' + (latest.customer_name || '') +
            (latest.customer_phone ? ' (' + latest.customer_phone + ')' : '');
        var opts = {
            body: body,
            icon: '/assets/pwa/apps/icons/icon-192.png',
            tag: 'erp-order-' + (latest.id || ''),
            requireInteraction: true
        };
        try {
            new Notification(title, opts);
        } catch (e) {}
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            var ctx = new Ctx();
            var o = ctx.createOscillator();
            var g = ctx.createGain();
            o.type = 'sine';
            o.frequency.value = 880;
            g.gain.value = 0.04;
            o.connect(g);
            g.connect(ctx.destination);
            o.start();
            setTimeout(function () { o.stop(); ctx.close(); }, 180);
        } catch (e2) {}
    }

    function poll() {
        fetch('?page=orderrequests&action=pendingJson', { cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) return;
                setBadges(data.count || 0);
                var latestId = parseInt(data.latest_id || 0, 10) || 0;
                if (!primed) {
                    primed = true;
                    if (latestId > lastKnownId) {
                        lastKnownId = latestId;
                        localStorage.setItem(STORAGE_KEY, String(lastKnownId));
                    }
                    return;
                }
                if (latestId > lastKnownId) {
                    lastKnownId = latestId;
                    localStorage.setItem(STORAGE_KEY, String(lastKnownId));
                    if (Notification.permission === 'granted') {
                        notifyNew(data.latest);
                    }
                }
            })
            .catch(function () {});
    }

    if ('Notification' in window && Notification.permission === 'default') {
        // Ask once when staff open ERP (needed for desktop toast)
        setTimeout(function () {
            try { Notification.requestPermission(); } catch (e) {}
        }, 2500);
    }

    poll();
    setInterval(poll, 15000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });
})();

// Desktop notifications for salesman invoice-edit requests (admin)
(function () {
    var badge = document.getElementById('saleEditBadge');
    var topBadge = document.getElementById('topSaleEditBadge');
    if (!badge && !topBadge) return;

    var STORAGE_KEY = 'iqbal_erp_sale_edit_req_latest';
    var lastKnownId = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10) || 0;
    var primed = false;

    function setBadges(count) {
        var show = count > 0;
        [badge, topBadge].forEach(function (el) {
            if (!el) return;
            el.textContent = String(count);
            el.style.display = show ? '' : 'none';
        });
    }

    function notifyNew(latest) {
        if (!latest || !('Notification' in window)) return;
        var title = 'Sale edit request';
        var body = (latest.invoice_no || '') + ' — ' + (latest.requested_by_name || '')
            + (latest.reason ? ': ' + String(latest.reason).slice(0, 80) : '');
        var opts = {
            body: body,
            icon: '/assets/pwa/apps/icons/icon-192.png',
            tag: 'erp-sale-edit-' + (latest.id || ''),
            requireInteraction: true
        };
        try {
            new Notification(title, opts);
        } catch (e) {}
    }

    function poll() {
        fetch('?page=saleedits&action=pendingJson', { cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) return;
                setBadges(data.count || 0);
                var latestId = parseInt(data.latest_id || 0, 10) || 0;
                if (!primed) {
                    primed = true;
                    if (latestId > lastKnownId) {
                        lastKnownId = latestId;
                        localStorage.setItem(STORAGE_KEY, String(lastKnownId));
                    }
                    return;
                }
                if (latestId > lastKnownId) {
                    lastKnownId = latestId;
                    localStorage.setItem(STORAGE_KEY, String(lastKnownId));
                    if (Notification.permission === 'granted') {
                        notifyNew(data.latest);
                    }
                }
            })
            .catch(function () {});
    }

    poll();
    setInterval(poll, 15000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });
})();
