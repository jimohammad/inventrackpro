<style>
.ai-page { max-width: 760px; }
.ai-head { display:flex;align-items:center;gap:12px;margin-bottom:18px; }
.ai-back { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none; }
.ai-back:hover { border-color:var(--primary);color:var(--primary); }
.ai-head h1 { font-size:1.15rem;font-weight:700;margin:0; }

.ai-meta { display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px; }
.ai-meta-card { background:var(--bg-card);border:1.5px solid var(--border-color);border-radius:10px;padding:12px 14px;text-align:center; }
.ai-meta-card .label { font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;font-weight:700; }
.ai-meta-card .value { font-size:1.6rem;font-weight:800;font-family:monospace;margin-top:2px; }
.ai-meta-card.stock .value { color:#3b82f6; }
.ai-meta-card.imei  .value { color:#7c3aed; }
.ai-meta-card.scan  .value { color:#16a34a; }

.ai-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;padding:18px;margin-bottom:16px; }
.ai-sep { font-size:.72rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;display:flex;align-items:center;gap:6px; }

.ai-scan { display:flex;gap:8px;margin-bottom:8px;align-items:stretch; }
.ai-scan input { flex:1;padding:11px 14px;border:2px solid var(--primary);border-radius:10px;font-size:1rem;font-family:monospace;letter-spacing:1px;background:var(--bg-main);color:var(--text-main);outline:none;min-width:0; }
.ai-scan input:focus { box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.ai-btn-paste { flex-shrink:0;padding:0 14px;border-radius:10px;border:1.5px solid #0ea5e9;background:#fff;color:#0ea5e9;font-size:.82rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:5px;white-space:nowrap; }
.ai-btn-paste:hover { background:#0ea5e9;color:#fff; }
@media (max-width: 520px) {
    .ai-scan { flex-direction:column; }
    .ai-btn-paste { width:100%;height:44px; }
}
.ai-msg { padding:5px 10px;border-radius:6px;font-size:.78rem;min-height:24px;margin-bottom:6px; }
.ai-msg.ok  { background:#d1fae5;color:#065f46; }
.ai-msg.err { background:#fee2e2;color:#991b1b; }

.ai-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;max-height:340px;overflow-y:auto;padding:6px;background:var(--bg-main);border-radius:8px; }
.ai-row { display:flex;justify-content:space-between;align-items:center;padding:7px 10px;border-radius:6px;font-size:.82rem;background:var(--bg-card);border:1px solid var(--border-color); }
.ai-row.scanned { background:#dcfce7;border-color:#86efac; }
.ai-row .imei { font-family:monospace;color:var(--text-main); }
.ai-row .icon { font-size:.85rem; }
.ai-row.scanned .icon { color:#16a34a; }
.ai-row .icon.miss { color:#94a3b8; }

.ai-actions { display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:14px;border-top:1.5px solid var(--border-color); }
.ai-warn { font-size:.78rem;color:#dc2626;font-weight:600; }
.ai-warn-ok { font-size:.78rem;color:#16a34a;font-weight:600; }
.ai-btn-cancel { padding:9px 18px;background:transparent;border:1.5px solid var(--border-color);color:var(--text-muted);border-radius:8px;text-decoration:none;font-size:.85rem; }
.ai-btn-submit { padding:9px 22px;background:linear-gradient(135deg,var(--primary),#4f46e5);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 6px rgba(99,102,241,.3); }
.ai-btn-submit:disabled { opacity:.5;cursor:not-allowed; }

/* Paste modal */
.ai-pm-textarea { width:100%;padding:12px;border:1.5px solid #e0e7ff;border-radius:8px;font-family:ui-monospace,monospace;font-size:.85rem;resize:vertical;background:#fafbff;color:#1e293b;outline:none;min-height:200px;letter-spacing:.5px; }
.ai-pm-textarea:focus { border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.ai-pm-summary { display:flex;gap:14px;margin-bottom:10px;font-size:.85rem;font-weight:700;flex-wrap:wrap; }
.ai-pm-summary span { display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px; }
.ai-pm-ok { color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0; }
.ai-pm-err { color:#dc2626;background:#fef2f2;border:1px solid #fecaca; }
.ai-pm-dup { color:#b45309;background:#fffbeb;border:1px solid #fde68a; }
.ai-pm-err-list { background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;max-height:160px;overflow-y:auto; }
.ai-pm-err-header { font-size:.75rem;font-weight:700;color:#991b1b;margin-bottom:6px; }
.ai-pm-err-row { display:flex;gap:12px;font-size:.78rem;padding:3px 0;border-bottom:1px solid #fecaca; }
.ai-pm-err-row:last-child { border-bottom:none; }
.ai-pm-err-imei { font-family:monospace;font-weight:600;color:#7f1d1d;width:160px;flex-shrink:0; }
.ai-pm-err-reason { color:#991b1b; }
</style>

<div class="ai-page">
    <div class="ai-head">
        <a href="?page=imei&action=audit" class="ai-back"><i class="bi bi-arrow-left"></i></a>
        <h1><i class="bi bi-tools" style="color:var(--primary);"></i> Reconcile: <?= htmlspecialchars($item['name']) ?></h1>
    </div>

    <div class="ai-meta">
        <div class="ai-meta-card stock"><div class="label">Stock Qty</div><div class="value"><?= (int)$item['stock'] ?></div></div>
        <div class="ai-meta-card imei"><div class="label">IMEI Count</div><div class="value" id="aiImeiCount"><?= (int)$item['imei_count'] ?></div></div>
        <div class="ai-meta-card scan"><div class="label">Scanned</div><div class="value" id="aiScanCount">0</div></div>
    </div>

    <div class="ai-card">
        <div class="ai-sep"><i class="bi bi-upc-scan"></i> Scan Physical IMEIs Present</div>
        <div class="ai-scan">
            <input type="text" id="aiInput" placeholder="Scan IMEI on phone..." autocomplete="off"
                   autocorrect="off" autocapitalize="off" spellcheck="false">
            <button type="button" class="ai-btn-paste" id="aiBtnPaste">
                <i class="bi bi-clipboard-plus"></i> Paste IMEIs
            </button>
        </div>
        <div class="ai-msg" id="aiMsg"></div>
    </div>

    <form method="POST" action="?page=imei&action=auditSubmit" id="aiForm">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
        <input type="hidden" name="scanned_imeis" id="aiScannedField" value="">

        <div class="ai-card">
            <div class="ai-sep"><i class="bi bi-list-ul"></i> Currently in Stock (<?= count($imeis) ?>)</div>
            <div class="ai-grid" id="aiList">
                <?php foreach ($imeis as $im): ?>
                <div class="ai-row" id="row_<?= htmlspecialchars($im['imei']) ?>">
                    <span class="imei"><?= htmlspecialchars($im['imei']) ?></span>
                    <i class="bi bi-circle icon miss"></i>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ai-actions">
                <div id="aiSummary" class="ai-warn">No IMEIs scanned yet.</div>
                <div style="display:flex;gap:8px;">
                    <a href="?page=imei&action=audit" class="ai-btn-cancel">Cancel</a>
                    <button type="submit" class="ai-btn-submit" id="aiSubmit" disabled>
                        <i class="bi bi-check-lg"></i> Mark Unscanned as Transferred
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Paste IMEIs modal -->
<div class="modal fade" id="aiPasteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-bottom:1px solid #bae6fd;">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700;display:flex;align-items:center;gap:8px;color:#0c4a6e;">
                    <i class="bi bi-clipboard-plus" style="color:#0ea5e9;"></i>
                    Paste IMEIs — Bulk Mark Present
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:.82rem;color:#64748b;margin-bottom:10px;">
                    <i class="bi bi-info-circle me-1" style="color:#0ea5e9;"></i>
                    Paste IMEIs below — one per line, or separated by commas/semicolons.
                    Matching in-stock serials will be marked scanned. Press <strong>Enter</strong> to validate, then <strong>Enter</strong> again to import.
                    <span style="color:#94a3b8;">Shift+Enter for a new line.</span>
                </p>
                <div style="font-size:.78rem;color:#475569;margin-bottom:8px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <span><i class="bi bi-box-seam me-1"></i> In stock list: <strong id="aiPmStockCount"><?= count($imeis) ?></strong></span>
                    <span><i class="bi bi-stickies me-1"></i> Lines pasted: <strong id="aiPmLineCount">0</strong></span>
                </div>
                <textarea id="aiPmTextarea" class="ai-pm-textarea"
                          placeholder="Paste IMEIs here (one per line)...&#10;&#10;352607621014220&#10;352607621014238"></textarea>
                <div id="aiPmPreview" style="margin-top:12px;display:none;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="aiPmValidateBtn"
                        style="background:#0ea5e9;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-check2-all me-1"></i> Validate
                </button>
                <button type="button" class="btn btn-sm" id="aiPmConfirmBtn" disabled
                        style="background:#6366f1;color:#fff;border:none;font-weight:600;">
                    <i class="bi bi-cloud-upload me-1"></i> Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var aiItemId = <?= (int)$item['id'] ?>;
var aiCsrf = '<?= Auth::csrfToken() ?>';
var aiInStock = <?= json_encode(array_column($imeis, 'imei')) ?>;
var aiScanned = [];
var _aiAutoT = null;
var aiPmValidList = [];
var aiPmValidated = false;
var aiPendingMissing = [];

function aiNormalizeImei(raw) {
    return String(raw || '').trim().toUpperCase().replace(/[^A-Z0-9\/\-]/g, '');
}

function aiParsePasteLines(raw) {
    return String(raw || '').split(/[\r\n,;]+/).map(function(s) {
        return aiNormalizeImei(s);
    }).filter(function(s) { return s.length > 0; });
}

function aiAuto() {
    clearTimeout(_aiAutoT);
    var v = document.getElementById('aiInput').value.trim();
    if (v.length >= 13 && /^[A-Z0-9\/\-]+$/i.test(v)) {
        _aiAutoT = setTimeout(aiScan, 150);
    }
}

function aiScan() {
    clearTimeout(_aiAutoT);
    var input = document.getElementById('aiInput');
    var imei  = aiNormalizeImei(input.value);
    input.value = '';
    if (!imei) return;
    if (aiScanned.indexOf(imei) !== -1) { aiMsg('Already scanned: ' + imei, 'err'); input.focus(); return; }

    fetch('?page=imei&action=auditScan&imei=' + encodeURIComponent(imei) + '&item_id=' + aiItemId)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok) {
                if (d.code === 'not_found') {
                    aiPromptRegister(imei);
                } else {
                    aiMsg(d.msg + ': ' + imei, 'err');
                    input.focus();
                }
                return;
            }
            aiAcceptScanned(imei);
            input.focus();
        })
        .catch(function() { aiMsg('Network error', 'err'); input.focus(); });
}

function aiBumpImeiCount(n) {
    var el = document.getElementById('aiImeiCount');
    if (!el) return;
    el.textContent = String(parseInt(el.textContent, 10) + (n || 1));
}

function aiAcceptScanned(imei, silent, isNew) {
    if (aiScanned.indexOf(imei) !== -1) return;
    aiScanned.push(imei);
    var row = document.getElementById('row_' + imei);
    if (row) {
        row.classList.add('scanned');
        row.querySelector('.icon').className = 'bi bi-check-circle-fill icon';
    } else {
        // New IMEI registered inline — add row to list
        var grid = document.getElementById('aiList');
        var div = document.createElement('div');
        div.id = 'row_' + imei;
        div.className = 'ai-row scanned';
        div.innerHTML = '<span class="imei">' + imei + '</span><i class="bi bi-plus-circle-fill icon" title="New IMEI registered"></i>';
        grid.insertBefore(div, grid.firstChild);
        aiInStock.push(imei);
        if (isNew) aiBumpImeiCount(1);
    }
    aiUpdate();
    if (!silent) aiMsg('✓ ' + imei, 'ok');
}

function aiPromptRegister(imei) {
    var msgEl = document.getElementById('aiMsg');
    msgEl.className = 'ai-msg err';
    msgEl.innerHTML =
        'IMEI <strong style="font-family:monospace;">' + imei + '</strong> not in system. ' +
        '<button type="button" class="ai-reg-btn" data-imei="' + imei.replace(/"/g, '') + '" ' +
        'style="margin-left:8px;background:#16a34a;color:#fff;border:none;border-radius:5px;padding:3px 10px;font-size:.75rem;font-weight:700;cursor:pointer;">' +
        '<i class="bi bi-plus-lg"></i> Register Here</button> ' +
        '<button type="button" class="ai-skip-btn" ' +
        'style="margin-left:4px;background:transparent;color:#64748b;border:1px solid #cbd5e1;border-radius:5px;padding:3px 10px;font-size:.75rem;cursor:pointer;">Skip</button>';
    document.getElementById('aiInput').focus();
}

function aiDismiss() {
    var msgEl = document.getElementById('aiMsg');
    msgEl.className = 'ai-msg';
    msgEl.innerHTML = '';
    document.getElementById('aiInput').focus();
}

function aiDoRegister(imei) {
    var fd = new FormData();
    fd.append('imei', imei);
    fd.append('item_id', aiItemId);
    fd.append('csrf_token', aiCsrf);
    fetch('?page=imei&action=auditRegister', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok) { aiMsg(d.msg, 'err'); return; }
            aiAcceptScanned(imei, false, true);
            document.getElementById('aiInput').focus();
        })
        .catch(function() { aiMsg('Network error during register', 'err'); });
}

function aiPromptRegisterMissing(missing, matchedCount) {
    aiPendingMissing = missing.slice();
    var msgEl = document.getElementById('aiMsg');
    msgEl.className = 'ai-msg err';
    var label = missing.length === 1
        ? ('IMEI <strong style="font-family:monospace;">' + missing[0] + '</strong> not in system.')
        : (missing.length + ' IMEI(s) not in system (physical phones missing from app).');
    var prefix = matchedCount > 0 ? (matchedCount + ' marked present · ') : '';
    msgEl.innerHTML =
        prefix + label + ' ' +
        '<button type="button" class="ai-reg-missing-btn" ' +
        'style="margin-left:8px;background:#16a34a;color:#fff;border:none;border-radius:5px;padding:3px 10px;font-size:.75rem;font-weight:700;cursor:pointer;">' +
        '<i class="bi bi-plus-lg"></i> Register Missing</button> ' +
        '<button type="button" class="ai-skip-btn" ' +
        'style="margin-left:4px;background:transparent;color:#64748b;border:1px solid #cbd5e1;border-radius:5px;padding:3px 10px;font-size:.75rem;cursor:pointer;">Skip</button>';
    document.getElementById('aiInput').focus();
}

function aiDoRegisterMissing() {
    if (!aiPendingMissing.length) return;
    var list = aiPendingMissing.slice();
    var fd = new FormData();
    fd.append('item_id', String(aiItemId));
    fd.append('imeis', list.join('\n'));
    fd.append('csrf_token', aiCsrf);

    var msgEl = document.getElementById('aiMsg');
    msgEl.className = 'ai-msg';
    msgEl.textContent = 'Registering ' + list.length + ' IMEI(s)…';

    fetch('?page=imei&action=auditRegisterBulk', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.ok) { aiMsg(d.msg || 'Register failed', 'err'); return; }
            (d.results || []).forEach(function(row) {
                aiAcceptScanned(row.imei, true, true);
            });
            aiPendingMissing = [];
            var n = d.registered || (d.results || []).length;
            var msg = n + ' missing IMEI(s) registered & marked present';
            if (d.skipped && d.skipped.length) {
                msg += ' · ' + d.skipped.length + ' could not be registered';
            }
            aiMsg(msg, n > 0 ? 'ok' : 'err');
            document.getElementById('aiInput').focus();
        })
        .catch(function() { aiMsg('Network error during register', 'err'); });
}

function aiMsg(t, type) {
    var el = document.getElementById('aiMsg');
    el.className = 'ai-msg ' + type;
    el.textContent = t;
    if (type === 'ok') {
        var ms = t.length > 40 ? 5000 : 1500;
        setTimeout(function() {
            if (el.textContent === t) { el.textContent = ''; el.className = 'ai-msg'; }
        }, ms);
    }
}

function aiUpdate() {
    document.getElementById('aiScanCount').textContent = aiScanned.length;
    document.getElementById('aiScannedField').value = aiScanned.join('\n');
    var unscanned = aiInStock.length - aiScanned.length;
    var sum = document.getElementById('aiSummary');
    var btn = document.getElementById('aiSubmit');

    if (aiScanned.length === 0) {
        if (aiInStock.length > 0) {
            sum.className = 'ai-warn';
            sum.textContent = 'None scanned — submit to mark all ' + aiInStock.length + ' IMEI(s) as transferred (e.g. zero on hand).';
            btn.disabled = false;
        } else {
            sum.className = 'ai-warn';
            sum.textContent = 'No IMEIs in this warehouse for this item.';
            btn.disabled = true;
        }
    } else if (unscanned === 0) {
        sum.className = 'ai-warn-ok';
        sum.textContent = '✓ All ' + aiInStock.length + ' IMEIs scanned. No changes needed.';
        btn.disabled = true;
    } else {
        sum.className = 'ai-warn';
        sum.textContent = 'Submit will mark ' + unscanned + ' unscanned IMEI(s) as transferred.';
        btn.disabled = false;
    }
}

function aiPasteModalOpen() {
    return !!document.querySelector('#aiPasteModal.show');
}

function openAuditPaste(prefill) {
    document.getElementById('aiPmTextarea').value = prefill || '';
    document.getElementById('aiPmLineCount').textContent = aiParsePasteLines(prefill || '').length;
    document.getElementById('aiPmPreview').style.display = 'none';
    document.getElementById('aiPmPreview').innerHTML = '';
    document.getElementById('aiPmConfirmBtn').disabled = true;
    document.getElementById('aiPmConfirmBtn').innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
    aiPmValidList = [];
    aiPmValidated = false;

    var modal = new bootstrap.Modal(document.getElementById('aiPasteModal'));
    modal.show();
    setTimeout(function() { document.getElementById('aiPmTextarea').focus(); }, 350);
}

function updateAuditPasteLineCount() {
    var lines = aiParsePasteLines(document.getElementById('aiPmTextarea').value);
    document.getElementById('aiPmLineCount').textContent = lines.length;
    aiPmValidated = false;
    document.getElementById('aiPmConfirmBtn').disabled = true;
}

function validateAuditPaste() {
    var lines = aiParsePasteLines(document.getElementById('aiPmTextarea').value);
    var valid = [];
    var errors = [];
    var already = 0;
    var seen = {};

    lines.forEach(function(imei) {
        if (imei.length < 8 || imei.length > 20) {
            errors.push({ imei: imei, reason: 'Invalid length (' + imei.length + ')' });
            return;
        }
        if (seen[imei]) {
            errors.push({ imei: imei, reason: 'Duplicate in list' });
            return;
        }
        seen[imei] = true;
        if (aiScanned.indexOf(imei) !== -1) {
            already++;
            return;
        }
        valid.push(imei);
    });

    aiPmValidList = valid;
    aiPmValidated = true;

    var html = '<div class="ai-pm-summary">';
    html += '<span class="ai-pm-ok"><i class="bi bi-check-circle-fill"></i> ' + valid.length + ' to check</span>';
    if (already > 0) {
        html += '<span class="ai-pm-dup"><i class="bi bi-info-circle-fill"></i> ' + already + ' already scanned</span>';
    }
    if (errors.length > 0) {
        html += '<span class="ai-pm-err"><i class="bi bi-x-circle-fill"></i> ' + errors.length + ' invalid (will be skipped)</span>';
    }
    html += '</div>';

    if (errors.length > 0) {
        html += '<div class="ai-pm-err-list"><div class="ai-pm-err-header">Skipped IMEIs:</div>';
        errors.forEach(function(e) {
            html += '<div class="ai-pm-err-row"><span class="ai-pm-err-imei">' + e.imei + '</span><span class="ai-pm-err-reason">' + e.reason + '</span></div>';
        });
        html += '</div>';
    }

    var preview = document.getElementById('aiPmPreview');
    preview.innerHTML = html;
    preview.style.display = 'block';
    document.getElementById('aiPmConfirmBtn').disabled = (valid.length === 0);
}

function confirmAuditPaste() {
    if (aiPmValidList.length === 0) return;

    var btn = document.getElementById('aiPmConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Importing...';

    var fd = new FormData();
    fd.append('item_id', String(aiItemId));
    fd.append('imeis', aiPmValidList.join('\n'));
    fd.append('csrf_token', aiCsrf);

    fetch('?page=imei&action=auditScanBulk', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.ok) {
                aiMsg(res.msg || 'Bulk import failed', 'err');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
                return;
            }

            var matched = 0;
            var missing = [];
            var skipped = [];
            (res.results || []).forEach(function(row) {
                if (row.ok) {
                    aiAcceptScanned(row.imei, true);
                    matched++;
                } else if (row.code === 'not_found') {
                    missing.push(row.imei);
                } else {
                    skipped.push({ imei: row.imei, reason: row.msg || row.code || 'Skipped' });
                }
            });

            bootstrap.Modal.getInstance(document.getElementById('aiPasteModal')).hide();

            if (missing.length > 0) {
                aiPromptRegisterMissing(missing, matched);
                if (skipped.length > 0) {
                    // Append other skip reasons under the register prompt briefly via title on message
                    var other = skipped.slice(0, 5).map(function(s) {
                        return s.imei + ' (' + s.reason + ')';
                    }).join('; ');
                    var msgEl = document.getElementById('aiMsg');
                    msgEl.insertAdjacentHTML('beforeend',
                        '<div style="margin-top:4px;font-size:.72rem;opacity:.85;">Also skipped: ' + other + '</div>');
                }
            } else {
                var msg = matched + ' IMEI(s) marked present';
                if (skipped.length > 0) {
                    var detail = skipped.slice(0, 8).map(function(s) {
                        return s.imei + ' (' + s.reason + ')';
                    }).join('; ');
                    if (skipped.length > 8) detail += '; …';
                    msg += ' · ' + skipped.length + ' skipped' + (detail ? ' — ' + detail : '');
                }
                aiMsg(msg, matched > 0 ? 'ok' : 'err');
            }
            document.getElementById('aiInput').focus();
        })
        .catch(function() {
            aiMsg('Network error during bulk import', 'err');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Confirm Import';
        });
}

document.getElementById('aiInput').addEventListener('input', aiAuto);
document.getElementById('aiInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        aiScan();
    }
});
document.getElementById('aiInput').addEventListener('paste', function(e) {
    var text = (e.clipboardData || window.clipboardData).getData('text') || '';
    var parts = aiParsePasteLines(text);
    if (parts.length > 1) {
        e.preventDefault();
        openAuditPaste(text);
    }
});

document.getElementById('aiBtnPaste').addEventListener('click', function() {
    openAuditPaste('');
});
document.getElementById('aiPmTextarea').addEventListener('input', updateAuditPasteLineCount);
document.getElementById('aiPmTextarea').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter' || e.shiftKey) return;
    e.preventDefault();
    if (aiPmValidated && aiPmValidList.length > 0 && !document.getElementById('aiPmConfirmBtn').disabled) {
        confirmAuditPaste();
    } else {
        validateAuditPaste();
    }
});
document.getElementById('aiPmValidateBtn').addEventListener('click', validateAuditPaste);
document.getElementById('aiPmConfirmBtn').addEventListener('click', confirmAuditPaste);

document.getElementById('aiMsg').addEventListener('click', function(e) {
    var reg = e.target.closest('.ai-reg-btn');
    if (reg) {
        aiDoRegister(reg.getAttribute('data-imei'));
        return;
    }
    if (e.target.closest('.ai-reg-missing-btn')) {
        aiDoRegisterMissing();
        return;
    }
    if (e.target.closest('.ai-skip-btn')) {
        aiPendingMissing = [];
        aiDismiss();
    }
});

document.getElementById('aiForm').addEventListener('submit', function(e) {
    var unscanned = aiInStock.length - aiScanned.length;
    if (unscanned > 0 && !confirm('Mark ' + unscanned + ' unscanned IMEI(s) as transferred? This cannot be undone.')) {
        e.preventDefault();
    }
});

document.getElementById('aiPasteModal').addEventListener('hidden.bs.modal', function() {
    if (!aiPasteModalOpen()) {
        setTimeout(function() { document.getElementById('aiInput').focus(); }, 100);
    }
});

aiUpdate();
setTimeout(function() { document.getElementById('aiInput').focus(); }, 200);
</script>
