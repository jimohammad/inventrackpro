<!-- Warehouses List -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Warehouses</h1>
        <p class="page-subtitle">Manage warehouse locations and user access</p>
    </div>
    <a href="?page=warehouses&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Warehouse
    </a>
</div>

<div class="row g-3">
    <?php foreach ($warehouses as $w): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100" style="border:1.5px solid <?= $w['is_active'] ? 'var(--border-color)' : 'rgba(239,68,68,0.3)' ?>;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:46px;height:46px;background:rgba(99,102,241,0.12);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-building" style="font-size:1.3rem;color:#6366f1;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:1rem;color:var(--text-main);">
                                <?= htmlspecialchars($w['name']) ?>
                                <?php if ($w['is_default']): ?>
                                <span class="badge ms-1" style="background:rgba(99,102,241,0.15);color:#6366f1;font-size:0.65rem;">DEFAULT</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($w['location']): ?>
                            <div style="font-size:0.78rem;color:var(--text-muted);">
                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($w['location']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge" style="border-radius:6px;<?= $w['is_active'] ? 'background:rgba(16,185,129,0.12);color:#10b981;' : 'background:rgba(239,68,68,0.12);color:#ef4444;' ?>">
                        <?= $w['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>

                <div class="d-flex gap-3 mb-3" style="font-size:0.82rem;color:var(--text-muted);">
                    <span><i class="bi bi-people me-1"></i><?= $w['user_count'] ?> users</span>
                    <?php if ($w['phone']): ?>
                    <span><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($w['phone']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <a href="?page=warehouses&action=edit&id=<?= $w['id'] ?>"
                       class="btn btn-sm flex-fill" style="background:rgba(99,102,241,0.12);color:#6366f1;border:none;">
                        <i class="bi bi-pencil me-1"></i> Edit & Users
                    </a>
                    <form method="POST" action="?page=warehouses&action=toggleStatus" style="display:inline;"
                          onsubmit="return confirm('Toggle status?')">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="id" value="<?= $w['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background:rgba(245,158,11,0.12);color:var(--warning);border:none;">
                            <i class="bi bi-toggle-on"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($warehouses)): ?>
    <div class="col-12 text-center py-5" style="color:var(--text-muted);">
        <i class="bi bi-building" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
        No warehouses yet. <a href="?page=warehouses&action=create">Create one</a>.
    </div>
    <?php endif; ?>
</div>
