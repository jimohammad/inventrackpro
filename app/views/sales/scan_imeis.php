<style>
.si-page { max-width: 720px; margin: 0 auto; }
.si-head { display:flex;align-items:center;gap:14px;margin-bottom:18px; }
.si-back { width:34px;height:34px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none; }
.si-back:hover { border-color:var(--primary);color:var(--primary); }
.si-head h1 { font-size:1.18rem;font-weight:700;margin:0; }

.si-info { background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fcd34d;border-radius:10px;padding:14px 18px;margin-bottom:14px; }
.si-info-title { font-size:.78rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px; }
.si-info-meta { display:flex;gap:18px;flex-wrap:wrap;font-size:.85rem;color:#78350f; }
.si-info-meta b { color:#451a03; }

.si-progress { background:var(--bg-card);border:1px solid var(--border-color);border-radius:10px;padding:14px 18px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center; }
.si-progress-num { font-size:1.4rem;font-weight:800;font-family:monospace; }
.si-progress-num .have  { color:#22c55e; }
.si-progress-num .need  { color:#94a3b8; }
.si-progress-num .left  { color:#f59e0b; }
.si-progress-bar { height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;flex:1;margin:0 18px;max-width:300px; }
.si-progress-fill { height:6px;background:linear-gradient(90deg,#22c55e,#16a34a);transition:width .3s ease; }

.si-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:18px;margin-bottom:14px; }
.si-sec { font-size:.72rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px; }

.si-existing { background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:10px 14px;margin-bottom:14px; }
.si-existing-label { font-size:.7rem;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px; }
.si-existing-tags { display:flex;flex-wrap:wrap;gap:5px; }
.si-existing-tag { background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:6px;font-size:.78rem;font-family:monospace;font-weight:600; }

.si-scan-input {
    width:100%;padding:13px 16px;border:2px solid var(--primary);border-radius:10px;
    font-size:1.1rem;font-family:monospace;letter-spacing:1px;background:var(--bg-main);
    color:var(--text-main);outline:none;
}
.si-scan-input:focus { box-shadow:0 0 0 4px rgba(99,102,241,.15); }
.si-scan-row { display:flex;gap:8px;align-items:center;margin-bottom:8px; }
.si-paste-btn { background:rgba(99,102,241,.12);color:var(--primary);border:none;border-radius:8px;padding:10px 14px;cursor:pointer;font-size:.82rem;font-weight:600;white-space:nowrap; }

.si-msg { padding:6px 12px;border-radius:8px;font-size:.82rem;min-height:28px;margin-bottom:8px; }
.si-msg.ok  { background:#d1fae5;color:#065f46; }
.si-msg.err { background:#fee2e2;color:#991b1b; }

.si-tag-list { display:flex;flex-wrap:wrap;gap:5px;min-height:50px;padding:8px;background:var(--bg-main);border-radius:8px;margin-top:8px; }
.si-tag { background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;padding:4px 10px;border-radius:6px;font-size:.78rem;font-family:monospace;display:inline-flex;align-items:center;gap:6px; }
.si-tag .x { cursor:pointer;color:#94a3b8;font-weight:700; }
.si-tag .x:hover { color:#ef4444; }

.si-paste-area { display:none;margin-top:10px;background:var(--bg-main);border-radius:8px;padding:12px; }
.si-paste-area textarea { width:100%;border:1.5px solid var(--border-color);border-radius:6px;padding:8px;font-family:monospace;font-size:.82rem;background:#fff;color:#1e293b;outline:none;resize:vertical; }

.si-foot { display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1.5px solid var(--border-color); }
.si-warn { font-size:.78rem;font-weight:600;color:#dc2626; }
.si-warn-ok { font-size:.78rem;font-weight:600;color:#16a34a; }
.si-btn-cancel { padding:9px 18px;background:transparent;border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:8px;text-decoration:none;font-size:.85rem; }
.si-btn-save { padding:9px 22px;background:linear-gradient(135deg,var(--primary),#4f46e5);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(99,102,241,.3); }
.si-btn-save:disabled { opacity:.5;cursor:not-allowed; }
</style>

<?php
$qty       = (int)$line['quantity'];
$existing  = count($existingImeis);
$remaining = max(0, $qty - $existing);
$pct       = $qty > 0 ? round(($existing / $qty) * 100) : 0;
?>

<div class="si-page">
    <div class="si-head">
        <a href="?page=sales&action=edit&id=<?= $sale['id'] ?>" class="si-back"><i class="bi bi-arrow-left"></i></a>
        <h1><i class="bi bi-upc-scan me-2" style="color:var(--primary);"></i>Scan IMEIs — <?= htmlspecialchars($sale['invoice_no']) ?></h1>
    </div>

    <div class="si-info">
        <div class="si-info-title"><i class="bi bi-exclamation-triangle me-1"></i> Retroactive IMEI Scan</div>
        <div class="si-info-meta">
            <span><b>Item:</b> <?= htmlspecialchars($line['item_name']) ?></span>
            <?php if ($line['sku']): ?><span><b>SKU:</b> <?= htmlspecialchars($line['sku']) ?></span><?php endif; ?>
            <span><b>Qty:</b> <?= $qty ?></span>
            <span><b>Price:</b> <?= APP_CURRENCY ?> <?= number_format($line['unit_price'], DECIMAL_PLACES) ?></span>
        </div>
    </div>

    <div class="si-progress">
        <div class="si-progress-num">
            <span class="have"><?= $existing ?></span><span class="need"> / <?= $qty ?></span>
            <div style="font-size:.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;">IMEIs Linked</div>
        </div>
        <div class="si-progress-bar"><div class="si-progress-fill" id="siProgressFill" style="width:<?= $pct ?>%;"></div></div>
        <div class="si-progress-num" style="text-align:right;">
            <span class="left" id="siRemainNum"><?= $remaining ?></span>
            <div style="font-size:.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;">Remaining</div>
        </div>
    </div>

    <?php if (!empty($existingImeis)): ?>
    <div class="si-existing">
        <div class="si-existing-label"><i class="bi bi-check-circle-fill me-1"></i> Already Linked (<?= count($existingImeis) ?>)</div>
        <div class="si-existing-tags">
            <?php foreach ($existingImeis as $ei): ?>
            <span class="si-existing-tag"><?= htmlspecialchars($ei['imei']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="?page=sales&action=scanItemImeisStore" id="siForm">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="id" value="<?= $sale['id'] ?>">
        <input type="hidden" name="sale_item_id" value="<?= $line['id'] ?>">
        <input type="hidden" name="imeis" id="siImeisField" value="">

        <div class="si-card">
            <div class="si-sec"><i class="bi bi-upc-scan"></i> Scan IMEIs Now</div>

            <?php if ($remaining === 0): ?>
            <div style="text-align:center;padding:30px;color:#16a34a;font-weight:600;">
                <i class="bi bi-check-circle-fill" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                All IMEIs already linked for this line.
            </div>
            <?php else: ?>

            <div class="si-scan-row">
                <input type="text" class="si-scan-input" id="siInput"
                       placeholder="Scan IMEI on phone..." autocomplete="off"
                       oninput="siAuto()"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();siScan();}">
                <button type="button" class="si-paste-btn" onclick="siTogglePaste()"><i class="bi bi-clipboard-plus"></i> Paste</button>
            </div>
            <div class="si-msg" id="siMsg"></div>

            <div class="si-paste-area" id="siPasteBox">
                <textarea id="siPasteInput" rows="4" placeholder="Paste IMEIs — one per line"></textarea>
                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button type="button" onclick="siProcessPaste()" style="background:var(--primary);color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:.78rem;font-weight:600;cursor:pointer;">Import All</button>
                    <button type="button" onclick="siTogglePaste()" style="background:transparent;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;padding:6px 12px;font-size:.78rem;cursor:pointer;">Cancel</button>
                </div>
            </div>

            <div style="margin-top:10px;">
                <div style="font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;">
                    Scanned this session: <span id="siCount" style="color:var(--primary);">0</span>
                </div>
                <div class="si-tag-list" id="siTagList"></div>
            </div>

            <div class="si-foot" style="margin-top:14px;">
                <div id="siSummary" class="si-warn">Need <?= $remaining ?> more IMEI(s).</div>
                <div style="display:flex;gap:8px;">
                    <a href="?page=sales&action=edit&id=<?= $sale['id'] ?>" class="si-btn-cancel">Cancel</a>
                    <button type="submit" class="si-btn-save" id="siSubmit" disabled>
                        <i class="bi bi-check-lg"></i> Link IMEIs
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
var siRemaining = <?= $remaining ?>;
var siItemName  = '<?= addslashes(strtolower($line['item_name'])) ?>';
var siExisting  = <?= json_encode(array_column($existingImeis, 'imei')) ?>;
var siScanned   = [];
var _siAutoT    = null;

function siRule() {
    if (siItemName.indexOf('h40') !== -1) return { min: 13, max: 13, label: '13' };
    return { min: 15, max: 15, label: '15' };
}

function siAuto() {
    clearTimeout(_siAutoT);
    var v = document.getElementById('siInput').value.trim();
    var r = siRule();
    if (v.length >= r.min && v.length <= r.max && /^\d+$/.test(v)) {
        _siAutoT = setTimeout(siScan, 150);
    }
}

function siScan() {
    clearTimeout(_siAutoT);
    var input = document.getElementById('siInput');
    var imei  = input.value.trim();
    input.value = '';
    if (!imei) return;

    var r = siRule();
    if (!/^\d+$/.test(imei))                                 { siMsg('Not digits: ' + imei, 'err'); input.focus(); return; }
    if (imei.length < r.min || imei.length > r.max)          { siMsg('Length must be ' + r.label, 'err'); input.focus(); return; }
    if (siExisting.indexOf(imei) !== -1)                     { siMsg('Already linked', 'err'); input.focus(); return; }
    if (siScanned.indexOf(imei) !== -1)                      { siMsg('Already in scan list', 'err'); input.focus(); return; }
    if (siScanned.length >= siRemaining)                     { siMsg('Limit reached (' + siRemaining + ')', 'err'); input.focus(); return; }

    siScanned.push(imei);
    siRender();
    siMsg('✓ ' + imei, 'ok');
    input.focus();
}

function siTogglePaste() {
    var box = document.getElementById('siPasteBox');
    box.style.display = box.style.display === 'none' || !box.style.display ? 'block' : 'none';
    if (box.style.display === 'block') document.getElementById('siPasteInput').focus();
}

function siProcessPaste() {
    var raw = document.getElementById('siPasteInput').value;
    var list = raw.split(/[\n,;\s\t]+/).map(function(s) { return s.trim(); }).filter(Boolean);
    var r = siRule();
    var added = 0, skipped = 0, invalid = 0, full = false;
    list.forEach(function(im) {
        if (full) return;
        if (!/^\d+$/.test(im)) { invalid++; return; }
        if (im.length < r.min || im.length > r.max) { invalid++; return; }
        if (siExisting.indexOf(im) !== -1) { skipped++; return; }
        if (siScanned.indexOf(im) !== -1) { skipped++; return; }
        if (siScanned.length >= siRemaining) { full = true; return; }
        siScanned.push(im); added++;
    });
    siRender();
    siMsg('Added ' + added + ', skipped ' + skipped + ', invalid ' + invalid + (full ? ' (limit hit)' : ''), added > 0 ? 'ok' : 'err');
    document.getElementById('siPasteInput').value = '';
}

function siRender() {
    document.getElementById('siCount').textContent = siScanned.length;
    document.getElementById('siRemainNum').textContent = siRemaining - siScanned.length;
    var pct = ((siExisting.length + siScanned.length) / (siExisting.length + siRemaining)) * 100;
    document.getElementById('siProgressFill').style.width = pct + '%';

    document.getElementById('siTagList').innerHTML = siScanned.map(function(im, idx) {
        return '<span class="si-tag">' + im + ' <span class="x" onclick="siRemove(' + idx + ')">&times;</span></span>';
    }).join('');

    var sum = document.getElementById('siSummary');
    var btn = document.getElementById('siSubmit');
    if (siScanned.length === 0) {
        sum.className = 'si-warn';
        sum.textContent = 'Need ' + siRemaining + ' more IMEI(s).';
        btn.disabled = true;
    } else {
        sum.className = 'si-warn-ok';
        sum.textContent = siScanned.length + ' IMEI(s) ready to link. ' + (siRemaining - siScanned.length) + ' still needed after.';
        btn.disabled = false;
    }
    document.getElementById('siImeisField').value = siScanned.join('\n');
}

function siRemove(idx) { siScanned.splice(idx, 1); siRender(); }

function siMsg(t, type) {
    var el = document.getElementById('siMsg');
    el.className = 'si-msg ' + type;
    el.textContent = t;
    if (type === 'ok') setTimeout(function() { el.textContent = ''; el.className = 'si-msg'; }, 1500);
}

setTimeout(function() { var i = document.getElementById('siInput'); if (i) i.focus(); }, 200);
</script>
