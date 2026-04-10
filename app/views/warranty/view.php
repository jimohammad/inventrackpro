<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="?page=warranty" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="page-title"><?= htmlspecialchars($wr['replacement_no']) ?></span>
        <?php if ($wr['status'] === 'completed'): ?>
        <span class="badge ms-2 px-2 py-1" style="background:#dcfce7;color:#15803d;border-radius:6px;">Completed</span>
        <?php else: ?>
        <span class="badge ms-2 px-2 py-1" style="background:#fef3c7;color:#92400e;border-radius:6px;">Pending Supplier</span>
        <?php endif; ?>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer me-1"></i> Print
    </button>
</div>

<div class="row g-3">

    <!-- Left column -->
    <div class="col-md-8">

        <!-- Header info -->
        <div class="card mb-3" style="border-radius:10px;">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">CUSTOMER</p>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($wr['customer_name']) ?></p>
                        <?php if ($wr['customer_phone']): ?>
                        <p class="mb-0 text-muted" style="font-size:0.82rem;"><?= $wr['customer_phone'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">DATE</p>
                        <p class="mb-0"><?= date('d M Y', strtotime($wr['date'])) ?></p>
                    </div>
                    <div class="col-sm-4">
                        <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">BRANCH</p>
                        <p class="mb-0"><?= htmlspecialchars($wr['warehouse_name']) ?></p>
                    </div>
                    <?php if ($wr['sale_invoice_no']): ?>
                    <div class="col-sm-4">
                        <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">ORIGINAL SALE</p>
                        <a href="?page=sales&action=detail&id=<?= $wr['sale_id'] ?>" style="font-weight:600;color:#6366f1;">
                            <?= $wr['sale_invoice_no'] ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-4">
                        <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">RECORDED BY</p>
                        <p class="mb-0"><?= htmlspecialchars($wr['created_by_name'] ?? '—') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device comparison -->
        <div class="row g-3 mb-3">

            <!-- Faulty device -->
            <div class="col-md-6">
                <div class="card h-100" style="border:2px solid #fca5a5;border-radius:10px;">
                    <div class="card-header" style="background:#fef2f2;border-bottom:1px solid #fca5a5;border-radius:9px 9px 0 0;">
                        <span style="font-weight:700;color:#dc2626;font-size:0.85rem;">
                            <i class="bi bi-x-circle-fill me-1"></i> Faulty Device (Returned)
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2 fw-semibold"><?= htmlspecialchars($wr['old_item_name']) ?></p>
                        <?php if ($wr['old_sku']): ?>
                        <p class="mb-2 text-muted" style="font-size:0.8rem;"><?= $wr['old_sku'] ?></p>
                        <?php endif; ?>
                        <?php if ($wr['old_imei']): ?>
                        <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">IMEI</p>
                        <code style="background:#fef2f2;color:#dc2626;padding:4px 8px;border-radius:6px;font-size:0.85rem;display:block;margin-bottom:6px;">
                            <?= $wr['old_imei'] ?>
                        </code>
                        <?php if ($wr['old_imei2']): ?>
                        <code style="background:#fef2f2;color:#dc2626;padding:4px 8px;border-radius:6px;font-size:0.85rem;display:block;">
                            <?= $wr['old_imei2'] ?>
                        </code>
                        <?php endif; ?>
                        <?php endif; ?>
                        <span style="font-size:0.75rem;background:#fef2f2;color:#dc2626;border-radius:4px;padding:2px 6px;margin-top:8px;display:inline-block;">
                            Status: Defective
                        </span>
                    </div>
                </div>
            </div>

            <!-- Replacement device -->
            <div class="col-md-6">
                <div class="card h-100" style="border:2px solid #86efac;border-radius:10px;">
                    <div class="card-header" style="background:#f0fdf4;border-bottom:1px solid #86efac;border-radius:9px 9px 0 0;">
                        <span style="font-weight:700;color:#16a34a;font-size:0.85rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> Replacement Device (Given Out)
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2 fw-semibold"><?= htmlspecialchars($wr['new_item_name']) ?></p>
                        <?php if ($wr['new_sku']): ?>
                        <p class="mb-2 text-muted" style="font-size:0.8rem;"><?= $wr['new_sku'] ?></p>
                        <?php endif; ?>
                        <?php if ($wr['new_imei']): ?>
                        <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">IMEI</p>
                        <code style="background:#f0fdf4;color:#16a34a;padding:4px 8px;border-radius:6px;font-size:0.85rem;display:block;margin-bottom:6px;">
                            <?= $wr['new_imei'] ?>
                        </code>
                        <?php if ($wr['new_imei2']): ?>
                        <code style="background:#f0fdf4;color:#16a34a;padding:4px 8px;border-radius:6px;font-size:0.85rem;display:block;">
                            <?= $wr['new_imei2'] ?>
                        </code>
                        <?php endif; ?>
                        <?php endif; ?>
                        <span style="font-size:0.75rem;background:#f0fdf4;color:#16a34a;border-radius:4px;padding:2px 6px;margin-top:8px;display:inline-block;">
                            Status: Replaced / Sold
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Fault description -->
        <?php if ($wr['fault_description']): ?>
        <div class="card mb-3" style="border-radius:10px;border-left:4px solid #f59e0b;">
            <div class="card-body">
                <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">FAULT DESCRIPTION</p>
                <p class="mb-0"><?= nl2br(htmlspecialchars($wr['fault_description'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($wr['notes']): ?>
        <div class="card" style="border-radius:10px;">
            <div class="card-body">
                <p class="mb-1" style="font-size:0.75rem;color:#94a3b8;font-weight:600;">INTERNAL NOTES</p>
                <p class="mb-0"><?= nl2br(htmlspecialchars($wr['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right column — summary -->
    <div class="col-md-4">
        <div class="card" style="border-radius:10px;background:linear-gradient(135deg,#f8faff,#f0f4ff);">
            <div class="card-body">
                <h6 style="font-weight:700;color:#4338ca;margin-bottom:16px;font-size:0.88rem;">
                    <i class="bi bi-shield-check me-1"></i> Replacement Summary
                </h6>
                <div style="font-size:0.83rem;display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#64748b;">Ref No</span>
                        <strong style="color:#6366f1;"><?= $wr['replacement_no'] ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#64748b;">Date</span>
                        <span><?= date('d M Y', strtotime($wr['date'])) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#64748b;">Customer</span>
                        <span><?= htmlspecialchars($wr['customer_name']) ?></span>
                    </div>
                    <hr style="margin:4px 0;border-color:#e0e7ff;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#dc2626;">Faulty IMEI</span>
                        <code style="font-size:0.78rem;color:#dc2626;"><?= $wr['old_imei'] ?: '—' ?></code>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#16a34a;">New IMEI</span>
                        <code style="font-size:0.78rem;color:#16a34a;"><?= $wr['new_imei'] ?: '—' ?></code>
                    </div>
                    <hr style="margin:4px 0;border-color:#e0e7ff;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="color:#64748b;">Status</span>
                        <?php if ($wr['status'] === 'completed'): ?>
                        <span style="background:#dcfce7;color:#15803d;border-radius:6px;padding:2px 10px;font-weight:700;font-size:0.78rem;">✅ Completed</span>
                        <?php else: ?>
                        <span style="background:#fef3c7;color:#92400e;border-radius:6px;padding:2px 10px;font-weight:700;font-size:0.78rem;">⏳ Pending</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:#64748b;">Cost to Business</span>
                        <strong style="color:#1e293b;">0.000 KWD</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
