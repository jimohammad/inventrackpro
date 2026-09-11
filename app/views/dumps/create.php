<?php
$nextNo  = $nextNo ?? 'DUMP-000001';
$dec     = (int) DECIMAL_PLACES;
?>
<style>
.dump-wrap{display:flex;flex-direction:column;gap:0;}
.dump-topbar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:#9a3412;border-radius:0;}
.dump-topbar .dump-title{font-size:1.05rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.dump-body{background:#fff;border:1px solid #e5e7eb;border-top:none;padding:20px 24px;}
.dump-field label{font-size:0.78rem;font-weight:600;color:#64748b;margin-bottom:4px;display:block;}
.dump-field input{width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:7px 11px;font-size:0.85rem;color:#1e293b;outline:none;background:#fafbff;}
.dump-field input:focus{border-color:#ea580c;background:#fff;}
.dump-field input.is-selected{background:#fff7ed;border-color:#fdba74;font-weight:600;}
.dump-search-wrap{position:relative;}
.dump-autocomplete{position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #fed7aa;border-radius:10px;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,0.12);max-height:240px;overflow-y:auto;margin-top:4px;display:none;}
.dump-autocomplete-item{padding:9px 14px;cursor:pointer;font-size:0.83rem;border-bottom:1px solid #fff7ed;color:#1e293b;}
.dump-autocomplete-item:last-child{border-bottom:none;}
.dump-autocomplete-item:hover,.dump-autocomplete-item.active{background:#fff7ed;}
.dump-autocomplete-item small{display:block;color:#94a3b8;font-size:0.75rem;font-weight:400;}
.dump-legacy{display:none;margin:10px 0 16px;padding:14px 16px;border:1.5px dashed #fdba74;border-radius:10px;background:#fff7ed;}
.dump-legacy h6{font-size:0.82rem;font-weight:700;color:#9a3412;margin:0 0 10px;}
.dump-legacy-grid{display:grid;grid-template-columns:1.4fr 0.8fr auto;gap:10px;align-items:end;}
.save-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-top:2px solid #fed7aa;border-radius:0 0 12px 12px;}
.btn-dump-save{padding:8px 28px;border-radius:8px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#ea580c,#c2410c);border:none;color:#fff;cursor:pointer;}
.btn-dump-save:disabled{opacity:0.5;cursor:not-allowed;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
@media(max-width:700px){.grid-3,.dump-legacy-grid{grid-template-columns:1fr;}}
</style>

<form method="POST" action="?page=dumps&action=store" id="dumpForm">
<?= Auth::csrfField() ?>
<input type="hidden" name="party_id" id="partyIdInput" required>

<div class="dump-wrap">
    <div class="dump-topbar">
        <div class="dump-title"><i class="bi bi-recycle"></i> New Dump Credit</div>
        <a href="?page=dumps" style="color:#fff;font-size:0.85rem;text-decoration:none;">Cancel</a>
    </div>
    <div class="dump-body">
        <p class="small text-muted mb-3">
            Type the <strong>party whose account is credited</strong>, then pick from the list.
            Scan an in-app IMEI for sold price, or add an <strong>older device not in the app</strong> (before April 2026).
            The phone is not restocked.
        </p>
        <div class="grid-3" style="margin-bottom:18px;">
            <div class="dump-field">
                <label>Dump No</label>
                <input type="text" value="<?= htmlspecialchars($nextNo) ?>" readonly style="background:#fff7ed;color:#c2410c;font-weight:700;">
            </div>
            <div class="dump-field">
                <label>Date</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="dump-field">
                <label>Party (account to credit)</label>
                <div class="dump-search-wrap" id="partySearchWrap">
                    <input type="text" id="partySearch" placeholder="Type to search party…" autocomplete="off">
                    <div class="dump-autocomplete" id="partyDropdown"></div>
                </div>
            </div>
        </div>
        <div class="dump-field mb-2">
            <label>Scan IMEI / serial</label>
            <input type="text" id="imeiScan" placeholder="Scan or paste, then Enter" autocomplete="off">
            <div class="d-flex justify-content-between align-items-center mt-1">
                <div id="imeiMsg" class="small" style="display:none;"></div>
                <button type="button" class="btn btn-link btn-sm p-0" id="legacyOpenBtn" style="font-size:0.78rem;color:#c2410c;">
                    IMEI not in app?
                </button>
            </div>
        </div>
        <div class="dump-legacy" id="legacyPanel">
            <h6>Older device — not in the app</h6>
            <p class="small text-muted mb-2 mb-md-3">
                IMEI <code id="legacyImeiLabel"></code> was never recorded here (sales started April 2026).
                Pick the model and the amount to credit the party.
            </p>
            <div class="dump-legacy-grid">
                <div class="dump-field">
                    <label>Model</label>
                    <div class="dump-search-wrap" id="itemSearchWrap">
                        <input type="text" id="legacyItemSearch" placeholder="Type to search item…" autocomplete="off">
                        <div class="dump-autocomplete" id="itemDropdown"></div>
                    </div>
                </div>
                <div class="dump-field">
                    <label>Credit amount</label>
                    <input type="number" id="legacyPrice" step="0.001" min="0.001" placeholder="0.000">
                </div>
                <div class="dump-field">
                    <button type="button" class="btn btn-sm" id="legacyAddBtn"
                            style="background:#c2410c;color:#fff;font-weight:700;padding:7px 14px;">
                        Add
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="legacyCancelBtn">Cancel</button>
                </div>
            </div>
        </div>
        <table class="table table-sm" id="dumpTable" style="font-size:0.85rem;">
            <thead>
                <tr>
                    <th>IMEI</th>
                    <th>Item</th>
                    <th>Invoice</th>
                    <th>Warranty</th>
                    <th class="text-end">Credit</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="dumpRows">
                <tr id="dumpEmpty"><td colspan="6" class="text-center text-muted py-3">No units scanned yet.</td></tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end fw-semibold">Credit total</td>
                    <td class="text-end fw-bold" id="dumpTotal"><?= APP_CURRENCY ?> 0.000</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="save-bar">
        <button type="submit" class="btn-dump-save" id="dumpSave" disabled>
            <i class="bi bi-check-lg me-1"></i> Save dump credit
        </button>
    </div>
</div>
</form>

<script>
(function () {
    const DEC = <?= $dec ?>;
    const partyIdInput = document.getElementById('partyIdInput');
    const partySearch = document.getElementById('partySearch');
    const partyDrop = document.getElementById('partyDropdown');
    const imeiScan = document.getElementById('imeiScan');
    const imeiMsg = document.getElementById('imeiMsg');
    const dumpRows = document.getElementById('dumpRows');
    const dumpEmpty = document.getElementById('dumpEmpty');
    const dumpSave = document.getElementById('dumpSave');
    const dumpTotal = document.getElementById('dumpTotal');
    const legacyPanel = document.getElementById('legacyPanel');
    const legacyImeiLabel = document.getElementById('legacyImeiLabel');
    const legacyItemSearch = document.getElementById('legacyItemSearch');
    const itemDrop = document.getElementById('itemDropdown');
    const legacyPrice = document.getElementById('legacyPrice');
    const seen = {};
    let rows = [];
    let partyResults = [];
    let partyHighlight = -1;
    let partyTimer = null;
    let partyAbort = null;
    let selectedPartyName = '';
    let itemResults = [];
    let itemHighlight = -1;
    let itemTimer = null;
    let itemAbort = null;
    let legacyImei = '';
    let legacyItemId = 0;
    let legacyItemName = '';

    function he(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function partyChosen() {
        return parseInt(partyIdInput.value, 10) > 0;
    }

    function syncSave() {
        dumpSave.disabled = !(partyChosen() && rows.length);
    }

    function markPartySelected(on) {
        partySearch.classList.toggle('is-selected', !!on);
    }

    function clearParty() {
        partyIdInput.value = '';
        selectedPartyName = '';
        markPartySelected(false);
        syncSave();
    }

    function selectParty(p) {
        if (!p) return;
        partyIdInput.value = String(p.id);
        selectedPartyName = p.name || '';
        partySearch.value = selectedPartyName;
        markPartySelected(true);
        partyDrop.style.display = 'none';
        partyHighlight = -1;
        syncSave();
        imeiScan.focus();
    }

    function updateHighlight(drop, cls, idx) {
        drop.querySelectorAll('.' + cls).forEach(function (el) {
            el.classList.toggle('active', parseInt(el.getAttribute('data-idx'), 10) === idx);
        });
        const active = drop.querySelector('.' + cls + '.active');
        if (active) active.scrollIntoView({ block: 'nearest' });
    }

    function renderPartyDrop(parties) {
        partyResults = Array.isArray(parties) ? parties : [];
        partyHighlight = partyResults.length ? 0 : -1;
        if (!partyResults.length) {
            partyDrop.style.display = 'none';
            partyDrop.innerHTML = '';
            return;
        }
        partyDrop.innerHTML = partyResults.map(function (p, idx) {
            const phone = p.phone ? '<small>' + he(p.phone) + '</small>' : '';
            return '<div class="dump-autocomplete-item" data-idx="' + idx + '"><strong>' + he(p.name) + '</strong>' + phone + '</div>';
        }).join('');
        partyDrop.querySelectorAll('.dump-autocomplete-item').forEach(function (el) {
            el.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectParty(partyResults[parseInt(el.getAttribute('data-idx'), 10)]);
            });
            el.addEventListener('mouseenter', function () {
                partyHighlight = parseInt(el.getAttribute('data-idx'), 10);
                updateHighlight(partyDrop, 'dump-autocomplete-item', partyHighlight);
            });
        });
        partyDrop.style.display = 'block';
        updateHighlight(partyDrop, 'dump-autocomplete-item', partyHighlight);
    }

    function runPartySearch() {
        const q = partySearch.value.trim();
        if (q.length < 1) {
            partyDrop.style.display = 'none';
            return;
        }
        if (partyAbort) partyAbort.abort();
        partyAbort = new AbortController();
        fetch('?page=dumps&action=searchParties&q=' + encodeURIComponent(q), { signal: partyAbort.signal })
            .then(function (r) { return r.json(); })
            .then(function (parties) {
                if (partySearch.value.trim() !== q) return;
                renderPartyDrop(parties);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                partyDrop.style.display = 'none';
            });
    }

    partySearch.addEventListener('input', function () {
        if (partyChosen() && this.value !== selectedPartyName) {
            clearParty();
        }
        clearTimeout(partyTimer);
        partyTimer = setTimeout(runPartySearch, 250);
    });

    partySearch.addEventListener('keydown', function (e) {
        const open = partyDrop.style.display === 'block' && partyResults.length;
        if (e.key === 'ArrowDown' && open) {
            e.preventDefault();
            partyHighlight = Math.min(partyHighlight + 1, partyResults.length - 1);
            updateHighlight(partyDrop, 'dump-autocomplete-item', partyHighlight);
            return;
        }
        if (e.key === 'ArrowUp' && open) {
            e.preventDefault();
            partyHighlight = Math.max(partyHighlight - 1, 0);
            updateHighlight(partyDrop, 'dump-autocomplete-item', partyHighlight);
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            if (open && partyHighlight >= 0) {
                selectParty(partyResults[partyHighlight]);
            }
            return;
        }
        if (e.key === 'Escape') {
            partyDrop.style.display = 'none';
        }
    });

    function hideLegacy() {
        legacyPanel.style.display = 'none';
        legacyImei = '';
        legacyItemId = 0;
        legacyItemName = '';
        legacyItemSearch.value = '';
        legacyPrice.value = '';
        itemDrop.style.display = 'none';
    }

    function showLegacy(imei) {
        legacyImei = imei;
        legacyImeiLabel.textContent = imei;
        legacyItemId = 0;
        legacyItemName = '';
        legacyItemSearch.value = '';
        legacyPrice.value = '';
        legacyPanel.style.display = 'block';
        setTimeout(function () { legacyItemSearch.focus(); }, 30);
    }

    function selectItem(it) {
        if (!it) return;
        legacyItemId = parseInt(it.id, 10) || 0;
        legacyItemName = it.name || '';
        legacyItemSearch.value = legacyItemName;
        itemDrop.style.display = 'none';
        if (it.sale_price && !legacyPrice.value) {
            legacyPrice.value = Number(it.sale_price).toFixed(DEC);
        }
        legacyPrice.focus();
        try { legacyPrice.select(); } catch (e) { /* ignore */ }
    }

    function renderItemDrop(items) {
        itemResults = Array.isArray(items) ? items : [];
        itemHighlight = itemResults.length ? 0 : -1;
        if (!itemResults.length) {
            itemDrop.style.display = 'none';
            itemDrop.innerHTML = '';
            return;
        }
        itemDrop.innerHTML = itemResults.map(function (it, idx) {
            const price = it.sale_price != null ? '<small><?= APP_CURRENCY ?> ' + he(Number(it.sale_price).toFixed(DEC)) + '</small>' : '';
            return '<div class="dump-autocomplete-item" data-idx="' + idx + '"><strong>' + he(it.name) + '</strong>' + price + '</div>';
        }).join('');
        itemDrop.querySelectorAll('.dump-autocomplete-item').forEach(function (el) {
            el.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectItem(itemResults[parseInt(el.getAttribute('data-idx'), 10)]);
            });
            el.addEventListener('mouseenter', function () {
                itemHighlight = parseInt(el.getAttribute('data-idx'), 10);
                updateHighlight(itemDrop, 'dump-autocomplete-item', itemHighlight);
            });
        });
        itemDrop.style.display = 'block';
        updateHighlight(itemDrop, 'dump-autocomplete-item', itemHighlight);
    }

    function runItemSearch() {
        const q = legacyItemSearch.value.trim();
        if (q.length < 1) {
            itemDrop.style.display = 'none';
            return;
        }
        if (itemAbort) itemAbort.abort();
        itemAbort = new AbortController();
        fetch('?page=dumps&action=searchItems&q=' + encodeURIComponent(q), { signal: itemAbort.signal })
            .then(function (r) { return r.json(); })
            .then(function (items) {
                if (legacyItemSearch.value.trim() !== q) return;
                renderItemDrop(items);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                itemDrop.style.display = 'none';
            });
    }

    legacyItemSearch.addEventListener('input', function () {
        if (legacyItemId && this.value !== legacyItemName) {
            legacyItemId = 0;
            legacyItemName = '';
        }
        clearTimeout(itemTimer);
        itemTimer = setTimeout(runItemSearch, 250);
    });

    legacyItemSearch.addEventListener('keydown', function (e) {
        const open = itemDrop.style.display === 'block' && itemResults.length;
        if (e.key === 'ArrowDown' && open) {
            e.preventDefault();
            itemHighlight = Math.min(itemHighlight + 1, itemResults.length - 1);
            updateHighlight(itemDrop, 'dump-autocomplete-item', itemHighlight);
            return;
        }
        if (e.key === 'ArrowUp' && open) {
            e.preventDefault();
            itemHighlight = Math.max(itemHighlight - 1, 0);
            updateHighlight(itemDrop, 'dump-autocomplete-item', itemHighlight);
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            if (open && itemHighlight >= 0) {
                selectItem(itemResults[itemHighlight]);
            }
            return;
        }
        if (e.key === 'Escape') {
            itemDrop.style.display = 'none';
        }
    });

    function addLegacyLine() {
        const price = parseFloat(legacyPrice.value);
        if (!legacyImei) {
            showMsg(false, 'Scan or type the IMEI first.');
            return;
        }
        if (!legacyItemId) {
            showMsg(false, 'Pick the model.');
            legacyItemSearch.focus();
            return;
        }
        if (!(price > 0)) {
            showMsg(false, 'Enter the credit amount.');
            legacyPrice.focus();
            return;
        }
        if (seen[legacyImei]) {
            showMsg(false, 'Already on this dump.');
            return;
        }
        seen[legacyImei] = true;
        rows.push({
            imei: legacyImei,
            item_id: legacyItemId,
            item_name: legacyItemName,
            unit_price: price.toFixed(DEC),
            sold_invoice: '',
            warranty_in: false,
            warranty_label: 'Not in app',
            legacy: true
        });
        showMsg(true, 'Older device added · credit <?= APP_CURRENCY ?> ' + price.toFixed(DEC));
        hideLegacy();
        render();
        imeiScan.focus();
    }

    document.getElementById('legacyAddBtn').addEventListener('click', addLegacyLine);
    document.getElementById('legacyCancelBtn').addEventListener('click', hideLegacy);
    legacyPrice.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addLegacyLine();
        }
    });
    document.getElementById('legacyOpenBtn').addEventListener('click', function () {
        const raw = imeiScan.value.trim();
        if (raw) {
            addImei(raw);
            imeiScan.value = '';
            return;
        }
        showMsg(false, 'Type or scan the older IMEI, then click “IMEI not in app?” again — or press Enter after scanning.');
        imeiScan.focus();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#partySearchWrap')) {
            partyDrop.style.display = 'none';
        }
        if (!e.target.closest('#itemSearchWrap')) {
            itemDrop.style.display = 'none';
        }
    });

    function render() {
        dumpRows.querySelectorAll('tr.dump-line').forEach(function (tr) { tr.remove(); });
        dumpEmpty.style.display = rows.length ? 'none' : '';
        let total = 0;
        rows.forEach(function (row, idx) {
            total += parseFloat(row.unit_price) || 0;
            const inW = !!row.warranty_in;
            const wLabel = row.warranty_label || '—';
            const wColor = row.legacy ? '#9a3412' : (inW ? '#15803d' : '#b45309');
            const invoice = row.legacy ? 'Not in app' : (row.sold_invoice || '—');
            const tr = document.createElement('tr');
            tr.className = 'dump-line';
            tr.innerHTML =
                '<td>' +
                    '<input type="hidden" name="lines[' + idx + '][imei]" value="' + he(row.imei) + '">' +
                    '<input type="hidden" name="lines[' + idx + '][legacy]" value="' + (row.legacy ? '1' : '0') + '">' +
                    (row.legacy
                        ? '<input type="hidden" name="lines[' + idx + '][item_id]" value="' + he(row.item_id) + '">' +
                          '<input type="hidden" name="lines[' + idx + '][unit_price]" value="' + he(row.unit_price) + '">'
                        : '') +
                    '<code>' + he(row.imei) + '</code>' +
                '</td>' +
                '<td>' + he(row.item_name) + '</td>' +
                '<td>' + he(invoice) + '</td>' +
                '<td style="color:' + wColor + ';font-weight:600;">' + he(wLabel) + '</td>' +
                '<td class="text-end"><?= APP_CURRENCY ?> ' + he(row.unit_price) + '</td>' +
                '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-idx="' + idx + '">&times;</button></td>';
            dumpRows.appendChild(tr);
        });
        dumpTotal.textContent = '<?= APP_CURRENCY ?> ' + total.toFixed(DEC);
        dumpRows.querySelectorAll('button[data-idx]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const i = parseInt(btn.getAttribute('data-idx'), 10);
                const gone = rows.splice(i, 1)[0];
                if (gone) delete seen[gone.imei];
                render();
                syncSave();
            });
        });
        syncSave();
    }

    function showMsg(ok, text) {
        imeiMsg.style.display = 'block';
        imeiMsg.style.color = ok ? '#15803d' : '#dc2626';
        imeiMsg.textContent = text;
    }

    function addImei(imei) {
        imei = String(imei || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (!imei) return;
        if (seen[imei]) { showMsg(false, 'Already on this dump.'); return; }
        hideLegacy();
        showMsg(true, 'Checking…');
        fetch('?page=dumps&action=lookupImei&imei=' + encodeURIComponent(imei))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.not_in_app) {
                    showMsg(false, data.message || 'Not in the app.');
                    showLegacy(data.imei || imei);
                    return;
                }
                if (!data || !data.ok) {
                    showMsg(false, (data && data.message) ? data.message : 'Not found.');
                    return;
                }
                if (seen[data.imei]) { showMsg(false, 'Already on this dump.'); return; }
                seen[data.imei] = true;
                data.legacy = false;
                rows.push(data);
                showMsg(true, data.message || 'Added.');
                render();
            })
            .catch(function () { showMsg(false, 'Lookup failed.'); });
    }

    imeiScan.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const raw = this.value;
        this.value = '';
        raw.split(/[\s,;]+/).forEach(addImei);
    });

    document.getElementById('dumpForm').addEventListener('submit', function (e) {
        if (!partyChosen() || !rows.length) {
            e.preventDefault();
            showMsg(false, 'Select a party and add at least one IMEI.');
            if (!partyChosen()) partySearch.focus();
        }
    });
})();
</script>
