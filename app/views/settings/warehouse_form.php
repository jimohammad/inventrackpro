<!-- Warehouse Form -->
<style>
.user-chip {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.25);
    border-radius:20px; padding:5px 12px 5px 8px;
    font-size:0.82rem; color:var(--text-main); font-weight:600;
}
.user-chip .chip-avatar {
    width:24px;height:24px;border-radius:50%;
    background:#6366f1;color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-size:0.7rem;font-weight:700;flex-shrink:0;
}
.user-chip .chip-remove {
    color:#ef4444;cursor:pointer;font-size:0.85rem;
    text-decoration:none;opacity:0.7;
}
.user-chip .chip-remove:hover { opacity:1; }
.role-badge {
    font-size:0.68rem;padding:2px 7px;border-radius:4px;
    background:rgba(99,102,241,0.1);color:#6366f1;font-weight:600;
}
</style>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="?page=warehouses" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= isset($editMode) ? 'Edit Warehouse' : 'New Warehouse' ?></h1>
</div>

<div class="row g-4">

    <!-- LEFT: Warehouse Details -->
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header" style="font-weight:700;font-size:0.875rem;">
                <i class="bi bi-building me-2" style="color:#6366f1;"></i>Warehouse Details
            </div>
            <div class="card-body">
                <form method="POST" action="?page=warehouses&action=<?= isset($editMode) ? 'update&id='.$warehouse['id'] : 'store' ?>">
    <?= Auth::csrfField() ?>
                    <?php if (isset($editMode)): ?>
                    <input type="hidden" name="id" value="<?= $warehouse['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;font-size:0.82rem;">Warehouse Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= htmlspecialchars($warehouse['name'] ?? '') ?>" placeholder="e.g. Main Warehouse">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;font-size:0.82rem;">Location / Address</label>
                        <input type="text" name="location" class="form-control"
                               value="<?= htmlspecialchars($warehouse['location'] ?? '') ?>" placeholder="e.g. Salmiya, Block 12">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;font-size:0.82rem;">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($warehouse['phone'] ?? '') ?>" placeholder="+965 XXXX XXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;font-size:0.82rem;">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Optional notes..."><?= htmlspecialchars($warehouse['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1"
                                   id="isDefaultCheck" <?= ($warehouse['is_default'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isDefaultCheck" style="font-weight:600;font-size:0.85rem;">
                                Set as Default Warehouse
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check-lg me-1"></i>
                            <?= isset($editMode) ? 'Save Changes' : 'Create Warehouse' ?>
                        </button>
                        <a href="?page=warehouses" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT: User Assignments (only in edit mode) -->
    <?php if (isset($editMode)): ?>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between" style="font-weight:700;font-size:0.875rem;">
                <span><i class="bi bi-people me-2" style="color:#6366f1;"></i>Assigned Users</span>
                <span class="badge" style="background:rgba(99,102,241,0.12);color:#6366f1;"><?= count($assignedUsers) ?> users</span>
            </div>
            <div class="card-body">

                <!-- Add user form -->
                <?php if (!empty($availableUsers)): ?>
                <form method="POST" action="?page=warehouses&action=assignUser" class="d-flex gap-2 mb-4">
    <?= Auth::csrfField() ?>
                    <input type="hidden" name="warehouse_id" value="<?= $warehouse['id'] ?>">
                    <select name="user_id" class="form-select form-select-sm" required>
                        <option value="">Select user to assign...</option>
                        <?php foreach ($availableUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> — <?= ucfirst($u['role']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary" style="white-space:nowrap;">
                        <i class="bi bi-plus-lg me-1"></i>Assign
                    </button>
                </form>
                <?php else: ?>
                <div class="mb-4" style="font-size:0.82rem;color:var(--text-muted);">All users are already assigned to this warehouse.</div>
                <?php endif; ?>

                <!-- Assigned users list -->
                <?php if (!empty($assignedUsers)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    <?php foreach ($assignedUsers as $u): ?>
                    <div class="user-chip">
                        <div class="chip-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                        <div>
                            <div><?= htmlspecialchars($u['name']) ?></div>
                            <span class="role-badge"><?= ucfirst($u['role']) ?></span>
                        </div>
                        <form method="POST" action="?page=warehouses&action=removeUser" style="display:inline;"
                              onsubmit="return confirm('Remove <?= htmlspecialchars($u['name']) ?> from this warehouse?')">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="warehouse_id" value="<?= $warehouse['id'] ?>">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="chip-remove" style="background:none;border:none;cursor:pointer;">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4" style="color:var(--text-muted);">
                    <i class="bi bi-person-x" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
                    No users assigned yet. Use the dropdown above to add users.
                </div>
                <?php endif; ?>

                <div class="mt-4 p-3" style="background:rgba(99,102,241,0.06);border-radius:10px;border:1px solid rgba(99,102,241,0.15);">
                    <div style="font-size:0.78rem;color:var(--text-muted);">
                        <i class="bi bi-info-circle me-1"></i>
                        Assigned users can pick this warehouse at login. If a user is not assigned here, this warehouse won't appear in their selector.
                        <strong style="color:var(--text-main);">Admins always see all warehouses.</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-md-7">
        <div class="card" style="border-style:dashed;opacity:0.6;">
            <div class="card-body text-center py-5" style="color:var(--text-muted);">
                <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>
                <div style="font-weight:600;">User assignments available after creating the warehouse.</div>
                <div style="font-size:0.82rem;margin-top:0.5rem;">Save the warehouse first, then you can assign users to it.</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
