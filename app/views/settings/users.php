<!-- User Management -->
<style>
.um-actions { display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; }
.um-edit {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.4rem; height: 1.4rem; border-radius: 0.3rem;
    background: rgba(99,102,241,0.12); color: #6366f1; border: none;
    text-decoration: none;
    flex-shrink: 0;
}
.um-edit i { font-size: 0.7rem; line-height: 1; }
.um-edit:hover { background: rgba(99,102,241,0.22); color: #4f46e5; }
/* Large switch-style submit (real POST, no inline handlers) */
.um-toggle-form { display: inline-block; margin: 0; vertical-align: middle; }
.um-toggle {
    position: relative;
    width: 2.6rem;
    height: 1.35rem;
    border-radius: 999px;
    border: 1px solid rgba(100,116,139,0.4);
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
    transition: background 0.2s, border-color 0.2s;
}
.um-toggle.is-on {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-color: rgba(22,163,74,0.6);
}
.um-toggle.is-off {
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    border-color: rgba(100,116,139,0.45);
}
.um-toggle:focus { outline: 2px solid rgba(99,102,241,0.45); outline-offset: 2px; }
.um-toggle-knob {
    position: absolute;
    top: 2px;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(15,23,42,0.22);
    transition: left 0.2s ease;
}
.um-toggle.is-on .um-toggle-knob { left: calc(100% - 1rem - 2px); }
.um-toggle.is-off .um-toggle-knob { left: 2px; }
.um-self-note { font-size: 0.75rem; color: var(--text-muted); }
</style>

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
                <?php foreach ($users as $u):
                    $uid    = (int) ($u['id'] ?? 0);
                    $active = (int) (!empty($u['is_active']));
                    $isSelf = $uid > 0 && $uid === (int) Auth::id();
                    ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars((string) ($u['name'] ?? '')) ?></td>
                    <td class="text-muted"><?= htmlspecialchars((string) ($u['email'] ?? '')) ?></td>
                    <td>
                        <span class="badge px-2" style="border-radius:5px;background:rgba(99,102,241,0.15);color:var(--primary);">
                            <?= ucfirst((string) ($u['role'] ?? '')) ?>
                        </span>
                    </td>
                    <td><small class="text-muted"><?= !empty($u['last_login']) ? date('d M Y H:i', strtotime((string) $u['last_login'])) : 'Never' ?></small></td>
                    <td>
                        <span class="badge <?= $active ? 'badge-paid' : 'badge-draft' ?> px-2" style="border-radius:5px;">
                            <?= $active ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="um-actions">
                            <a href="?page=users&action=edit&id=<?= $uid ?>" class="um-edit" title="Edit user">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($isSelf): ?>
                            <span class="um-self-note" title="Use another admin account to change your status.">—</span>
                            <?php else: ?>
                            <form method="POST" action="?page=users&action=toggleStatus" class="um-toggle-form js-um-toggle-form"
                                  data-confirm="<?= $active ? 'Deactivate this user? They will not be able to log in.' : 'Activate this user?' ?>">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= $uid ?>">
                                <button type="submit" class="um-toggle <?= $active ? 'is-on' : 'is-off' ?>"
                                        title="<?= $active ? 'Click to deactivate' : 'Click to activate' ?>"
                                        aria-label="<?= $active ? 'Deactivate user' : 'Activate user' ?>">
                                    <span class="um-toggle-knob" aria-hidden="true"></span>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.js-um-toggle-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var msg = form.getAttribute('data-confirm') || 'Change this user’s active status?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });
})();
</script>
