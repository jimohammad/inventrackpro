<style>
.svc-page { max-width: 860px; margin: 0 auto; padding-bottom: 40px; }

/* Hero header */
.svc-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a9e 100%);
    border-radius: 16px 16px 0 0;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #fff;
    box-shadow: 0 4px 20px rgba(30,58,95,.2);
}
.svc-hero a.back {
    width: 36px; height: 36px; border-radius: 10px;
    background: rgba(255,255,255,.15); color: #fff;
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; transition: all .15s;
}
.svc-hero a.back:hover { background: rgba(255,255,255,.25); }
.svc-hero-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: rgba(255,255,255,.2); display: flex;
    align-items: center; justify-content: center; font-size: 1.4rem;
}
.svc-hero h1 { margin: 0; font-size: 1.25rem; font-weight: 700; }
.svc-hero p { margin: 2px 0 0; font-size: .8rem; opacity: .85; }

/* Body */
.svc-body {
    background: var(--bg-card); border: 1px solid var(--border-color);
    border-top: none; border-radius: 0 0 16px 16px;
    padding: 24px 28px;
}

/* Section */
.svc-section { margin-bottom: 22px; }
.svc-section:last-child { margin-bottom: 0; }
.svc-section-head {
    display: flex; align-items: center; gap: 8px;
    padding-bottom: 10px; margin-bottom: 14px;
    border-bottom: 1.5px solid rgba(99,102,241,.1);
}
.svc-section-num {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(99,102,241,.1); color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .78rem;
}
.svc-section-title {
    font-size: .78rem; font-weight: 700; color: var(--primary);
    text-transform: uppercase; letter-spacing: .8px;
}
.svc-section-sub {
    font-size: .72rem; color: var(--text-muted); font-weight: 500;
    margin-left: auto;
}

/* Form field */
.svc-f { margin-bottom: 12px; }
.svc-f:last-child { margin-bottom: 0; }
.svc-f label {
    display: block; font-size: .72rem; font-weight: 600;
    color: var(--text-muted); margin-bottom: 5px;
    text-transform: uppercase; letter-spacing: .4px;
}
.svc-f label .req { color: #ef4444; margin-left: 2px; }
.svc-f input, .svc-f select, .svc-f textarea {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid var(--border-color); border-radius: 10px;
    font-size: .88rem; background: var(--bg-main); color: var(--text-main);
    outline: none; font-family: inherit; transition: all .15s;
}
.svc-f input:focus, .svc-f select:focus, .svc-f textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.svc-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.svc-row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.svc-row21 { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }

/* IMEI special */
.svc-imei-wrap {
    position: relative;
    background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(139,92,246,.06));
    border: 2px solid rgba(99,102,241,.2); border-radius: 12px;
    transition: all .15s;
}
.svc-imei-wrap:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.svc-imei-wrap::before {
    content: '\F52A'; font-family: 'bootstrap-icons';
    position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
    font-size: 1.5rem; color: var(--primary); pointer-events: none;
}
.svc-imei-wrap input {
    border: none !important; background: transparent !important;
    padding: 18px 16px 18px 52px !important; font-family: monospace;
    font-size: 1.4rem; letter-spacing: 2px; font-weight: 700;
    color: var(--text-main); box-shadow: none !important;
    width: 100%; display: block; outline: none !important;
}

/* Fault category pills */
.svc-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.svc-pill {
    padding: 6px 14px; border-radius: 20px; background: var(--bg-main);
    border: 1.5px solid var(--border-color); color: var(--text-muted);
    font-size: .78rem; font-weight: 600; cursor: pointer;
    transition: all .15s; user-select: none;
    display: flex; align-items: center; gap: 5px;
}
.svc-pill:hover { border-color: var(--primary); color: var(--primary); }
.svc-pill.active {
    background: var(--primary); color: #fff; border-color: var(--primary);
    box-shadow: 0 2px 8px rgba(99,102,241,.3);
}

/* Customer card */
.svc-customer-hint {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; background: rgba(99,102,241,.06);
    border-radius: 10px; margin-bottom: 12px; font-size: .82rem;
    color: var(--text-muted);
}
.svc-customer-hint i { color: var(--primary); font-size: 1rem; }

/* Cost field */
.svc-cost-wrap { position: relative; }
.svc-cost-wrap .prefix {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    font-size: .78rem; font-weight: 700; color: var(--text-muted);
    pointer-events: none; letter-spacing: .5px;
}
.svc-cost-wrap input { padding-left: 52px; font-weight: 600; }

