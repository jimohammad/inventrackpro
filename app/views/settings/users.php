<!-- User Management -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="page-title">User Management</h1><p class="page-subtitle">Admin only</p></div>
    <a href="?page=users&action=create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New User</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge px-2" style="border-radius:5px;background:rgba(99,102,241,0.15);color:var(--primary);">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td><small class="text-muted"><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : 'Never' ?></small></td>
                    <td>
                        <span class="badge <?= $u['is_active'] ? 'badge-paid' : 'badge-draft' ?> px-2" style="border-radius:5px;">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <a href="?page=users&action=edit&id=<?= $u['id'] ?>"
                           class="btn btn-sm me-1" style="background:rgba(99,102,241,0.12);color:#6366f1;border:none;">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if ($u['id'] !== Auth::id()): ?>
                        <form method="POST" action="?page=users&action=toggleStatus" style="display:inline;" onsubmit="return confirm('Toggle status for this user?')">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:rgba(245,158,11,0.15);color:var(--warning);border:none;">
                                <i class="bi bi-toggle-on"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
