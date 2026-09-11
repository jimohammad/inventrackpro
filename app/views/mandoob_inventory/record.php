<?php
$row        = $row ?? [];
$lastItems  = $lastItems ?? [];
$today      = $today ?? date('Y-m-d');
$name       = (string) ($row['name'] ?? 'Mandoob');
$intervalM  = max(1, min(24, (int) ($row['interval_months'] ?? 3)));
$isPaused   = (int) ($row['is_paused'] ?? 0) === 1;
$lastJson   = json_encode($lastItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
if ($lastJson === false) {
    $lastJson = '[]';
}
?>
<style>
.mi-rec-search-wrap { position: relative; }
.mi-rec-search-wrap .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6366f1; pointer-events: none; }
.mi-rec-search-wrap input { padding-left: 38px; }
.mi-rec-drop { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1.5px solid #e0e7ff; border-radius: 10px; z-index: 30; box-shadow: 0 6px 20px rgba(0,0,0,.12); max-height: 280px; overflow-y: auto; margin-top: 4px; }
.mi-rec-drop-item { padding: 9px 14px; cursor: pointer; font-size: .83rem; border-bottom: 1px solid #f8fafc; }
.mi-rec-drop-item:last-child { border-bottom: none; }
.mi-rec-drop-item:hover, .mi-rec-drop-item.active { background: #eff6ff; }
.mi-rec-tbl input, .mi-rec-tbl textarea { border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 8px; font-size: .85rem; width: 100%; }
.mi-rec-tbl textarea { min-height: 38px; resize: vertical; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .78rem; }
.mi-rec-empty td { padding: 2rem 1rem; color: #94a3b8; text-align: center; }
</style>

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="?page=mandoob_inventory" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 class="page-title mb-0"><i class="bi bi-check-circle me-2 text-success"></i>Record inventory</h1>
            <p class="page-subtitle mb-0">
                <?= htmlspecialchars($name) ?>
                <?php if (!empty($row['phone'])): ?>
                · <?= htmlspecialchars((string) $row['phone']) ?>
                <?php endif; ?>
                · every <?= $intervalM ?> mo
                <?php if ($isPaused): ?>
                <span class="badge bg-secondary-subtle text-secondary ms-1">Paused — saving will resume the countdown</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <a href="?page=mandoob_inventory&action=history&id=<?= (int) ($row['id'] ?? 0) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-clock-history me-1"></i>History
    </a>
</div>

<form method="post" action="?page=mandoob_inventory&action=record_count" id="miRecordForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="miCountDate">Date of inventory <span class="text-danger">*</span></label>
                    <input type="date" name="count_date" id="miCountDate" class="form-control" required
                           max="<?= htmlspecialchars($today) ?>" value="<?= htmlspecialchars($today) ?>">
                    <div class="form-text">Next due = this date + <?= $intervalM ?> month<?= $intervalM === 1 ? '' : 's' ?>.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold" for="miCountNotes">Notes <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="notes" id="miCountNotes" class="form-control" maxlength="500"
                           placeholder="e.g. counted at shop, missing 2 pieces">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
            <div class="fw-semibold">
                <i class="bi bi-box-seam me-1 text-primary"></i>Counted items
                <span class="badge bg-primary-subtle text-primary ms-1" id="miItemBadge">0 items</span>
            </div>
            <?php if ($lastItems !== []): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" id="miLoadLastBtn">
                <i class="bi bi-copy me-1"></i>Load last count (<?= count($lastItems) ?>)
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body border-bottom">
            <div class="mi-rec-search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="miItemSearch" class="form-control" placeholder="Search item by name, SKU, or barcode…" autocomplete="off">
                <div class="mi-rec-drop" id="miItemDrop" style="display:none;"></div>
            </div>
            <div class="form-text mt-2">Snapshot only — this does not change warehouse stock or IMEI status.</div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 mi-rec-tbl">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Item</th>
                        <th style="width:110px;">SKU</th>
                        <th style="width:90px;" class="text-center">Qty</th>
                        <th>IMEIs <span class="text-muted fw-normal">(optional)</span></th>
                        <th style="width:44px;"></th>
                    </tr>
                </thead>
                <tbody id="miItemTbody">
                    <tr id="miEmptyRow" class="mi-rec-empty">
                        <td colspan="6">
                            <i class="bi bi-search d-block mb-1" style="font-size:1.4rem;"></i>
                            Search and add the items found with this mandoob.
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="fw-semibold">Total quantity</td>
                        <td class="text-center fw-bold" id="miTotalQty">0</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2">
        <a href="?page=mandoob_inventory" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-lg me-1"></i>Save inventory &amp; reset countdown
        </button>
    </div>
</form>

<script>
(function () {
    var lastItems = <?= $lastJson ?>;
    var tbody = document.getElementById('miItemTbody');
    var searchInput = document.getElementById('miItemSearch');
    var drop = document.getElementById('miItemDrop');
    var searchTimer = null;
    var dropItems = [];
    var dropIndex = -1;

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function rowCount() {
        return tbody.querySelectorAll('tr[data-item-id]').length;
    }

    function refreshMeta() {
        var n = 0;
        var qty = 0;
        tbody.querySelectorAll('tr[data-item-id]').forEach(function (tr, i) {
            n++;
            var num = tr.querySelector('.mi-row-num');
            if (num) num.textContent = String(i + 1);
            qty += parseInt(tr.querySelector('.mi-qty')?.value || '0', 10) || 0;
        });
        var badge = document.getElementById('miItemBadge');
        var totalEl = document.getElementById('miTotalQty');
        if (badge) badge.textContent = n + ' item' + (n === 1 ? '' : 's');
        if (totalEl) totalEl.textContent = String(qty);
        var empty = document.getElementById('miEmptyRow');
        if (n === 0 && !empty) {
            tbody.innerHTML = '<tr id="miEmptyRow" class="mi-rec-empty"><td colspan="6"><i class="bi bi-search d-block mb-1" style="font-size:1.4rem;"></i>Search and add the items found with this mandoob.</td></tr>';
        }
    }

    function addRow(item, opts) {
        opts = opts || {};
        var id = parseInt(item.item_id || item.id, 10);
        if (!id) return;
        if (document.querySelector('tr[data-item-id="' + id + '"]')) {
            if (!opts.silentDup) {
                window.alert('"' + (item.item_name || item.name || 'Item') + '" is already on this count.');
            }
            return;
        }
        var empty = document.getElementById('miEmptyRow');
        if (empty) empty.remove();

        var name = item.item_name || item.name || ('Item #' + id);
        var sku = item.sku || '';
        var qty = parseInt(opts.quantity || item.quantity || 1, 10);
        if (!Number.isFinite(qty) || qty < 1) qty = 1;
        var imeis = opts.imeis != null ? String(opts.imeis) : (item.imeis ? String(item.imeis) : '');
        var hasImei = parseInt(item.has_imei || '0', 10) === 1 || imeis !== '';

        var tr = document.createElement('tr');
        tr.setAttribute('data-item-id', String(id));
        tr.innerHTML =
            '<td class="mi-row-num text-muted"></td>' +
            '<td class="fw-semibold">' + esc(name) +
                '<input type="hidden" name="items[' + id + '][item_id]" value="' + id + '">' +
            '</td>' +
            '<td class="text-muted small">' + esc(sku || '—') + '</td>' +
            '<td><input type="number" class="mi-qty text-center" name="items[' + id + '][quantity]" min="1" max="9999" value="' + qty + '" required></td>' +
            '<td>' +
                '<textarea name="items[' + id + '][imeis]" class="mi-imeis" rows="1" maxlength="8000" placeholder="' +
                    (hasImei ? 'One IMEI per line' : 'Optional') + '">' + esc(imeis) + '</textarea>' +
            '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-danger mi-remove" title="Remove">&times;</button>' +
            '</td>';
        tbody.appendChild(tr);
        refreshMeta();
        var qtyInput = tr.querySelector('.mi-qty');
        if (qtyInput && !opts.skipFocus) qtyInput.focus();
    }

    function hideDrop() {
        drop.style.display = 'none';
        drop.innerHTML = '';
        dropItems = [];
        dropIndex = -1;
    }

    function renderDrop(items) {
        dropItems = items || [];
        dropIndex = dropItems.length ? 0 : -1;
        if (!dropItems.length) {
            hideDrop();
            return;
        }
        drop.innerHTML = dropItems.map(function (it, i) {
            return '<div class="mi-rec-drop-item' + (i === 0 ? ' active' : '') + '" data-idx="' + i + '">' +
                '<span class="fw-semibold">' + esc(it.name) + '</span>' +
                '<small class="text-muted ms-2">' + esc(it.sku || '') + '</small>' +
                '<small class="text-muted float-end">Stock ' + esc(it.stock ?? 0) + '</small>' +
            '</div>';
        }).join('');
        drop.style.display = 'block';
    }

    function pickDrop(idx) {
        var it = dropItems[idx];
        if (!it) return;
        addRow(it);
        searchInput.value = '';
        hideDrop();
        searchInput.focus();
    }

    searchInput.addEventListener('input', function () {
        var q = searchInput.value.trim();
        clearTimeout(searchTimer);
        if (q.length < 1) { hideDrop(); return; }
        searchTimer = setTimeout(function () {
            fetch('?page=mandoob_inventory&action=searchItems&q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(renderDrop)
                .catch(function () { hideDrop(); });
        }, 220);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (drop.style.display === 'none') return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            dropIndex = Math.min(dropItems.length - 1, dropIndex + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            dropIndex = Math.max(0, dropIndex - 1);
        } else if (e.key === 'Enter') {
            if (dropIndex >= 0) {
                e.preventDefault();
                pickDrop(dropIndex);
            }
            return;
        } else if (e.key === 'Escape') {
            hideDrop();
            return;
        } else {
            return;
        }
        drop.querySelectorAll('.mi-rec-drop-item').forEach(function (el, i) {
            el.classList.toggle('active', i === dropIndex);
        });
    });

    drop.addEventListener('mousedown', function (e) {
        var el = e.target.closest('.mi-rec-drop-item');
        if (!el) return;
        e.preventDefault();
        pickDrop(parseInt(el.getAttribute('data-idx') || '-1', 10));
    });

    searchInput.addEventListener('blur', function () {
        setTimeout(hideDrop, 180);
    });

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('mi-qty')) refreshMeta();
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.mi-remove');
        if (!btn) return;
        btn.closest('tr')?.remove();
        refreshMeta();
    });

    var loadLast = document.getElementById('miLoadLastBtn');
    if (loadLast) {
        loadLast.addEventListener('click', function () {
            if (rowCount() > 0 && !window.confirm('Replace the current list with the last recorded count?')) {
                return;
            }
            tbody.innerHTML = '';
            (lastItems || []).forEach(function (it) {
                addRow(it, { quantity: it.quantity, imeis: it.imeis || '', silentDup: true, skipFocus: true });
            });
            refreshMeta();
        });
    }

    document.getElementById('miRecordForm').addEventListener('submit', function () {
        var btn = this.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';
        }
    });

    if (searchInput) searchInput.focus();
})();
</script>
