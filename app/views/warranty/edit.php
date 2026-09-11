<style>
.wr-wrap{display:flex;flex-direction:column;gap:0;}
.wr-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#1e3a5f;border-radius:0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
.wr-topbar .wr-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.wh-sel{padding:5px 12px;border-radius:8px;font-size:0.8rem;font-weight:600;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);color:#fff;cursor:pointer;outline:none;}
.wh-sel option{background:#1e3a5f;color:#fff;}
.wr-body{background:#fff;border:1px solid #e5e7eb;border-top:none;padding:20px 24px;}
.wr-section{border:1.5px solid #e0e7ff;border-radius:10px;padding:16px 18px;margin-bottom:18px;}
.wr-section-title{font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#4338ca;margin-bottom:14px;display:flex;align-items:center;gap:6px;}
.wr-field label{font-size:0.78rem;font-weight:600;color:#64748b;margin-bottom:4px;display:block;}
.wr-field input,.wr-field select,.wr-field textarea{width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:7px 11px;font-size:0.85rem;color:#1e293b;outline:none;background:#fafbff;transition:border-color 0.15s;}
.wr-field input:focus,.wr-field select:focus,.wr-field textarea:focus{border-color:#6366f1;background:#fff;}
.wr-field select{cursor:pointer;}
.search-wrap{position:relative;}
.search-input{width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:7px 11px;font-size:0.85rem;color:#1e293b;outline:none;background:#fafbff;box-sizing:border-box;}
.search-input:focus{border-color:#6366f1;background:#fff;}
.search-drop{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #e0e7ff;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:220px;overflow-y:auto;margin-top:3px;display:none;}
.search-drop-item{padding:8px 12px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #f8fafc;color:#1e293b;}
.search-drop-item:last-child{border-bottom:none;}
.search-drop-item:hover,.search-drop-item.active{background:#f0f4ff;}
.selected-badge{display:none;margin-top:5px;padding:6px 10px;border-radius:6px;font-size:0.82rem;font-weight:600;}
.imei-result{background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:8px;padding:9px 13px;font-size:0.82rem;margin-top:6px;display:none;}
.imei-result.found{background:#f0fdf4;border-color:#86efac;color:#15803d;}
.imei-result.notfound{background:#fef2f2;border-color:#fca5a5;color:#dc2626;}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:2px solid #e0e7ff;border-radius:0 0 12px 12px;position:sticky;bottom:0;z-index:90;box-shadow:0 -4px 12px rgba(0,0,0,0.06);}
.btn-save{padding:8px 28px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#059669,#047857);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-save:disabled{opacity:0.5;cursor:not-allowed;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
@media(max-width:700px){.grid-2,.grid-3{grid-template-columns:1fr;}}
</style>

<form method="POST" action="?page=warranty&action=update" id="wrForm">
<?= Auth::csrfField() ?>
<input type="hidden" name="id" value="<?= (int)$wr['id'] ?>">
<input type="hidden" name="sale_id" value="<?= (int)($wr['sale_id'] ?? 0) ?>">
<input type="hidden" name="party_id"    id="partyIdInput" value="<?= (int)$wr['party_id'] ?>">
<input type="hidden" name="old_item_id" id="oldItemIdInput" value="<?= (int)$wr['old_item_id'] ?>">
<input type="hidden" name="new_item_id" id="newItemIdInput" value="<?= (int)$wr['new_item_id'] ?>">
<input type="hidden" name="old_imei2" value="<?= htmlspecialchars((string)($wr['old_imei2'] ?? '')) ?>">
<input type="hidden" name="new_imei2" value="<?= htmlspecialchars((string)($wr['new_imei2'] ?? '')) ?>">

<div class="wr-wrap">

    <!-- TOP BAR -->
    <div class="wr-topbar">
        <div class="wr-title"><i class="bi bi-pencil-square"></i> Edit Warranty Replacement</div>
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:flex-end;font-size:0.75rem;">
                <span style="color:rgba(255,255,255,0.6);margin-bottom:2px;">Branch</span>
                <select name="warehouse_id" id="whSelect" class="wh-sel" disabled title="Branch cannot be changed on edit">
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= (int)$wr['warehouse_id'] === (int)$wh['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wh['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="wr-body">

        <!-- REF + DATE + STATUS -->
        <div class="grid-3" style="margin-bottom:18px;">
            <div class="wr-field">
                <label>Replacement Ref No</label>
                <input type="text" value="<?= htmlspecialchars($wr['replacement_no']) ?>" readonly
                    style="background:#f0fdf4;color:#6366f1;font-weight:700;cursor:default;">
            </div>
            <div class="wr-field">
                <label>Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($wr['date']) ?>">
            </div>
            <div class="wr-field">
                <label>Status</label>
                <select name="status">
                    <option value="completed" <?= $wr['status'] === 'completed' ? 'selected' : '' ?>>✅ Completed — device replaced</option>
                    <option value="pending_supplier" <?= $wr['status'] === 'pending_supplier' ? 'selected' : '' ?>>⏳ Pending Supplier Return</option>
                </select>
            </div>
        </div>

        <!-- CUSTOMER + SALE -->
        <div class="wr-section">
            <div class="wr-section-title"><i class="bi bi-person"></i> Customer & Original Sale</div>
            <div>
                <div class="wr-field">
                    <label>Customer *</label>
                    <div class="search-wrap">
                        <input type="text" class="search-input" id="customerSearch"
                            placeholder="Type to search customer..." autocomplete="off"
                            value="<?= htmlspecialchars($wr['customer_name']) ?>">
                        <div class="search-drop" id="customerDrop"></div>
                    </div>
                    <div class="selected-badge" id="customerBadge"
                        style="display:block;background:#f0fdf4;color:#15803d;">
                        <i class="bi bi-person-check-fill me-1"></i>
                        <?= htmlspecialchars($wr['customer_name']) ?>
                        <?php if (!empty($wr['customer_phone'])): ?>
                         · <?= htmlspecialchars($wr['customer_phone']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($wr['sale_invoice_no'])): ?>
                <p class="mb-0 mt-2 text-muted" style="font-size:0.8rem;">
                    Original sale: <strong><?= htmlspecialchars($wr['sale_invoice_no']) ?></strong>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- FAULTY DEVICE -->
        <div class="wr-section" style="border-color:#fecaca;">
            <div class="wr-section-title" style="color:#dc2626;">
                <i class="bi bi-x-circle"></i> Faulty Device (Returned by Customer)
            </div>
            <div style="margin-bottom:14px;">
                <div class="wr-field">
                    <label>Old IMEI (faulty device) *</label>
                    <input type="text" name="old_imei" id="oldImeiInput"
                        placeholder="Scan or type IMEI..." required
                        value="<?= htmlspecialchars((string)($wr['old_imei'] ?? '')) ?>"
                        style="font-family:monospace;letter-spacing:0.5px;">
                    <div class="imei-result" id="oldImeiResult"></div>
                </div>
            </div>
            <div class="wr-field">
                <label>Faulty Item / Model *</label>
                <div class="search-wrap">
                    <input type="text" class="search-input" id="oldItemSearch"
                        placeholder="Type to search item..." autocomplete="off"
                        value="<?= htmlspecialchars($wr['old_item_name']) ?>">
                    <div class="search-drop" id="oldItemDrop"></div>
                </div>
                <div class="selected-badge" id="oldItemBadge"
                    style="display:block;background:#fef2f2;color:#dc2626;">
                    <i class="bi bi-box-seam me-1"></i> <?= htmlspecialchars($wr['old_item_name']) ?>
                </div>
            </div>
        </div>

        <!-- REPLACEMENT DEVICE -->
        <div class="wr-section" style="border-color:#86efac;">
            <div class="wr-section-title" style="color:#16a34a;">
                <i class="bi bi-check-circle"></i> Replacement Device (Given to Customer)
            </div>
            <div style="margin-bottom:14px;">
                <div class="wr-field">
                    <label>New IMEI (replacement device) *</label>
                    <div class="search-wrap">
                        <input type="text" name="new_imei" id="newImeiInput"
                            placeholder="Scan or type new IMEI..."
                            class="search-input"
                            autocomplete="off" required
                            value="<?= htmlspecialchars((string)($wr['new_imei'] ?? '')) ?>"
                            style="font-family:monospace;letter-spacing:0.5px;">
                        <div class="search-drop" id="newImeiDrop"></div>
                    </div>
                    <div class="imei-result" id="newImeiResult"></div>
                </div>
            </div>
            <div class="wr-field">
                <label>Replacement Item / Model *</label>
                <div class="search-wrap">
                    <input type="text" class="search-input" id="newItemSearch"
                        placeholder="Type to search replacement model..." autocomplete="off"
                        value="<?= htmlspecialchars($wr['new_item_name']) ?>">
                    <div class="search-drop" id="newItemDrop"></div>
                </div>
                <div class="selected-badge" id="newItemBadge"
                    style="display:block;background:#f0fdf4;color:#15803d;">
                    <i class="bi bi-box-seam me-1"></i> <?= htmlspecialchars($wr['new_item_name']) ?>
                </div>
            </div>
        </div>

        <!-- FAULT + NOTES -->
        <div class="grid-2">
            <div class="wr-field">
                <label>Fault Description *</label>
                <textarea name="fault_description" rows="3"
                    placeholder="e.g. Dead screen, battery not charging, speaker issue..."><?= htmlspecialchars((string)($wr['fault_description'] ?? '')) ?></textarea>
            </div>
            <div class="wr-field">
                <label>Internal Notes <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                <textarea name="notes" rows="3" placeholder="Any internal notes..."><?= htmlspecialchars((string)($wr['notes'] ?? '')) ?></textarea>
            </div>
        </div>

    </div>

    <!-- SAVE BAR -->
    <div class="save-bar">
        <a href="?page=warranty&action=view&id=<?= (int)$wr['id'] ?>"
           style="padding:8px 20px;border-radius:8px;font-size:0.88rem;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;text-decoration:none;display:inline-flex;align-items:center;">
            Cancel
        </a>
        <button type="submit" class="btn-save" id="wrSaveBtn">
            <i class="bi bi-check-lg"></i> Save Changes
        </button>
    </div>

</div>
</form>

<script>
const ALL_CUSTOMERS = <?= json_encode($customers) ?>;
const ALL_ITEMS     = <?= json_encode($items) ?>;
const searchTimers  = {};
const CURRENT_NEW_IMEI = <?= json_encode((string)($wr['new_imei'] ?? '')) ?>;

function he(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function jsStr(s) {
    return String(s ?? '').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/\r?\n/g,' ');
}

function hideDrop(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function renderDrop(dropId, html) {
    const el = document.getElementById(dropId);
    el.innerHTML = html;
    el.style.display = html ? 'block' : 'none';
}

function showCustomerDrop() {
    filterCustomers(document.getElementById('customerSearch').value);
}

function filterCustomers(q) {
    const list = q.trim()
        ? ALL_CUSTOMERS.filter(c => c.name.toLowerCase().includes(q.toLowerCase())).slice(0, 12)
        : ALL_CUSTOMERS.slice(0, 15);

    if (!list.length) { hideDrop('customerDrop'); return; }

    renderDrop('customerDrop', list.map(c => `
        <div class="search-drop-item"
            onmousedown="selectCustomer(${c.id},'${jsStr(c.name)}','${jsStr(c.phone||'')}')">
            <strong>${he(c.name)}</strong>
            ${c.phone ? `<small style="color:#94a3b8;float:right;">${he(c.phone)}</small>` : ''}
        </div>`).join(''));
}

function selectCustomer(id, name, phone) {
    document.getElementById('partyIdInput').value = id;
    document.getElementById('customerSearch').value = name;
    hideDrop('customerDrop');
    const badge = document.getElementById('customerBadge');
    badge.style.display = 'block';
    badge.innerHTML = `<i class="bi bi-person-check-fill me-1"></i> ${he(name)}${phone ? ' · ' + he(phone) : ''}`;
    checkSave();
}

function lookupOldImei(imei) {
    clearTimeout(searchTimers.oldImei);
    const box = document.getElementById('oldImeiResult');
    if (imei.length < 5) { box.style.display = 'none'; return; }
    searchTimers.oldImei = setTimeout(() => {
        fetch(`?page=warranty&action=lookupImei&imei=${encodeURIComponent(imei)}`)
            .then(r => r.json()).then(data => {
                box.style.display = 'block';
                if (!data) {
                    box.className = 'imei-result notfound';
                    box.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i> IMEI not found — select item manually below`;
                    return;
                }
                const statusMap = {sold:'Sold', in_stock:'In Stock', defective:'⚠️ Already Defective', dumped:'♻️ Dumped', returned:'Returned'};
                box.className = 'imei-result found';
                box.innerHTML = `<i class="bi bi-check-circle me-1"></i>
                    <strong>${he(data.item_name)}</strong>${data.sku ? ` (${he(data.sku)})` : ''} ·
                    Status: <strong>${he(statusMap[data.status]||data.status)}</strong>
                    ${data.sale_invoice_no ? ` · Sale: <strong>${he(data.sale_invoice_no)}</strong>` : ''}
                    ${data.customer_name   ? ` · Customer: <strong>${he(data.customer_name)}</strong>` : ''}`;

                setItem('oldItemSearch','oldItemDrop','oldItemIdInput','oldItemBadge','red', data.item_id, data.item_name);

                if (!document.getElementById('newItemIdInput').value) {
                    setItem('newItemSearch','newItemDrop','newItemIdInput','newItemBadge','green', data.item_id, data.item_name);
                }

                if (data.customer_id && !document.getElementById('partyIdInput').value) {
                    selectCustomer(data.customer_id, data.customer_name, '');
                }
            });
    }, 400);
}

function searchInStockImei(q) {
    clearTimeout(searchTimers.newImei);
    const box = document.getElementById('newImeiResult');
    if (q.length < 3) { hideDrop('newImeiDrop'); box.style.display='none'; return; }
    // Keep currently assigned IMEI selectable even if already sold on this replacement
    if (CURRENT_NEW_IMEI && q === CURRENT_NEW_IMEI) {
        hideDrop('newImeiDrop');
        box.className = 'imei-result found';
        box.style.display = 'block';
        box.innerHTML = `<i class="bi bi-check-circle me-1"></i> Current replacement IMEI`;
        return;
    }
    const whId = document.getElementById('whSelect').value;
    searchTimers.newImei = setTimeout(() => {
        fetch(`?page=warranty&action=searchNewImei&q=${encodeURIComponent(q)}&warehouse_id=${whId}`)
            .then(r => r.json()).then(rows => {
                if (!rows.length) { hideDrop('newImeiDrop'); return; }
                renderDrop('newImeiDrop', rows.map(row => `
                    <div class="search-drop-item"
                        onmousedown="selectNewImei('${jsStr(row.imei)}','${jsStr(row.item_name)}',${row.item_id})">
                        <code style="font-size:0.8rem;color:#16a34a;">${he(row.imei)}</code>
                        <span style="color:#475569;"> — ${he(row.item_name)}</span>
                    </div>`).join(''));
            });
    }, 250);
}

function selectNewImei(imei, itemName, itemId) {
    document.getElementById('newImeiInput').value = imei;
    hideDrop('newImeiDrop');
    const box = document.getElementById('newImeiResult');
    box.className = 'imei-result found';
    box.style.display = 'block';
    box.innerHTML = `<i class="bi bi-check-circle me-1"></i> <strong>${he(imei)}</strong> — ${he(itemName)} · In Stock`;
    setItem('newItemSearch','newItemDrop','newItemIdInput','newItemBadge','green', itemId, itemName);
    checkSave();
}

function showItemDrop(dropId, hiddenId, badgeId, color) {
    const searchId = dropId === 'oldItemDrop' ? 'oldItemSearch' : 'newItemSearch';
    filterItems(document.getElementById(searchId).value, dropId, hiddenId, badgeId, color);
}

function filterItems(q, dropId, hiddenId, badgeId, color) {
    const list = q.trim()
        ? ALL_ITEMS.filter(i => i.name.toLowerCase().includes(q.toLowerCase()) || (i.sku||'').toLowerCase().includes(q.toLowerCase())).slice(0, 15)
        : ALL_ITEMS.slice(0, 15);

    if (!list.length) { hideDrop(dropId); return; }

    renderDrop(dropId, list.map(item => `
        <div class="search-drop-item"
            onmousedown="setItem('${dropId==='oldItemDrop'?'oldItemSearch':'newItemSearch'}','${dropId}','${hiddenId}','${badgeId}','${color}',${item.id},'${jsStr(item.name)}')">
            <strong>${he(item.name)}</strong>
            ${item.sku ? `<small style="color:#94a3b8;"> · ${he(item.sku)}</small>` : ''}
        </div>`).join(''));
}

function setItem(searchId, dropId, hiddenId, badgeId, color, itemId, itemName) {
    document.getElementById(hiddenId).value     = itemId;
    document.getElementById(searchId).value     = itemName;
    hideDrop(dropId);
    const badge = document.getElementById(badgeId);
    badge.style.display = 'block';
    const bg  = color === 'red'   ? '#fef2f2' : '#f0fdf4';
    const fg  = color === 'red'   ? '#dc2626' : '#15803d';
    badge.style.background = bg;
    badge.style.color      = fg;
    badge.innerHTML = `<i class="bi bi-box-seam me-1"></i> ${he(itemName)}`;
    checkSave();
}

function checkSave() {
    const oldImei = (document.getElementById('oldImeiInput').value || '').trim();
    const newImei = (document.getElementById('newImeiInput').value || '').trim();
    const ok = document.getElementById('partyIdInput').value &&
               document.getElementById('oldItemIdInput').value &&
               document.getElementById('newItemIdInput').value &&
               oldImei !== '' && newImei !== '';
    document.getElementById('wrSaveBtn').disabled = !ok;
}

document.getElementById('customerSearch').addEventListener('input', function () {
    filterCustomers(this.value);
});
document.getElementById('customerSearch').addEventListener('focus', showCustomerDrop);
document.getElementById('customerSearch').addEventListener('blur', function () {
    setTimeout(() => hideDrop('customerDrop'), 200);
});

document.getElementById('oldImeiInput').addEventListener('input', function () {
    lookupOldImei(this.value);
    checkSave();
});

document.getElementById('newImeiInput').addEventListener('input', function () {
    searchInStockImei(this.value);
    checkSave();
});
document.getElementById('newImeiInput').addEventListener('blur', function () {
    setTimeout(() => hideDrop('newImeiDrop'), 200);
});

document.getElementById('oldItemSearch').addEventListener('input', function () {
    filterItems(this.value, 'oldItemDrop', 'oldItemIdInput', 'oldItemBadge', 'red');
});
document.getElementById('oldItemSearch').addEventListener('focus', function () {
    showItemDrop('oldItemDrop', 'oldItemIdInput', 'oldItemBadge', 'red');
});
document.getElementById('oldItemSearch').addEventListener('blur', function () {
    setTimeout(() => hideDrop('oldItemDrop'), 200);
});

document.getElementById('newItemSearch').addEventListener('input', function () {
    filterItems(this.value, 'newItemDrop', 'newItemIdInput', 'newItemBadge', 'green');
});
document.getElementById('newItemSearch').addEventListener('focus', function () {
    showItemDrop('newItemDrop', 'newItemIdInput', 'newItemBadge', 'green');
});
document.getElementById('newItemSearch').addEventListener('blur', function () {
    setTimeout(() => hideDrop('newItemDrop'), 200);
});

checkSave();
</script>
