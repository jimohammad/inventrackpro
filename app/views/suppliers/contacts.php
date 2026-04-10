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
.sc-modal { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px); }
.sc-modal.show { display:flex; }
.sc-modal-box { background:var(--bg-card);border:1px solid var(--border-color);border-radius:16px;width:100%;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,.3);max-height:90vh;overflow-y:auto; }
.sc-modal-head { padding:20px 24px 0;display:flex;justify-content:space-between;align-items:center; }
.sc-modal-head h3 { font-size:1.05rem;font-weight:700;color:var(--text-main);margin:0;display:flex;align-items:center;gap:8px; }
.sc-modal-head .close-x { background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1; }
.sc-modal-body { padding:16px 24px 20px; }
.sc-modal-foot { padding:0 24px 20px;display:flex;justify-content:flex-end;gap:8px; }

.sc-sep { font-size:.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.8px;margin:14px 0 8px;padding-bottom:4px;border-bottom:1.5px solid rgba(99,102,241,.15);display:flex;align-items:center;gap:6px; }
.sc-sep:first-child { margin-top:0; }
.sc-sep i { font-size:.8rem; }

.sc-f { margin-bottom:8px; }
.sc-f label { display:block;font-size:.7rem;font-weight:600;color:var(--text-muted);margin-bottom:2px;text-transform:uppercase;letter-spacing:.3px; }
.sc-f input,.sc-f select,.sc-f textarea { width:100%;padding:7px 10px;border:1.5px solid var(--border-color);border-radius:8px;font-size:.84rem;background:var(--bg-main);color:var(--text-main);outline:none;font-family:inherit; }
.sc-f input:focus,.sc-f select:focus,.sc-f textarea:focus { border-color:var(--primary); }
.sc-r2 { display:grid;grid-template-columns:1fr 1fr;gap:8px; }
.sc-r3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px; }

.sc-phone { display:flex; }
.sc-phone select { width:86px;border-radius:8px 0 0 8px;border-right:none;padding:7px 2px 7px 8px;font-size:.8rem;flex-shrink:0; }
.sc-phone input { border-radius:0 8px 8px 0;flex:1;min-width:0; }

.sc-person-block { background:var(--bg-main);border:1px solid var(--border-color);border-radius:10px;padding:12px;margin-bottom:8px; }
.sc-person-num { font-size:.7rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px; }

