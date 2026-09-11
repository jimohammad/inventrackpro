<?php
$peerName = (string) ($peerName ?? 'other shop');
$peerConfigured = !empty($peerConfigured);
?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=intershop" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="page-title mb-0">Send stock to <?= htmlspecialchars($peerName) ?></h1>
        <p class="text-muted mb-0" style="font-size:0.82rem;">Same SKU must exist on both shops. Serial items need a scan for every unit. No sale or payment is created.</p>
    </div>
</div>

<?php if (!$peerConfigured): ?>
<div class="alert alert-warning">Set <code>INTERSHOP_PEER_URL</code> and <code>INTERSHOP_PEER_API_KEY</code> in the server <code>.env</code> before sending.</div>
<?php endif; ?>

<form method="POST" action="?page=intershop&action=store" id="istForm">
    <?= Auth::csrfField() ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" required value="<?= htmlspecialchars(date('Y-m-d')) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label" style="font-weight:600;font-size:0.82rem;">Notes</label>
                    <input type="text" name="notes" class="form-control form-control-sm" maxlength="500" placeholder="Optional">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Items</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" id="istAddRow">+ Add line</button>
            </div>
            <div id="istRows"></div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="?page=intershop" class="btn btn-outline-secondary btn-sm">Cancel</a>
        <button type="submit" class="btn btn-primary btn-sm" <?= $peerConfigured ? '' : 'disabled' ?>>Send stock</button>
    </div>
</form>

<template id="istRowTpl">
    <div class="ist-row border rounded p-3 mb-2" data-idx="__IDX__">
        <div class="row g-2 align-items-end">
            <div class="col-md-5 position-relative">
                <label class="form-label" style="font-size:0.75rem;">Item (needs SKU)</label>
                <input type="hidden" name="items[__IDX__][item_id]" class="ist-item-id">
                <input type="text" class="form-control form-control-sm ist-search" placeholder="Type name or SKU" autocomplete="off">
                <div class="ist-suggest list-group position-absolute w-100 shadow-sm" style="z-index:20;display:none;"></div>
                <small class="text-muted ist-sku-hint"></small>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:0.75rem;">Qty</label>
                <input type="number" min="1" step="1" name="items[__IDX__][quantity]" class="form-control form-control-sm ist-qty" value="1">
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-size:0.75rem;">Serials (one per line)</label>
                <textarea name="items[__IDX__][imeis]" class="form-control form-control-sm ist-imeis" rows="2" placeholder="Required if the item has IMEI"></textarea>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm ist-remove" title="Remove">&times;</button>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    var wrap = document.getElementById('istRows');
    var tpl = document.getElementById('istRowTpl').innerHTML;
    var idx = 0;
    var searchUrl = '?page=intershop&action=searchItems&q=';

    function addRow() {
        wrap.insertAdjacentHTML('beforeend', tpl.replaceAll('__IDX__', String(idx)));
        bindRow(wrap.lastElementChild);
        idx += 1;
    }

    function bindRow(row) {
        var search = row.querySelector('.ist-search');
        var suggest = row.querySelector('.ist-suggest');
        var hidden = row.querySelector('.ist-item-id');
        var hint = row.querySelector('.ist-sku-hint');
        var timer = null;
        row.querySelector('.ist-remove').addEventListener('click', function () { row.remove(); });
        search.addEventListener('input', function () {
            hidden.value = '';
            hint.textContent = '';
            clearTimeout(timer);
            var q = search.value.trim();
            if (q.length < 1) { suggest.style.display = 'none'; return; }
            timer = setTimeout(function () {
                fetch(searchUrl + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (items) {
                        suggest.innerHTML = '';
                        (items || []).slice(0, 12).forEach(function (it) {
                            var a = document.createElement('button');
                            a.type = 'button';
                            a.className = 'list-group-item list-group-item-action py-1';
                            var sku = it.sku || '';
                            a.textContent = it.name + (sku ? ' (' + sku + ')' : ' — NO SKU');
                            a.addEventListener('click', function () {
                                hidden.value = it.id;
                                search.value = it.name;
                                hint.textContent = sku ? ('SKU ' + sku + (it.has_imei == 1 ? ' · serial tracked' : '')) : 'This item has no SKU — set one before sending.';
                                suggest.style.display = 'none';
                            });
                            suggest.appendChild(a);
                        });
                        suggest.style.display = suggest.childElementCount ? 'block' : 'none';
                    })
                    .catch(function () { suggest.style.display = 'none'; });
            }, 200);
        });
        document.addEventListener('click', function (e) {
            if (!row.contains(e.target)) suggest.style.display = 'none';
        });
    }

    document.getElementById('istAddRow').addEventListener('click', addRow);
    addRow();
})();
</script>
