<style>
.wr-wrap{display:flex;flex-direction:column;gap:0;}
.wr-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:linear-gradient(135deg,#1e3a5f,#2d5a9e);border-radius:12px 12px 0 0;position:sticky;top:58px;z-index:90;box-shadow:0 2px 10px rgba(30,58,95,0.3);}
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

<form method="POST" action="?page=warranty&action=store" id="wrForm">
<?= Auth::csrfField() ?>
<input type="hidden" name="party_id"    id="partyIdInput">
<input type="hidden" name="old_item_id" id="oldItemIdInput">
<input type="hidden" name="new_item_id" id="newItemIdInput">

<div class="wr-wrap">

    <!-- TOP BAR -->
    <div class="wr-topbar">
        <div class="wr-title"><i class="bi bi-shield-plus"></i> New Warranty Replacement</div>
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:flex-end;font-size:0.75rem;">
                <span style="color:rgba(255,255,255,0.6);margin-bottom:2px;">Branch</span>
                <select name="warehouse_id" id="whSelect" class="wh-sel">
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= Auth::warehouseId() == $wh['id'] ? 'selected' : '' ?>>
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
                <input type="text" value="<?= $nextNo ?>" readonly
                    style="background:#f0fdf4;color:#6366f1;font-weight:700;cursor:default;">
            </div>
            <div class="wr-field">
                <label>Date</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="wr-field">
                <label>Status</label>
                <select name="status">
                    <option value="completed">✅ Completed — device replaced</option>
                    <option value="pending_supplier">⏳ Pending Supplier Return</option>
                </select>
            </div>
        </div>

        <!-- CUSTOMER + SALE -->
        <div class="wr-section">
            <div class="wr-section-title"><i class="bi bi-person"></i> Customer & Original Sale</div>
            <div>

                <!-- Customer searchable dropdown -->
                <div class="wr-field">
                    <label>Customer *</label>
                    <div class="search-wrap">
                        <input type="text" class="search-input" id="customerSearch"
                            placeholder="Type to search customer..." autocomplete="off"
                            oninput="filterCustomers(this.value)"
                            onfocus="showCustomerDrop()"
                            onblur="setTimeout(()=>hideDrop('customerDrop'),200)">
                        <div class="search-drop" id="customerDrop"></div>
                    </div>
                    <div class="selected-badge" id="customerBadge"
                        style="background:#f0fdf4;color:#15803d;"></div>
                </div>



            </div>
        </div>

        <!-- FAULTY DEVICE -->
        <div class="wr-section" style="border-color:#fecaca;">
            <div class="wr-section-title" style="color:#dc2626;">
                <i class="bi bi-x-circle"></i> Faulty Device (Returned by Customer)
            </div>
            <div style="margin-bottom:14px;">
                <div class="wr-field">
                    <label>Old IMEI (faulty device)</label>
                    <input type="text" name="old_imei" id="oldImeiInput"
                        placeholder="Scan or type IMEI..."
                        oninput="lookupOldImei(this.value)"
                        style="font-family:monospace;letter-spacing:0.5px;">
                    <div class="imei-result" id="oldImeiResult"></div>
                </div>

            </div>
            <div class="wr-field">
                <label>Faulty Item / Model *</label>
                <div class="search-wrap">
                    <input type="text" class="search-input" id="oldItemSearch"
                        placeholder="Type to search item..." autocomplete="off"
                        oninput="filterItems(this.value,'oldItemDrop','oldItemIdInput','oldItemBadge','red')"
                        onfocus="showItemDrop('oldItemDrop','oldItemIdInput','oldItemBadge','red')"
                        onblur="setTimeout(()=>hideDrop('oldItemDrop'),200)">
                    <div class="search-drop" id="oldItemDrop"></div>
                </div>
                <div class="selected-badge" id="oldItemBadge"
                    style="background:#fef2f2;color:#dc2626;"></div>
            </div>
        </div>

        <!-- REPLACEMENT DEVICE -->
        <div class="wr-section" style="border-color:#86efac;">
            <div class="wr-section-title" style="color:#16a34a;">
                <i class="bi bi-check-circle"></i> Replacement Device (Given to Customer)
            </div>
            <div style="margin-bottom:14px;">
                <div class="wr-field">
                    <label>New IMEI (replacement device)</label>
                    <div class="search-wrap">
                        <input type="text" name="new_imei" id="newImeiInput"
                            placeholder="Scan or type new IMEI..."
                            class="search-input"
                            autocomplete="off"
                            oninput="searchInStockImei(this.value)"
                            onblur="setTimeout(()=>hideDrop('newImeiDrop'),200)"
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
                        oninput="filterItems(this.value,'newItemDrop','newItemIdInput','newItemBadge','green')"
                        onfocus="showItemDrop('newItemDrop','newItemIdInput','newItemBadge','green')"
                        onblur="setTimeout(()=>hideDrop('newItemDrop'),200)">
                    <div class="search-drop" id="newItemDrop"></div>
                </div>
                <div class="selected-badge" id="newItemBadge"
                    style="background:#f0fdf4;color:#15803d;"></div>
            </div>
        </div>

        <!-- FAULT + NOTES -->
        <div class="grid-2">
            <div class="wr-field">
                <label>Fault Description *</label>
                <textarea name="fault_description" rows="3"
                    placeholder="e.g. Dead screen, battery not charging, speaker issue..."></textarea>
            </div>
            <div class="wr-field">
                <label>Internal Notes <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                <textarea name="notes" rows="3" placeholder="Any internal notes..."></textarea>
            </div>
        </div>

    </div>

    <!-- SAVE BAR -->
    <div class="save-bar">
        <a href="?page=warranty"
           style="padding:8px 20px;border-radius:8px;font-size:0.88rem;border:1.5px solid #e5e7eb;color:#64748b;background:#fff;text-decoration:none;display:inline-flex;align-items:center;">
            Cancel
        </a>
        <button type="submit" class="btn-save" id="wrSaveBtn" disabled>
            <i class="bi bi-shield-check"></i> Save Warranty Replacement
        </button>
    </div>

</div>
</form>

<script>
// ── Data from PHP ─────────────────────────────────────────────────────────────
const ALL_CUSTOMERS = <?= json_encode($customers) ?>;
const ALL_ITEMS     = <?= json_encode($items) ?>;
const searchTimers  = {};

// ── Generic helpers ───────────────────────────────────────────────────────────
function hideDrop(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function renderDrop(dropId, html) {
    const el = document.getElementById(dropId);
    el.innerHTML = html;
    el.style.display = html ? 'block' : 'none';
}

// ── Customer searchable dropdown ──────────────────────────────────────────────
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
            onmousedown="selectCustomer(${c.id},'${c.name.replace(/'/g,"\\'")}','${(c.phone||'').replace(/'/g,"\\'")}')">
            <strong>${c.name}</strong>
            ${c.phone ? `<small style="color:#94a3b8;float:right;">${c.phone}</small>` : ''}
        </div>`).join(''));
}

function selectCustomer(id, name, phone) {
    document.getElementById('partyIdInput').value = id;
    document.getElementById('customerSearch').value = name;
    hideDrop('customerDrop');
    const badge = document.getElementById('customerBadge');
    badge.style.display = 'block';
    badge.innerHTML = `<i class="bi bi-person-check-fill me-1"></i> ${name}${phone ? ' · ' + phone : ''}`;
    checkSave();
}

// ── Old IMEI lookup ───────────────────────────────────────────────────────────
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
                const statusMap = {sold:'Sold', in_stock:'In Stock', defective:'⚠️ Already Defective', returned:'Returned'};
                box.className = 'imei-result found';
                box.innerHTML = `<i class="bi bi-check-circle me-1"></i>
                    <strong>${data.item_name}</strong>${data.sku ? ` (${data.sku})` : ''} ·
                    Status: <strong>${statusMap[data.status]||data.status}</strong>
                    ${data.sale_invoice_no ? ` · Sale: <strong>${data.sale_invoice_no}</strong>` : ''}
                    ${data.customer_name   ? ` · Customer: <strong>${data.customer_name}</strong>` : ''}`;

                // Auto-fill old item
                setItem('oldItemSearch','oldItemDrop','oldItemIdInput','oldItemBadge','red', data.item_id, data.item_name);

                // Auto-fill new item with same model if not already set
                if (!document.getElementById('newItemIdInput').value) {
                    setItem('newItemSearch','newItemDrop','newItemIdInput','newItemBadge','green', data.item_id, data.item_name);
                }

                // Auto-fill customer
                if (data.customer_id && !document.getElementById('partyIdInput').value) {
                    selectCustomer(data.customer_id, data.customer_name, '');
                }
            });
    }, 400);
}

// ── New IMEI search (in-stock only) ──────────────────────────────────────────
function searchInStockImei(q) {
    clearTimeout(searchTimers.newImei);
    const box = document.getElementById('newImeiResult');
    if (q.length < 3) { hideDrop('newImeiDrop'); box.style.display='none'; return; }
    const whId = document.getElementById('whSelect').value;
    searchTimers.newImei = setTimeout(() => {
        fetch(`?page=warranty&action=searchNewImei&q=${encodeURIComponent(q)}&warehouse_id=${whId}`)
            .then(r => r.json()).then(rows => {
                if (!rows.length) { hideDrop('newImeiDrop'); return; }
                renderDrop('newImeiDrop', rows.map(row => `
                    <div class="search-drop-item"
                        onmousedown="selectNewImei('${row.imei}','${row.item_name.replace(/'/g,"\\'")}',${row.item_id})">
                        <code style="font-size:0.8rem;color:#16a34a;">${row.imei}</code>
                        <span style="color:#475569;"> — ${row.item_name}</span>
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
    box.innerHTML = `<i class="bi bi-check-circle me-1"></i> <strong>${imei}</strong> — ${itemName} · In Stock`;
    setItem('newItemSearch','newItemDrop','newItemIdInput','newItemBadge','green', itemId, itemName);
}

// ── Items searchable dropdown ─────────────────────────────────────────────────
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
            onmousedown="setItem('${dropId==='oldItemDrop'?'oldItemSearch':'newItemSearch'}','${dropId}','${hiddenId}','${badgeId}','${color}',${item.id},'${item.name.replace(/'/g,"\\'")}')">
            <strong>${item.name}</strong>
            ${item.sku ? `<small style="color:#94a3b8;"> · ${item.sku}</small>` : ''}
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
    badge.innerHTML = `<i class="bi bi-box-seam me-1"></i> ${itemName}`;
    checkSave();
}

// ── Save guard ────────────────────────────────────────────────────────────────
function checkSave() {
    const ok = document.getElementById('partyIdInput').value &&
               document.getElementById('oldItemIdInput').value &&
               document.getElementById('newItemIdInput').value;
    document.getElementById('wrSaveBtn').disabled = !ok;
}
</script>