.sc-btn-cancel { padding:8px 18px;background:transparent;border:1px solid var(--border-color);color:var(--text-muted);border-radius:8px;cursor:pointer;font-size:.82rem; }
.sc-btn-save { padding:8px 24px;background:var(--primary);border:none;color:#fff;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:600; }
.sc-btn-save:hover { opacity:.9; }
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
            <h3><i class="bi bi-plus-circle" style="color:var(--primary);"></i> New Supplier</h3>
            <button class="close-x" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" action="?page=suppliercontacts&action=store">
            <?= Auth::csrfField() ?>
            <div class="sc-modal-body">
                <div class="sc-sep"><i class="bi bi-building"></i> Company</div>
                <div class="sc-f"><label>Company Name *</label><input type="text" name="company_name" id="addCompany" required placeholder="e.g. Samsung Gulf FZE"></div>
                <div class="sc-r2">
                    <div class="sc-f"><label>Country *</label><select name="country" id="addCountry"><?php foreach($countryOrder as $co): ?><option value="<?=$co?>"><?=$co?></option><?php endforeach; ?></select></div>
                    <div class="sc-f"><label>Product Type *</label><select name="product_type" id="addType"><option value="Mobile Phones">Mobile Phones</option><option value="Accessories">Accessories</option><option value="Tablets">Tablets</option><option value="Mixed">Mixed</option></select></div>
                </div>
                <div class="sc-r2">
                    <div class="sc-f"><label>Email</label><input type="email" name="email" id="addEmail" placeholder="company@email.com"></div>
                    <div class="sc-f"><label>Address</label><input type="text" name="address" id="addAddress" placeholder="Office / warehouse"></div>
                </div>

                <div class="sc-sep"><i class="bi bi-person"></i> Contact Person 1</div>
                <div class="sc-person-block">
                    <div class="sc-f"><label>Name</label><input type="text" name="contact_person" id="addP1Name" placeholder="Name / designation"></div>
                    <div class="sc-r2">
                        <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="addP1Code"><?= $phoneOptions ?></select><input type="text" id="addP1Num" placeholder="50 xxx xxxx"></div><input type="hidden" name="mobile" id="addP1Full"></div>
                        <div class="sc-f"><label>WeChat</label><input type="text" name="wechat" id="addP1Wechat" placeholder="WeChat ID"></div>
                    </div>
                </div>

                <div class="sc-sep"><i class="bi bi-person"></i> Contact Person 2</div>
                <div class="sc-person-block">
                    <div class="sc-f"><label>Name</label><input type="text" name="contact_person_2" id="addP2Name" placeholder="Name / designation"></div>
                    <div class="sc-r2">
                        <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="addP2Code"><?= $phoneOptions ?></select><input type="text" id="addP2Num" placeholder="50 xxx xxxx"></div><input type="hidden" name="mobile_2" id="addP2Full"></div>
                        <div class="sc-f"><label>WeChat</label><input type="text" name="wechat_2" id="addP2Wechat" placeholder="WeChat ID"></div>
                    </div>
                </div>

                <div class="sc-sep"><i class="bi bi-journal-text"></i> Notes</div>
                <div class="sc-f"><textarea name="notes" id="addNotes" rows="2" placeholder="Payment terms, remarks..."></textarea></div>
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
            <h3><i class="bi bi-pencil" style="color:var(--primary);"></i> Edit Supplier</h3>
            <button class="close-x" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" action="?page=suppliercontacts&action=update">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" id="editId">
            <div class="sc-modal-body">
                <div class="sc-sep"><i class="bi bi-building"></i> Company</div>
                <div class="sc-f"><label>Company Name *</label><input type="text" name="company_name" id="editCompany" required></div>
                <div class="sc-r2">
                    <div class="sc-f"><label>Country *</label><select name="country" id="editCountry"><?php foreach($countryOrder as $co): ?><option value="<?=$co?>"><?=$co?></option><?php endforeach; ?></select></div>
                    <div class="sc-f"><label>Product Type *</label><select name="product_type" id="editType"><option value="Mobile Phones">Mobile Phones</option><option value="Accessories">Accessories</option><option value="Tablets">Tablets</option><option value="Mixed">Mixed</option></select></div>
                </div>
                <div class="sc-r2">
                    <div class="sc-f"><label>Email</label><input type="email" name="email" id="editEmail"></div>
                    <div class="sc-f"><label>Address</label><input type="text" name="address" id="editAddress"></div>
                </div>

                <div class="sc-sep"><i class="bi bi-person"></i> Contact Person 1</div>
                <div class="sc-person-block">
                    <div class="sc-f"><label>Name</label><input type="text" name="contact_person" id="editP1Name"></div>
                    <div class="sc-r2">
                        <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="editP1Code"><?= $phoneOptions ?></select><input type="text" id="editP1Num"></div><input type="hidden" name="mobile" id="editP1Full"></div>
                        <div class="sc-f"><label>WeChat</label><input type="text" name="wechat" id="editP1Wechat"></div>
                    </div>
                </div>

                <div class="sc-sep"><i class="bi bi-person"></i> Contact Person 2</div>
                <div class="sc-person-block">
                    <div class="sc-f"><label>Name</label><input type="text" name="contact_person_2" id="editP2Name"></div>
                    <div class="sc-r2">
                        <div class="sc-f"><label>Mobile</label><div class="sc-phone"><select id="editP2Code"><?= $phoneOptions ?></select><input type="text" id="editP2Num"></div><input type="hidden" name="mobile_2" id="editP2Full"></div>
                        <div class="sc-f"><label>WeChat</label><input type="text" name="wechat_2" id="editP2Wechat"></div>
                    </div>
                </div>

                <div class="sc-sep"><i class="bi bi-journal-text"></i> Notes</div>
                <div class="sc-f"><textarea name="notes" id="editNotes" rows="2"></textarea></div>
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
