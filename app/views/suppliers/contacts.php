<?php
$grouped = [];
$countryOrder = ['Dubai', 'Hongkong', 'China', 'Other'];
$countryIcons = ['Dubai' => 'bi-building', 'Hongkong' => 'bi-globe-asia-australia', 'China' => 'bi-globe-asia-australia', 'Other' => 'bi-geo-alt'];
$countryColors = ['Dubai' => '#f59e0b', 'Hongkong' => '#ef4444', 'China' => '#dc2626', 'Other' => '#6366f1'];
$typeIcons = ['Mobile Phones' => 'bi-phone', 'Accessories' => 'bi-headset', 'Tablets' => 'bi-tablet', 'Mixed' => 'bi-box-seam'];
foreach ($contacts as $c) { $grouped[$c['country']][$c['product_type']][] = $c; }

$phoneCodes = ['+971'=>'UAE','+852'=>'HK','+86'=>'CN','+965'=>'KW','+966'=>'SA','+91'=>'IN','+92'=>'PK','+44'=>'UK','+1'=>'US'];
$phoneOptions = '';
foreach ($phoneCodes as $code => $label) {
    $phoneOptions .= '<option value="'.$code.'">'.$code.' '.$label.'</option>';
}
?>

<style>
.sc-page { max-width: 1000px; }
.sc-section { margin-bottom: 24px; }
.sc-country-head { display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:25px;color:#fff;font-weight:800;font-size:1rem;margin-bottom:12px; }
.sc-type-head { display:flex;align-items:center;gap:6px;padding:6px 0;margin-bottom:6px;border-bottom:1px solid var(--border-color);font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px; }
.sc-card { background:var(--bg-card);border:1px solid var(--border-color);border-radius:10px;padding:14px 16px;margin-bottom:6px;display:flex;align-items:flex-start;gap:14px;transition:all .15s;position:relative; }
.sc-card:hover { border-color:var(--primary);transform:translateX(3px);box-shadow:0 2px 8px rgba(0,0,0,.06); }
.sc-card-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0; }
.sc-card-body { flex:1;min-width:0; }
.sc-company { font-weight:700;font-size:.92rem;color:var(--text-main); }
.sc-contact-row { display:flex;flex-wrap:wrap;gap:6px 16px;margin-top:5px;font-size:.78rem;color:var(--text-muted); }
.sc-contact-row .sc-ct { display:flex;align-items:center;gap:3px;background:var(--bg-main);padding:3px 8px;border-radius:6px;border:1px solid var(--border-color); }
.sc-contact-row .sc-ct-name { font-weight:600;color:var(--text-main); }
.sc-contact-row a { color:var(--primary);text-decoration:none; }
.sc-contact-row a:hover { text-decoration:underline; }
.sc-address { font-size:.78rem;color:var(--text-muted);margin-top:4px; }
.sc-actions { position:absolute;top:10px;right:10px;display:flex;gap:4px;opacity:0;transition:opacity .15s; }
.sc-card:hover .sc-actions { opacity:1; }
.sc-act { width:26px;height:26px;border-radius:6px;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;transition:all .15s; }
.sc-act.edit { background:rgba(59,130,246,.1);color:#3b82f6; }
.sc-act.del { background:rgba(239,68,68,.1);color:#ef4444; }

/* Modal */
.sc-modal { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:flex-start;justify-content:center;backdrop-filter:blur(4px);padding:max(16px, env(safe-area-inset-top, 0px)) 14px max(24px, env(safe-area-inset-bottom, 0px));box-sizing:border-box;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch; }
.sc-modal.show { display:flex; }
.sc-modal-box { background:linear-gradient(165deg,#f8fafc 0%,var(--bg-card) 22%,var(--bg-card) 100%);border:1px solid rgba(99,102,241,.22);border-radius:18px;width:min(96vw,980px);max-width:none;box-shadow:0 4px 24px rgba(99,102,241,.12),0 24px 64px rgba(15,23,42,.28);margin:0 auto 8px;flex-shrink:0; }
.sc-modal-box > form { display:block; }
.sc-modal-head { padding:20px 26px;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(125deg,#4f46e5 0%,#7c3aed 42%,#a855f7 100%);border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;border:1px solid rgba(255,255,255,.12);border-bottom:none; }
.sc-modal-head h3 { font-size:1.2rem;font-weight:800;color:#fff;margin:0;display:flex;align-items:center;gap:12px;text-shadow:0 1px 2px rgba(0,0,0,.12); }
.sc-modal-head h3 > i { color:#fde68a !important;filter:drop-shadow(0 1px 1px rgba(0,0,0,.2));font-size:1.35rem; }
.sc-modal-head .close-x { background:rgba(255,255,255,.12);border:none;color:#fff;font-size:1.25rem;cursor:pointer;line-height:1;width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;transition:background .15s; }
.sc-modal-head .close-x:hover { background:rgba(255,255,255,.22); }
.sc-modal-body { padding:20px 26px 18px; }
.sc-modal-foot { padding:18px 26px 22px;display:flex;justify-content:flex-end;gap:12px;border-top:1px solid rgba(99,102,241,.14);background:var(--bg-card);border-radius:0 0 17px 17px; }

.sc-sep { font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.75px;margin:12px 0 8px;padding:9px 14px 9px 11px;border-radius:10px;border:none;border-left:4px solid;display:flex;align-items:center;gap:8px; }
.sc-sep:first-child { margin-top:0; }
.sc-sep i { font-size:.92rem;opacity:.95; }
.sc-modal-contact-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px 22px;align-items:start;margin-top:4px; }
.sc-contact-col .sc-sep { margin-top:0; }
@media (max-width: 900px) {
.sc-r3 { grid-template-columns:1fr; }
}
@media (max-width: 720px) {
.sc-modal-contact-grid { grid-template-columns:1fr; }
}
.sc-sep-company { color:#0369a1;background:linear-gradient(90deg,rgba(14,165,233,.14),rgba(14,165,233,.02));border-left-color:#0ea5e9; }
.sc-sep-c1 { color:#047857;background:linear-gradient(90deg,rgba(16,185,129,.16),rgba(16,185,129,.02));border-left-color:#10b981; }
.sc-sep-c2 { color:#86198f;background:linear-gradient(90deg,rgba(217,70,239,.14),rgba(217,70,239,.02));border-left-color:#d946ef; }
.sc-sep-notes { color:#92400e;background:linear-gradient(90deg,rgba(251,191,36,.2),rgba(251,191,36,.04));border-left-color:#f59e0b; }
.sc-notes-ta { min-height:52px;max-height:80px;resize:vertical; }

.sc-f { margin-bottom:7px; }
.sc-f label { display:block;font-size:.74rem;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px; }
.sc-f input,.sc-f select,.sc-f textarea { width:100%;padding:10px 12px;border:1.5px solid var(--border-color);border-radius:10px;font-size:.93rem;background:var(--bg-main);color:var(--text-main);outline:none;font-family:inherit;transition:border-color .15s,box-shadow .15s; }
.sc-f input:focus,.sc-f select:focus,.sc-f textarea:focus { border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.sc-r2 { display:grid;grid-template-columns:1fr 1fr;gap:12px 14px; }
.sc-r3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px 14px; }

.sc-phone { display:flex; }
.sc-phone select { width:92px;border-radius:10px 0 0 10px;border-right:none;padding:10px 2px 10px 8px;font-size:.82rem;flex-shrink:0; }
.sc-phone input { border-radius:0 10px 10px 0;flex:1;min-width:0; }

.sc-person-block { border-radius:12px;padding:16px;margin-bottom:0;border:1px solid; }
.sc-person-block.sc-pb-1 { background:linear-gradient(145deg,rgba(16,185,129,.08),rgba(16,185,129,.02));border-color:rgba(16,185,129,.28);box-shadow:0 2px 12px rgba(16,185,129,.06); }
.sc-person-block.sc-pb-2 { background:linear-gradient(145deg,rgba(217,70,239,.08),rgba(217,70,239,.02));border-color:rgba(217,70,239,.25);box-shadow:0 2px 12px rgba(217,70,239,.06); }
.sc-person-num { font-size:.7rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px; }

.sc-btn-cancel { padding:11px 24px;background:#fff;border:1.5px solid rgba(100,116,139,.35);color:#475569;border-radius:11px;cursor:pointer;font-size:.92rem;font-weight:600;transition:all .15s; }
.sc-btn-cancel:hover { border-color:#64748b;color:#334155;background:#f8fafc; }
.sc-btn-save { padding:11px 30px;background:linear-gradient(125deg,#4f46e5,#7c3aed);border:none;color:#fff;border-radius:11px;cursor:pointer;font-size:.92rem;font-weight:700;box-shadow:0 4px 14px rgba(79,70,229,.35);transition:transform .12s,box-shadow .15s; }
.sc-btn-save:hover { transform:translateY(-1px);box-shadow:0 6px 20px rgba(79,70,229,.4); }
.sc-empty { text-align:center;padding:40px;color:var(--text-muted); }
</style>

<div class="sc-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title">Supplier Contacts</h1>
            <p class="page-subtitle"><?= count($contacts) ?> suppliers across <?= count($grouped) ?> countries</p>
        </div>
        <?php if (Auth::can('supplier_contacts', 'add')): ?>
        <button class="btn btn-primary btn-sm" onclick="openAddModal()"><i class="bi bi-plus-lg me-1"></i> Add Supplier</button>
        <?php endif; ?>
    </div>

    <?php if (empty($contacts)): ?>
    <div class="sc-empty">
        <i class="bi bi-building" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>
        No supplier contacts yet. Add your first one!
    </div>
    <?php endif; ?>

    <?php foreach ($countryOrder as $country):
        if (!isset($grouped[$country])) continue;
        $color = $countryColors[$country] ?? '#6366f1';
        $cIcon = $countryIcons[$country] ?? 'bi-geo-alt';
    ?>
    <div class="sc-section">
        <div class="sc-country-head" style="background:<?= $color ?>;">
            <i class="bi <?= $cIcon ?>"></i> <?= htmlspecialchars($country) ?>
            <span style="background:rgba(255,255,255,.25);padding:2px 8px;border-radius:10px;font-size:.75rem;"><?= array_sum(array_map('count', $grouped[$country])) ?></span>
        </div>
        <?php foreach ($grouped[$country] as $type => $typeContacts): $tIcon = $typeIcons[$type] ?? 'bi-box-seam'; ?>
        <div style="margin-left:12px;margin-bottom:14px;">
            <div class="sc-type-head"><i class="bi <?= $tIcon ?>"></i> <?= htmlspecialchars($type) ?> <span style="margin-left:4px;font-size:.7rem;">(<?= count($typeContacts) ?>)</span></div>
            <?php foreach ($typeContacts as $c): ?>
            <div class="sc-card">
                <div class="sc-card-icon" style="background:<?= $color ?>15;color:<?= $color ?>;"><i class="bi bi-building"></i></div>
                <div class="sc-card-body">
                    <div class="sc-company"><?= htmlspecialchars($c['company_name']) ?></div>
                    <?php if ($c['contact_person']): ?>
                    <div class="sc-contact-row">
                        <span class="sc-ct"><i class="bi bi-person"></i><span class="sc-ct-name"><?= htmlspecialchars($c['contact_person']) ?></span></span>
                        <?php if ($c['mobile']): ?><span class="sc-ct"><i class="bi bi-phone"></i><a href="tel:<?= htmlspecialchars($c['mobile']) ?>"><?= htmlspecialchars($c['mobile']) ?></a></span><?php endif; ?>
                        <?php if (!empty($c['wechat'])): ?><span class="sc-ct"><i class="bi bi-chat-dots" style="color:#07c160;"></i><?= htmlspecialchars($c['wechat']) ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($c['contact_person_2'])): ?>
                    <div class="sc-contact-row">
                        <span class="sc-ct"><i class="bi bi-person"></i><span class="sc-ct-name"><?= htmlspecialchars($c['contact_person_2']) ?></span></span>
                        <?php if (!empty($c['mobile_2'])): ?><span class="sc-ct"><i class="bi bi-phone"></i><a href="tel:<?= htmlspecialchars($c['mobile_2']) ?>"><?= htmlspecialchars($c['mobile_2']) ?></a></span><?php endif; ?>
                        <?php if (!empty($c['wechat_2'])): ?><span class="sc-ct"><i class="bi bi-chat-dots" style="color:#07c160;"></i><?= htmlspecialchars($c['wechat_2']) ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($c['email']): ?>
                    <div class="sc-address"><i class="bi bi-envelope me-1"></i><a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="color:var(--primary);text-decoration:none;"><?= htmlspecialchars($c['email']) ?></a></div>
                    <?php endif; ?>
                    <?php if ($c['address']): ?>
                    <div class="sc-address"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($c['address']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (Auth::can('supplier_contacts', 'edit') || Auth::can('supplier_contacts', 'delete')): ?>
                <div class="sc-actions">
                    <?php if (Auth::can('supplier_contacts', 'edit')): ?>
                    <button class="sc-act edit" onclick='openEditModal(<?= json_encode($c) ?>)' title="Edit"><i class="bi bi-pencil"></i></button>
                    <?php endif; ?>
                    <?php if (Auth::can('supplier_contacts', 'delete')): ?>
                    <form method="POST" action="?page=suppliercontacts&action=delete" style="display:inline;" onsubmit="return confirm('Delete this supplier?')">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="sc-act del" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- ADD MODAL -->
<div class="sc-modal" id="addModal">
    <div class="sc-modal-box">
        <div class="sc-modal-head">
            <h3><i class="bi bi-plus-circle"></i> New Supplier</h3>
            <button class="close-x" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" action="?page=suppliercontacts&action=store">
            <?= Auth::csrfField() ?>
            <div class="sc-modal-body">
                <div class="sc-sep sc-sep-company"><i class="bi bi-building"></i> Company</div>
                <div class="sc-f"><label>Company Name *</label><input type="text" name="company_name" id="addCompany" required placeholder="e.g. Samsung Gulf FZE"></div>
                <div class="sc-r3">
                    <div class="sc-f"><label>Country *</label><select name="country" id="addCountry"><?php foreach($countryOrder as $co): ?><option value="<?=$co?>"><?=$co?></option><?php endforeach; ?></select></div>
                    <div class="sc-f"><label>Product Type *</label><select name="product_type" id="addType"><option value="Mobile Phones">Mobile Phones</option><option value="Accessories">Accessories</option><option value="Tablets">Tablets</option><option value="Mixed">Mixed</option></select></div>
                    <div class="sc-f"><label>Email</label><input type="email" name="email" id="addEmail" placeholder="company@email.com"></div>
                </div>
                <div class="sc-f"><label>Address</label><input type="text" name="address" id="addAddress" placeholder="Office / warehouse"></div>

                <div class="sc-modal-contact-grid">
                    <div class="sc-contact-col">
                        <div class="sc-sep sc-sep-c1"><i class="bi bi-person"></i> Contact Person 1</div>
                        <div class="sc-person-block sc-pb-1">
                            <div class="sc-f"><label>Name</label><input type="text" name="contact_person" id="addP1Name" placeholder="Name / designation"></div>
                            <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="addP1Code"><?= $phoneOptions ?></select><input type="text" id="addP1Num" placeholder="50 xxx xxxx"></div><input type="hidden" name="mobile" id="addP1Full"></div>
                            <div class="sc-f"><label>WeChat</label><input type="text" name="wechat" id="addP1Wechat" placeholder="WeChat ID"></div>
                        </div>
                    </div>
                    <div class="sc-contact-col">
                        <div class="sc-sep sc-sep-c2"><i class="bi bi-person"></i> Contact Person 2</div>
                        <div class="sc-person-block sc-pb-2">
                            <div class="sc-f"><label>Name</label><input type="text" name="contact_person_2" id="addP2Name" placeholder="Name / designation"></div>
                            <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="addP2Code"><?= $phoneOptions ?></select><input type="text" id="addP2Num" placeholder="50 xxx xxxx"></div><input type="hidden" name="mobile_2" id="addP2Full"></div>
                            <div class="sc-f"><label>WeChat</label><input type="text" name="wechat_2" id="addP2Wechat" placeholder="WeChat ID"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sc-modal-foot">
                <button type="button" class="sc-btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="sc-btn-save"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="sc-modal" id="editModal">
    <div class="sc-modal-box">
        <div class="sc-modal-head">
            <h3><i class="bi bi-pencil"></i> Edit Supplier</h3>
            <button class="close-x" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" action="?page=suppliercontacts&action=update">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" id="editId">
            <div class="sc-modal-body">
                <div class="sc-sep sc-sep-company"><i class="bi bi-building"></i> Company</div>
                <div class="sc-f"><label>Company Name *</label><input type="text" name="company_name" id="editCompany" required></div>
                <div class="sc-r3">
                    <div class="sc-f"><label>Country *</label><select name="country" id="editCountry"><?php foreach($countryOrder as $co): ?><option value="<?=$co?>"><?=$co?></option><?php endforeach; ?></select></div>
                    <div class="sc-f"><label>Product Type *</label><select name="product_type" id="editType"><option value="Mobile Phones">Mobile Phones</option><option value="Accessories">Accessories</option><option value="Tablets">Tablets</option><option value="Mixed">Mixed</option></select></div>
                    <div class="sc-f"><label>Email</label><input type="email" name="email" id="editEmail"></div>
                </div>
                <div class="sc-f"><label>Address</label><input type="text" name="address" id="editAddress"></div>

                <div class="sc-modal-contact-grid">
                    <div class="sc-contact-col">
                        <div class="sc-sep sc-sep-c1"><i class="bi bi-person"></i> Contact Person 1</div>
                        <div class="sc-person-block sc-pb-1">
                            <div class="sc-f"><label>Name</label><input type="text" name="contact_person" id="editP1Name"></div>
                            <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="editP1Code"><?= $phoneOptions ?></select><input type="text" id="editP1Num"></div><input type="hidden" name="mobile" id="editP1Full"></div>
                            <div class="sc-f"><label>WeChat</label><input type="text" name="wechat" id="editP1Wechat"></div>
                        </div>
                    </div>
                    <div class="sc-contact-col">
                        <div class="sc-sep sc-sep-c2"><i class="bi bi-person"></i> Contact Person 2</div>
                        <div class="sc-person-block sc-pb-2">
                            <div class="sc-f"><label>Name</label><input type="text" name="contact_person_2" id="editP2Name"></div>
                            <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="editP2Code"><?= $phoneOptions ?></select><input type="text" id="editP2Num"></div><input type="hidden" name="mobile_2" id="editP2Full"></div>
                            <div class="sc-f"><label>WeChat</label><input type="text" name="wechat_2" id="editP2Wechat"></div>
                        </div>
                    </div>
                </div>

                <div class="sc-sep sc-sep-notes"><i class="bi bi-journal-text"></i> Notes</div>
                <div class="sc-f"><textarea name="notes" id="editNotes" rows="2" class="sc-notes-ta"></textarea></div>
            </div>
            <div class="sc-modal-foot">
                <button type="button" class="sc-btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="sc-btn-save"><i class="bi bi-check-lg me-1"></i>Update</button>
            </div>
        </form>
    </div>
</div>

<script>
var codes = ['+971','+852','+86','+965','+966','+91','+92','+44','+1'];
function splitPhone(ph) {
    ph = (ph||'').trim();
    for (var i=0;i<codes.length;i++) { if (ph.indexOf(codes[i])===0) return {code:codes[i],num:ph.substring(codes[i].length).trim()}; }
    return {code:'+971',num:ph};
}
function combPhone(codeId,numId,hidId) {
    var c=document.getElementById(codeId).value, n=document.getElementById(numId).value.trim();
    document.getElementById(hidId).value = n ? c+' '+n : '';
}

function openAddModal() { var m=document.getElementById('addModal'); m.querySelector('form').reset(); m.classList.add('show'); }
function openEditModal(c) {
    document.getElementById('editId').value = c.id;
    document.getElementById('editCompany').value = c.company_name||'';
    document.getElementById('editCountry').value = c.country||'Dubai';
    document.getElementById('editType').value = c.product_type||'Mobile Phones';
    document.getElementById('editEmail').value = c.email||'';
    document.getElementById('editAddress').value = c.address||'';
    // Person 1
    document.getElementById('editP1Name').value = c.contact_person||'';
    var p1 = splitPhone(c.mobile);
    document.getElementById('editP1Code').value = p1.code;
    document.getElementById('editP1Num').value = p1.num;
    document.getElementById('editP1Wechat').value = c.wechat||'';
    // Person 2
    document.getElementById('editP2Name').value = c.contact_person_2||'';
    var p2 = splitPhone(c.mobile_2);
    document.getElementById('editP2Code').value = p2.code;
    document.getElementById('editP2Num').value = p2.num;
    document.getElementById('editP2Wechat').value = c.wechat_2||'';
    document.getElementById('editNotes').value = c.notes||'';
    document.getElementById('editModal').classList.add('show');
}
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

document.querySelectorAll('.sc-modal').forEach(function(el) {
    el.addEventListener('click', function(e) { if(e.target===this) closeModal(this.id); });
});
document.getElementById('addModal').querySelector('form').addEventListener('submit', function() {
    combPhone('addP1Code','addP1Num','addP1Full');
    combPhone('addP2Code','addP2Num','addP2Full');
});
document.getElementById('editModal').querySelector('form').addEventListener('submit', function() {
    combPhone('editP1Code','editP1Num','editP1Full');
    combPhone('editP2Code','editP2Num','editP2Full');
});
if (new URLSearchParams(window.location.search).get('action')==='add') openAddModal();
</script>
