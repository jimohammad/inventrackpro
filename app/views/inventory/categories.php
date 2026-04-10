<style>
.cat-page { max-width: 900px; }

.cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.cat-tile {
    background: var(--bg-card);
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    padding: 16px;
    position: relative;
    transition: border-color 0.2s, transform 0.15s;
    cursor: default;
}
.cat-tile:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}
.cat-tile-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cat-tile-icon {
    width: 28px; height: 28px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.cat-tile-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: var(--text-muted);
}
.cat-count {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(99,102,241,0.1);
    color: var(--primary);
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.72rem;
}
.cat-parent-tag {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: rgba(245,158,11,0.1);
    color: #d97706;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.7rem;
}
.cat-desc {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cat-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 2px;
    opacity: 0;
    transition: opacity 0.15s;
}
.cat-tile:hover .cat-actions { opacity: 1; }
.cat-act {
    width: 26px; height: 26px;
    border-radius: 6px;
    border: none;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    font-size: 0.78rem;
    transition: all 0.15s;
}
.cat-act.edit { background: rgba(59,130,246,0.1); color: #3b82f6; }
.cat-act.edit:hover { background: rgba(59,130,246,0.2); }
.cat-act.del { background: rgba(239,68,68,0.1); color: #ef4444; }
.cat-act.del:hover { background: rgba(239,68,68,0.2); }

/* Add tile */
.cat-add-tile {
    background: transparent;
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--text-muted);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 90px;
}
.cat-add-tile:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(99,102,241,0.04);
}

/* Color bands for tiles */
.cat-tile::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 12px 12px 0 0;
    background: var(--tile-color, var(--primary));
}

/* Modal */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: none; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.modal-overlay.show { display: flex; }
.modal-box {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 24px;
    width: 100%; max-width: 400px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-title {
    font-size: 1rem; font-weight: 700;
    color: var(--text-main);
    margin-bottom: 18px;
    display: flex; justify-content: space-between; align-items: center;
}
.modal-title .close-x {
    background: none; border: none;
    color: var(--text-muted); font-size: 1.3rem;
    cursor: pointer; line-height: 1;
}
.modal-label {
    font-size: 0.75rem; font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 4px; display: block;
    text-transform: uppercase; letter-spacing: 0.4px;
}
.modal-input {
    width: 100%;
    background: var(--bg-main);
    border: 1.5px solid var(--border-color);
    color: var(--text-main);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.85rem;
    outline: none;
    transition: border-color 0.15s;
    margin-bottom: 12px;
}
.modal-input:focus { border-color: var(--primary); }
.modal-footer {
    display: flex; justify-content: flex-end; gap: 8px;
    margin-top: 4px;
}
.modal-footer .btn-cancel {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-muted);
    padding: 6px 16px; border-radius: 8px;
    cursor: pointer; font-size: 0.82rem;
}
.modal-footer .btn-submit {
    background: var(--primary); border: none; color: #fff;
    padding: 6px 20px; border-radius: 8px;
    cursor: pointer; font-size: 0.82rem; font-weight: 600;
}
.modal-footer .btn-submit:hover { background: var(--primary-hover); }

/* Stats bar */
.cat-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    font-size: 0.82rem;
    color: var(--text-muted);
}
.cat-stats strong { color: var(--primary); }
</style>

<?php
$tileColors = ['#6366f1','#3b82f6','#0891b2','#059669','#d97706','#dc2626','#8b5cf6','#ec4899','#f59e0b','#0ea5e9'];
$totalItems = array_sum(array_column($categories, 'item_count'));
?>

<div class="cat-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="page-title mb-0">Categories</h4>
            <div class="cat-stats mt-1">
                <span><strong><?= count($categories) ?></strong> categories</span>
                <span><strong><?= $totalItems ?></strong> total items</span>
            </div>
        </div>
    </div>

    <div class="cat-grid">
        <?php foreach ($categories as $i => $cat): ?>
        <div class="cat-tile" style="--tile-color: <?= $tileColors[$i % count($tileColors)] ?>;">
            <div class="cat-actions">
                <button class="cat-act edit"
                    onclick="openEditModal(<?= $cat['id'] ?>, '<?= addslashes(htmlspecialchars($cat['name'])) ?>', <?= $cat['parent_id'] ?: 'null' ?>, '<?= addslashes(htmlspecialchars($cat['description'] ?? '')) ?>')"
                    title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="?page=categories&action=delete" style="display:inline;"
                      onsubmit="return confirm('Delete this category?')">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="cat-act del" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
            <div class="cat-tile-name">
                <div class="cat-tile-icon" style="background:<?= $tileColors[$i % count($tileColors)] ?>20;color:<?= $tileColors[$i % count($tileColors)] ?>;">
                    <i class="bi bi-tag-fill"></i>
                </div>
                <?= htmlspecialchars($cat['name']) ?>
            </div>
            <div class="cat-tile-meta">
                <span class="cat-count"><i class="bi bi-box"></i> <?= $cat['item_count'] ?></span>
                <?php if ($cat['parent_name']): ?>
                <span class="cat-parent-tag"><i class="bi bi-diagram-2"></i> <?= htmlspecialchars($cat['parent_name']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($cat['description'])): ?>
            <div class="cat-desc"><?= htmlspecialchars($cat['description']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Add new tile -->
        <div class="cat-add-tile" onclick="openAddModal()">
            <i class="bi bi-plus-lg"></i> Add Category
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div class="modal-title">
            <span><i class="bi bi-tag me-2" style="color:var(--primary);"></i>New Category</span>
            <button class="close-x" onclick="closeModal('addModal')">×</button>
        </div>
        <form method="POST" action="?page=categories&action=store">
            <?= Auth::csrfField() ?>
            <label class="modal-label">Name *</label>
            <input type="text" name="name" class="modal-input" placeholder="e.g. Smartphones" required>
            <label class="modal-label">Parent (optional)</label>
            <select name="parent_id" class="modal-input" style="cursor:pointer;">
                <option value="">— None —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="modal-label">Description (optional)</label>
            <input type="text" name="description" class="modal-input" placeholder="Short description...">
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn-submit">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-title">
            <span><i class="bi bi-pencil me-2" style="color:var(--primary);"></i>Edit Category</span>
            <button class="close-x" onclick="closeModal('editModal')">×</button>
        </div>
        <form method="POST" action="?page=categories&action=update">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" id="editId">
            <label class="modal-label">Name *</label>
            <input type="text" name="name" id="editName" class="modal-input" required>
            <label class="modal-label">Parent (optional)</label>
            <select name="parent_id" id="editParent" class="modal-input" style="cursor:pointer;">
                <option value="">— None —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="modal-label">Description (optional)</label>
            <input type="text" name="description" id="editDesc" class="modal-input">
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-submit">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.add('show');
    document.querySelector('#addModal input[name="name"]').focus();
}
function openEditModal(id, name, parentId, desc) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editDesc').value = desc;
    var sel = document.getElementById('editParent');
    sel.value = parentId || '';
    Array.from(sel.options).forEach(function(opt) { opt.disabled = (parseInt(opt.value) === id); });
    document.getElementById('editModal').classList.add('show');
    document.getElementById('editName').focus();
}
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
});
</script>
