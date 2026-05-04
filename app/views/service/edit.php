<style>
.svc-page { max-width: 860px; margin: 0 auto; padding-bottom: 40px; }
.svc-hero { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a9e 100%); border-radius: 16px 16px 0 0; padding: 24px 28px; display: flex; align-items: center; gap: 14px; color: #fff; box-shadow: 0 4px 20px rgba(30,58,95,.2); }
.svc-hero a.back { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.15); color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all .15s; }
.svc-hero a.back:hover { background: rgba(255,255,255,.25); }
.svc-hero-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.svc-hero h1 { margin: 0; font-size: 1.25rem; font-weight: 700; }
.svc-hero p { margin: 2px 0 0; font-size: .8rem; opacity: .85; }
.svc-body { background: var(--bg-card); border: 1px solid var(--border-color); border-top: none; border-radius: 0 0 16px 16px; padding: 24px 28px; }
.svc-section { margin-bottom: 22px; }
.svc-section:last-child { margin-bottom: 0; }
.svc-section-head { display: flex; align-items: center; gap: 8px; padding-bottom: 10px; margin-bottom: 14px; border-bottom: 1.5px solid rgba(99,102,241,.1); }
.svc-section-num { width: 26px; height: 26px; border-radius: 8px; background: rgba(99,102,241,.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .78rem; }
.svc-section-title { font-size: .78rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: .8px; }
.svc-section-sub { font-size: .72rem; color: var(--text-muted); font-weight: 500; margin-left: auto; }
.svc-f { margin-bottom: 12px; }
.svc-f:last-child { margin-bottom: 0; }
.svc-f label { display: block; font-size: .72rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
.svc-f label .req { color: #ef4444; margin-left: 2px; }
.svc-f input, .svc-f select, .svc-f textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border-color); border-radius: 10px; font-size: .88rem; background: var(--bg-main); color: var(--text-main); outline: none; font-family: inherit; transition: all .15s; }
.svc-f input:focus, .svc-f select:focus, .svc-f textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
.svc-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.svc-row21 { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }
.svc-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.svc-pill { padding: 6px 14px; border-radius: 20px; background: var(--bg-main); border: 1.5px solid var(--border-color); color: var(--text-muted); font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .15s; user-select: none; }
.svc-pill:hover { border-color: var(--primary); color: var(--primary); }
.svc-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 2px 8px rgba(99,102,241,.3); }
.svc-cost-wrap { position: relative; }
.svc-cost-wrap .prefix { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: .78rem; font-weight: 700; color: var(--text-muted); pointer-events: none; }
.svc-cost-wrap input { padding-left: 52px; font-weight: 600; }
.svc-actions { display: flex; gap: 10px; justify-content: space-between; margin-top: 24px; padding-top: 20px; border-top: 1.5px solid var(--border-color); }
.svc-btn { padding: 11px 24px; border-radius: 10px; font-size: .88rem; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all .15s; }
.svc-btn-save { background: linear-gradient(135deg, var(--primary), #4f46e5); color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,.3); }
.svc-btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,.4); }
.svc-btn-cancel { background: transparent; border: 1.5px solid var(--border-color); color: var(--text-muted); }
.svc-btn-cancel:hover { border-color: #ef4444; color: #ef4444; }
@media (max-width: 640px) { .svc-row2, .svc-row21 { grid-template-columns: 1fr; } .svc-body { padding: 16px; } }
</style>

<div class="svc-page">
    <div class="svc-hero">
        <a href="?page=service&action=detail&id=<?= $record['id'] ?>" class="back"><i class="bi bi-arrow-left"></i></a>
        <div class="svc-hero-icon"><i class="bi bi-pencil-square"></i></div>
        <div style="flex:1;">
            <h1>Edit <?= htmlspecialchars($record['service_no']) ?></h1>
            <p>Update service record details</p>
        </div>
    </div>

    <form method="POST" action="?page=service&action=update">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="id" value="<?= $record['id'] ?>">

        <div class="svc-body">
            <!-- Section 1: Device -->
            <div class="svc-section">
                <div class="svc-section-head">
                    <div class="svc-section-num">1</div>
                    <div class="svc-section-title">Device Information</div>
                    <div class="svc-section-sub">
                        <input type="date" name="received_date" value="<?= htmlspecialchars($record['received_date'] ?: date('Y-m-d')) ?>" style="padding:4px 10px;border:1.5px solid var(--border-color);border-radius:8px;font-size:.78rem;background:var(--bg-main);color:var(--text-main);outline:none;">
                    </div>
                </div>

                <div class="svc-f">
                    <label>IMEI / Serial Number <span class="req">*</span></label>
                    <input type="text" name="imei" required autocomplete="off"
                        value="<?= htmlspecialchars($record['imei']) ?>"
                        style="font-family:monospace;font-size:1.1rem;font-weight:700;letter-spacing:1px;">
                </div>

                <div class="svc-row2">
                    <div class="svc-f">
                        <label>Brand</label>
                        <?php $deviceBrandValue = (string) ($record['device_brand'] ?? ''); include __DIR__ . '/partials/device_brand_select.php'; ?>
                    </div>
                    <div class="svc-f">
                        <label>Model</label>
                        <input type="text" name="device_model" value="<?= htmlspecialchars($record['device_model'] ?? '') ?>" placeholder="A17 5G 6GB/128GB">
                    </div>
                </div>
            </div>

            <!-- Section 2: Customer -->
            <div class="svc-section">
                <div class="svc-section-head">
                    <div class="svc-section-num">2</div>
                    <div class="svc-section-title">Customer</div>
                </div>

                <div class="svc-f">
                    <label>Existing Customer (optional)</label>
                    <select name="party_id" id="partySelect" onchange="fillCustomer(this)">
                        <option value="">— Walk-in / New Customer —</option>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= $p['id'] ?>" data-phone="<?= htmlspecialchars($p['phone'] ?? '') ?>" data-name="<?= htmlspecialchars($p['name']) ?>" <?= (int)$record['party_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?><?= $p['phone'] ? ' · ' . htmlspecialchars($p['phone']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="svc-row21">
                    <div class="svc-f">
                        <label>Customer Name <span class="req">*</span></label>
                        <input type="text" name="customer_name" id="customerName" required value="<?= htmlspecialchars($record['customer_name'] ?? '') ?>" placeholder="Full name">
                    </div>
                    <div class="svc-f">
                        <label>Phone</label>
                        <input type="text" name="customer_phone" id="customerPhone" value="<?= htmlspecialchars($record['customer_phone'] ?? '') ?>" placeholder="55123456">
                    </div>
                </div>
            </div>

            <!-- Section 3: Service Details -->
            <div class="svc-section">
                <div class="svc-section-head">
                    <div class="svc-section-num">3</div>
                    <div class="svc-section-title">Service Details</div>
                </div>

                <div class="svc-f">
                    <label>Fault Category</label>
                    <input type="hidden" name="fault_category" id="faultCategory" value="<?= htmlspecialchars($record['fault_category'] ?? '') ?>">
                    <div class="svc-pills" id="faultPills">
                        <?php foreach (['Screen','Battery','Charging Port','Camera','Speaker','Microphone','Software','Water Damage','Not Turning On','Touch Issue','Network','Other'] as $cat): ?>
                        <div class="svc-pill <?= ($record['fault_category'] === $cat) ? 'active' : '' ?>" data-value="<?= htmlspecialchars($cat) ?>" onclick="selectFault(this)"><?= htmlspecialchars($cat) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="svc-f">
                    <label>Fault Description</label>
                    <textarea name="fault_description" rows="3"><?= htmlspecialchars($record['fault_description'] ?? '') ?></textarea>
                </div>

                <div class="svc-row2">
                    <div class="svc-f">
                        <label>Technician</label>
                        <input type="text" name="technician_name" value="<?= htmlspecialchars($record['technician_name'] ?? '') ?>" placeholder="Tech name">
                    </div>
                    <div class="svc-f">
                        <label>Repair Cost</label>
                        <div class="svc-cost-wrap">
                            <span class="prefix"><?= APP_CURRENCY ?></span>
                            <input type="number" name="repair_cost" step="0.001" min="0" value="<?= number_format((float)($record['repair_cost'] ?? 0), 3, '.', '') ?>">
                        </div>
                    </div>
                </div>

                <div class="svc-f">
                    <label>Notes</label>
                    <textarea name="notes" rows="2"><?= htmlspecialchars($record['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="svc-actions">
                <a href="?page=service&action=detail&id=<?= $record['id'] ?>" class="svc-btn svc-btn-cancel"><i class="bi bi-x-lg"></i> Cancel</a>
                <button type="submit" class="svc-btn svc-btn-save pin-protect"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>

<script>
function fillCustomer(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!sel.value) return;
    document.getElementById('customerName').value = opt.dataset.name || '';
    document.getElementById('customerPhone').value = opt.dataset.phone || '';
}
function selectFault(el) {
    document.querySelectorAll('#faultPills .svc-pill').forEach(function(p) { p.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('faultCategory').value = el.dataset.value;
}

</script>
