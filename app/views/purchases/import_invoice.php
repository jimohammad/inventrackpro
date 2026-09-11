<?php
$step         = $step ?? 'upload';
$resolved     = is_array($resolved ?? null) ? $resolved : null;
$duplicate    = $duplicate ?? null;
$importNonce  = (string) ($importNonce ?? '');
$pack         = is_array($pack ?? null) ? $pack : [];
$filename     = (string) ($pack['filename'] ?? '');
$fileDate     = trim((string) (($resolved['invoice']['date'] ?? '') ?: ''));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fileDate)) {
    $fileDate = date('Y-m-d');
}
$canSave = is_array($resolved) && !empty($resolved['ok']);
$money = static fn($v) => APP_CURRENCY . ' ' . number_format((float) $v, DECIMAL_PLACES);
?>
<style>
.imp-ac{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:40;box-shadow:0 6px 20px rgba(0,0,0,.12);max-height:280px;overflow-y:auto;margin-top:4px;}
.imp-ac-item{padding:10px 14px;cursor:pointer;font-size:.85rem;border-bottom:1px solid #f1f5f9;color:#1e293b;display:flex;align-items:center;justify-content:space-between;gap:12px;}
.imp-ac-item:last-child{border-bottom:none;}
.imp-ac-item:hover,.imp-ac-item.active{background:#eff6ff;}
.imp-ac-item strong{display:block;font-weight:700;}
.imp-ac-item small{color:#94a3b8;font-size:.78rem;white-space:nowrap;}
.imp-mismatch td{background:#fef2f2;}
</style>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=purchases&action=create" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="page-title mb-0">Import sales invoice</h1>
        <p class="text-muted mb-0" style="font-size:0.82rem;">Upload a JSON exported from another shop’s sales invoice. Items match by SKU. You pick the supplier here.</p>
    </div>
</div>

<?php if ($step !== 'preview'): ?>
<div class="card mb-3" style="border:none;border-radius:14px;">
    <div class="card-body">
        <form method="POST" action="?page=purchases&action=importInvoice" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            <label class="form-label" style="font-weight:600;font-size:0.82rem;">Invoice file <span class="text-muted fw-normal">(.iqbal.json)</span></label>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="file" name="invoice_file" class="form-control form-control-sm" accept=".json,application/json" required style="max-width:420px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i> Preview</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<?php
    $inv = $resolved['invoice'] ?? [];
    $fromShop = (string) ($resolved['from_shop'] ?? '');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">
        File: <strong><?= htmlspecialchars($filename !== '' ? $filename : 'invoice.iqbal.json') ?></strong>
        <?php if ($fromShop !== ''): ?> · From <?= htmlspecialchars($fromShop) ?><?php endif; ?>
        · Source <?= htmlspecialchars((string) ($resolved['source_invoice_no'] ?? '')) ?>
        <?php if (!empty($inv['party_name'])): ?> · Sold to <?= htmlspecialchars((string) $inv['party_name']) ?><?php endif; ?>
    </div>
    <a href="?page=purchases&action=importInvoiceClear" class="btn btn-sm btn-outline-secondary">Choose another file</a>
</div>

<?php if (!empty($resolved['errors'])): ?>
<div class="alert alert-danger">
    <strong>Cannot save yet.</strong> Same SKU must exist on this shop for every line.
    <ul class="mb-0 mt-2">
        <?php foreach ($resolved['errors'] as $err): ?>
        <li><?= htmlspecialchars((string) $err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($duplicate): ?>
<div class="alert alert-warning mb-3">
    This source invoice was already imported as
    <a href="?page=purchases&action=detail&id=<?= (int) $duplicate['id'] ?>"><?= htmlspecialchars((string) ($duplicate['invoice_no'] ?? '')) ?></a>
    on <?= htmlspecialchars((string) ($duplicate['date'] ?? '')) ?>.
    Tick <strong>Import anyway</strong> below to create another purchase.
</div>
<?php endif; ?>

<form method="POST" action="?page=purchases&action=importInvoiceStore" id="impForm">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="import_nonce" value="<?= htmlspecialchars($importNonce) ?>">

    <div class="card mb-3" style="border:none;border-radius:14px;">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 position-relative" id="impSupplierWrap">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Supplier</label>
                    <input type="text" id="impSupplierSearch" class="form-control form-control-sm" placeholder="Search supplier…" autocomplete="off" <?= $canSave ? 'required' : 'disabled' ?>>
                    <input type="hidden" name="party_id" id="impSupplierId" value="">
                    <div class="imp-ac" id="impSupplierDrop" style="display:none;"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($fileDate) ?>" <?= $canSave ? '' : 'disabled' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Supplier ref</label>
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($resolved['source_invoice_no'] ?? '')) ?>" disabled>
                </div>
            </div>
            <?php if ($duplicate): ?>
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="confirm_duplicate" value="1" id="impDup" <?= $canSave ? '' : 'disabled' ?>>
                <label class="form-check-label" for="impDup">Import anyway (create a second purchase)</label>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-3" style="border:none;border-radius:14px;">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:0.85rem;">
                <thead>
                    <tr>
                        <th>This shop</th>
                        <th>SKU</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Cost / pc</th>
                        <th class="text-end">Line</th>
                        <th class="text-center">IMEI</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($resolved['lines'] as $line): ?>
                    <tr class="<?= empty($line['matched']) ? 'imp-mismatch' : '' ?>">
                        <td>
                            <?php if (!empty($line['matched'])): ?>
                                <?= htmlspecialchars((string) $line['local_name']) ?>
                                <?php if (($line['file_name'] ?? '') !== '' && ($line['file_name'] ?? '') !== ($line['local_name'] ?? '')): ?>
                                <div class="small text-muted">File: <?= htmlspecialchars((string) $line['file_name']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-danger"><?= htmlspecialchars((string) ($line['file_name'] ?: 'Unmatched')) ?></span>
                                <?php if (!empty($line['error'])): ?>
                                <div class="small"><?= htmlspecialchars((string) $line['error']) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars((string) ($line['sku'] ?? '')) ?></code></td>
                        <td class="text-center"><?= (int) ($line['quantity'] ?? 0) ?></td>
                        <td class="text-end"><?= number_format((float) ($line['unit_price'] ?? 0), DECIMAL_PLACES) ?></td>
                        <td class="text-end"><?= number_format((float) ($line['line_total'] ?? 0), DECIMAL_PLACES) ?></td>
                        <td class="text-center">
                            <?php if (!empty($line['has_imei'])): ?>
                                <?= (int) ($line['imei_count'] ?? 0) ?>/<?= (int) ($line['quantity'] ?? 0) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Subtotal</th>
                        <th class="text-end"><?= $money($resolved['subtotal'] ?? 0) ?></th>
                        <th></th>
                    </tr>
                    <?php if ((float) ($resolved['discount'] ?? 0) > 0.0005): ?>
                    <tr>
                        <th colspan="4" class="text-end">Discount</th>
                        <th class="text-end"><?= $money($resolved['discount'] ?? 0) ?></th>
                        <th></th>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th colspan="4" class="text-end">Grand total (unpaid)</th>
                        <th class="text-end"><?= $money($resolved['grand_total'] ?? 0) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="?page=purchases&action=create" class="btn btn-outline-secondary btn-sm">Cancel</a>
        <button type="submit" class="btn btn-primary btn-sm" <?= $canSave ? '' : 'disabled' ?> id="impSaveBtn">
            Create purchase
        </button>
    </div>
</form>

<script>
(function () {
    var input = document.getElementById('impSupplierSearch');
    var hidden = document.getElementById('impSupplierId');
    var drop = document.getElementById('impSupplierDrop');
    var wrap = document.getElementById('impSupplierWrap');
    if (!input || !hidden || !drop) return;
    var timer = null;
    var results = [];
    var hi = -1;

    function render(parties) {
        results = parties || [];
        hi = -1;
        if (!results.length) { drop.style.display = 'none'; return; }
        drop.innerHTML = results.map(function (p, idx) {
            var typeLbl = p.type === 'customer' ? 'Customer' : (p.type === 'both' ? 'Customer & Supplier' : '');
            var typeHtml = typeLbl ? '<small>' + typeLbl + '</small>' : '';
            return '<div class="imp-ac-item" data-idx="' + idx + '"><strong></strong>' + typeHtml + '</div>';
        }).join('');
        drop.querySelectorAll('.imp-ac-item').forEach(function (el) {
            var idx = parseInt(el.dataset.idx, 10);
            el.querySelector('strong').textContent = results[idx].name || '';
            el.addEventListener('mousedown', function (e) {
                e.preventDefault();
                pick(results[idx]);
            });
        });
        drop.style.display = 'block';
    }
    function pick(p) {
        input.value = p.name || '';
        hidden.value = String(p.id || '');
        drop.style.display = 'none';
        input.classList.add('is-valid');
    }
    function search() {
        var q = input.value.trim();
        if (q.length < 1) { drop.style.display = 'none'; return; }
        fetch('?page=sales&action=searchParties&q=' + encodeURIComponent(q) + '&type=purchase&balances=0')
            .then(function (r) { return r.json(); })
            .then(function (parties) {
                if (input.value.trim() !== q) return;
                render(parties);
            });
    }
    input.addEventListener('input', function () {
        hidden.value = '';
        input.classList.remove('is-valid');
        clearTimeout(timer);
        timer = setTimeout(search, 250);
    });
    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) drop.style.display = 'none';
    });
    document.getElementById('impForm').addEventListener('submit', function (e) {
        if (!hidden.value) {
            e.preventDefault();
            alert('Select a supplier.');
            input.focus();
        }
    });
})();
</script>
<?php endif; ?>
