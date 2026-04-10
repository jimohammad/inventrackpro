<!-- Backups -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">Database Backups</h1><p class="page-subtitle">Admin only</p></div>
    <a href="?page=backups&action=run" class="btn btn-success" onclick="return confirm('Create a new backup now?')">
        <i class="bi bi-cloud-arrow-down me-1"></i> Create Backup Now
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
        <p class="text-center text-muted py-5">No backups yet. Click "Create Backup Now" to start.</p>
        <?php else: ?>
        <table class="table mb-0">
            <thead>
                <tr><th>Filename</th><th>Size</th><th>Created By</th><th>Date</th><th>Download</th></tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $b): ?>
                <tr>
                    <td style="font-family:monospace;font-size:0.85rem;"><?= htmlspecialchars($b['filename']) ?></td>
                    <td><?= number_format($b['size_kb']) ?> KB</td>
                    <td><?= htmlspecialchars($b['created_by_name'] ?? '—') ?></td>
                    <td><?= date('d M Y H:i', strtotime($b['created_at'])) ?></td>
                    <td>
                        <a href="?page=backups&action=download&file=<?= urlencode($b['filename']) ?>"
                           class="btn btn-sm" style="background:rgba(16,185,129,0.15);color:var(--success);border:none;">
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