/* Actions */
.svc-actions {
    display: flex; gap: 10px; justify-content: space-between;
    margin-top: 24px; padding-top: 20px;
    border-top: 1.5px solid var(--border-color);
}
.svc-btn {
    padding: 11px 24px; border-radius: 10px; font-size: .88rem;
    font-weight: 600; cursor: pointer; border: none;
    display: inline-flex; align-items: center; gap: 8px;
    text-decoration: none; transition: all .15s;
}
.svc-btn-save {
    background: linear-gradient(135deg, var(--primary), #4f46e5);
    color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,.3);
}
.svc-btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,.4); }
.svc-btn-cancel {
    background: transparent; border: 1.5px solid var(--border-color);
    color: var(--text-muted);
}
.svc-btn-cancel:hover { border-color: #ef4444; color: #ef4444; }

@media (max-width: 640px) {
    .svc-row2, .svc-row3, .svc-row21 { grid-template-columns: 1fr; }
    .svc-body { padding: 16px; }
}
</style>

<div class="svc-page">
    <!-- Hero -->
    <div class="svc-hero">
        <a href="?page=service" class="back"><i class="bi bi-arrow-left"></i></a>
        <div class="svc-hero-icon"><i class="bi bi-tools"></i></div>
        <div style="flex:1;">
            <h1>New Service Record</h1>
            <p>Register a device for repair or replacement</p>
        </div>
    </div>

    <form method="POST" action="?page=service&action=create" id="svcForm">
        <?= Auth::csrfField() ?>

        <div class="svc-body">
            <!-- Section 1: Device -->
            <div class="svc-section">
                <div class="svc-section-head">
                    <div class="svc-section-num">1</div>
                    <div class="svc-section-title">Device Information</div>
                    <div class="svc-section-sub">
                        <input type="date" name="received_date" value="<?= date('Y-m-d') ?>" style="padding:4px 10px;border:1.5px solid var(--border-color);border-radius:8px;font-size:.78rem;background:var(--bg-main);color:var(--text-main);outline:none;">
                    </div>
                </div>

                <!-- IMEI highlighted box -->
                <div class="svc-imei-wrap">
                    <input type="text" name="imei" required autofocus autocomplete="off" placeholder="IMEI / Serial Number">
                </div>

                <div class="svc-row2" style="margin-top:12px;">
                    <div class="svc-f">
                        <label>Brand</label>
                        <input type="text" name="device_brand" placeholder="Samsung, Redmi, Honor...">
                    </div>
                    <div class="svc-f">
                        <label>Model</label>
                        <input type="text" name="device_model" placeholder="A17 5G 6GB/128GB">
                    </div>
                </div>
            </div>

            <!-- Section 2: Customer -->
            <div class="svc-section">
                <div class="svc-section-head">
                    <div class="svc-section-num">2</div>
                    <div class="svc-section-title">Customer</div>
                    <div class="svc-section-sub">Select existing or walk-in</div>
                </div>

                <div class="svc-f">
                    <label>Existing Customer (optional)</label>
                    <select name="party_id" id="partySelect" onchange="fillCustomer(this)">
                        <option value="">— Walk-in / New Customer —</option>
                        <?php foreach ($parties as $p): ?>
                        <option value="<?= $p['id'] ?>" data-phone="<?= htmlspecialchars($p['phone'] ?? '') ?>" data-name="<?= htmlspecialchars($p['name']) ?>">
                            <?= htmlspecialchars($p['name']) ?><?= $p['phone'] ? ' · ' . htmlspecialchars($p['phone']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="svc-row21">
                    <div class="svc-f">
                        <label>Customer Name <span class="req">*</span></label>
                        <input type="text" name="customer_name" id="customerName" required placeholder="Full name">
                    </div>
                    <div class="svc-f">
                        <label>Phone</label>
                        <input type="text" name="customer_phone" id="customerPhone" placeholder="55123456">
                    </div>
                </div>
            </div>

            <!-- Section 3: Service Details -->
            <div class="svc-section">
                <div class="svc-section-head">
                    <div class="svc-section-num">3</div>
                    <div class="svc-section-title">Service Details</div>
                    <div class="svc-section-sub">Fault & repair info</div>
                </div>

                <div class="svc-f">
                    <label>Fault Category</label>
                    <input type="hidden" name="fault_category" id="faultCategory" value="">
                    <div class="svc-pills" id="faultPills">
                        <?php foreach (['Screen','Battery','Charging Port','Camera','Speaker','Microphone','Software','Water Damage','Not Turning On','Touch Issue','Network','Other'] as $cat): ?>
                        <div class="svc-pill" data-value="<?= htmlspecialchars($cat) ?>" onclick="selectFault(this)"><?= htmlspecialchars($cat) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="svc-f">
                    <label>Fault Description</label>
                    <textarea name="fault_description" rows="3" placeholder="What's wrong with the device..."></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="svc-actions">
                <a href="?page=service" class="svc-btn svc-btn-cancel"><i class="bi bi-x-lg"></i> Cancel</a>
                <button type="submit" class="svc-btn svc-btn-save">
                    <i class="bi bi-check-lg"></i> Save & Create Tracking
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function fillCustomer(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!sel.value) return;
    var nameEl = document.getElementById('customerName');
    var phoneEl = document.getElementById('customerPhone');
    if (!nameEl.value || nameEl.dataset.autofilled === '1') {
        nameEl.value = opt.dataset.name || '';
        nameEl.dataset.autofilled = '1';
    }
    if (!phoneEl.value || phoneEl.dataset.autofilled === '1') {
        phoneEl.value = opt.dataset.phone || '';
        phoneEl.dataset.autofilled = '1';
    }
}

function selectFault(el) {
    document.querySelectorAll('#faultPills .svc-pill').forEach(function(p) { p.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('faultCategory').value = el.dataset.value;
}
</script>
