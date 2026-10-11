<?php
    $holders = $holders ?? [];
    $history = $history ?? [];
    $cashWaiting = (float) ($cashWaiting ?? 0);
    $bankWaiting = (float) ($bankWaiting ?? 0);
    $money = static function (float $n): string {
        return APP_CURRENCY . ' ' . number_format($n, DECIMAL_PLACES);
    };
    $roleLabel = static function (string $role): string {
        return $role === 'manager' ? 'Manager' : 'Salesman';
    };
?>
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <div>
        <h1 class="page-title mb-1">Collect from salesman</h1>
        <p class="mb-0" style="color:#64748b;font-size:0.88rem;">
            Tick the receipts you took. Cash and bank balances stay as they were when the receipt was saved.
        </p>
    </div>
    <a href="?page=payments" class="btn btn-outline-secondary">Payment In</a>
</div>

<div class="d-flex flex-wrap gap-3 mb-4">
    <div class="card mb-0" style="border:none;background:#ecfdf5;min-width:200px;">
        <div class="card-body py-3">
            <div style="font-size:0.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#047857;">Cash to take</div>
            <div style="font-size:1.35rem;font-weight:800;color:#065f46;"><?= $money($cashWaiting) ?></div>
        </div>
    </div>
    <div class="card mb-0" style="border:none;background:#eff6ff;min-width:200px;">
        <div class="card-body py-3">
            <div style="font-size:0.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#1d4ed8;">Bank to confirm</div>
            <div style="font-size:1.35rem;font-weight:800;color:#1e3a8a;"><?= $money($bankWaiting) ?></div>
        </div>
    </div>
</div>

<?php if ($holders === []): ?>
<div class="card" style="border:none;">
    <div class="card-body" style="color:#475569;">
        Nothing is waiting. Every salesman and manager receipt is already with you.
        Receipts saved before this page was first opened are already counted as with you.
    </div>
</div>
<?php endif; ?>

<?php foreach ($holders as $holder): ?>
<?php $formId = 'collect-holder-' . (int) $holder['user_id']; ?>
<form method="POST" action="?page=payments&action=collectStore" class="card mb-4" style="border:none;" id="<?= $formId ?>">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="holder_user_id" value="<?= (int) $holder['user_id'] ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1"><?= htmlspecialchars((string) $holder['name']) ?></h2>
                <div style="color:#64748b;font-size:0.82rem;"><?= htmlspecialchars($roleLabel((string) $holder['role'])) ?></div>
            </div>
            <div style="text-align:right;font-size:0.88rem;">
                <div>Cash <strong><?= $money((float) $holder['cash_total']) ?></strong></div>
                <div>Bank <strong><?= $money((float) $holder['bank_total']) ?></strong></div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-2">
            <label class="collect-pick">
                <input type="checkbox" class="form-check-input js-collect-everything m-0">
                Select all
            </label>
            <?php if (($holder['cash'] ?? []) !== []): ?>
            <label class="collect-pick collect-pick-cash">
                <input type="checkbox" class="form-check-input js-collect-bucket m-0" data-bucket="cash">
                All cash
            </label>
            <?php endif; ?>
            <?php if (($holder['bank'] ?? []) !== []): ?>
            <label class="collect-pick collect-pick-bank">
                <input type="checkbox" class="form-check-input js-collect-bucket m-0" data-bucket="bank">
                All bank
            </label>
            <?php endif; ?>
        </div>

        <?php foreach (['cash' => 'Cash', 'bank' => 'Bank'] as $bucket => $label): ?>
        <?php if (($holder[$bucket] ?? []) === []) continue; ?>
        <h3 class="h6 mt-3 mb-2"><?= $label ?></h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:42px;"></th>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Account</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holder[$bucket] as $row): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input js-collect-row" data-bucket="<?= $bucket ?>" name="payment_ids[]" value="<?= (int) $row['id'] ?>">
                        </td>
                        <td>
                            <a href="?page=payments&action=detail&id=<?= (int) $row['id'] ?>"><?= htmlspecialchars((string) $row['payment_no']) ?></a>
                        </td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime((string) $row['date']))) ?></td>
                        <td><?= htmlspecialchars((string) ($row['party_name'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['account_name'] ?? '—')) ?></td>
                        <td class="text-end" style="font-weight:700;"><?= $money((float) $row['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
            <input type="text" name="note" maxlength="255" placeholder="Note (optional)"
                   style="height:42px;padding:0 12px;border:1px solid #cbd5e1;min-width:220px;">
            <button type="submit" class="btn btn-success">I collected these</button>
        </div>
    </div>
</form>
<?php endforeach; ?>

<h2 class="h5 mt-4 mb-3">Recent collections</h2>
<?php if ($history === []): ?>
<p style="color:#64748b;">No collections yet.</p>
<?php else: ?>
<div class="card" style="border:none;">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>When</th>
                    <th>From</th>
                    <th>Admin</th>
                    <th class="text-end">Cash</th>
                    <th class="text-end">Bank</th>
                    <th>Receipts</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row): ?>
                <?php $undone = !empty($row['undone_at']); ?>
                <tr style="<?= $undone ? 'color:#94a3b8;' : '' ?>">
                    <td><?= htmlspecialchars(date('d M Y H:i', strtotime((string) $row['collected_at']))) ?></td>
                    <td><?= htmlspecialchars((string) ($row['holder_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['admin_name'] ?? '')) ?></td>
                    <td class="text-end"><?= $money((float) $row['cash_total']) ?></td>
                    <td class="text-end"><?= $money((float) $row['bank_total']) ?></td>
                    <td>
                        <?= (int) $row['receipt_count'] ?>
                        <?php if ((int) ($row['dropped_count'] ?? 0) > 0): ?>
                        <span class="badge bg-warning text-dark"><?= (int) $row['dropped_count'] ?> cancelled later</span>
                        <?php endif; ?>
                        <?php if ($undone): ?>
                        <span class="badge bg-secondary">Undone</span>
                        <?php endif; ?>
                        <?php if (!empty($row['note'])): ?>
                        <div style="font-size:0.75rem;"><?= htmlspecialchars((string) $row['note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if (!empty($row['same_day'])): ?>
                        <form method="POST" action="?page=payments&action=collectUndo" class="d-inline" onsubmit="return confirm('Put these receipts back with the salesman?');">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Undo</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
.collect-pick {
    display:inline-flex; align-items:center; gap:6px; margin:0; cursor:pointer;
    border:1px solid #cbd5e1; background:#fff; padding:4px 10px;
    font-size:0.82rem; font-weight:700; color:#334155; user-select:none;
}
.collect-pick-cash { color:#047857; border-color:#a7f3d0; background:#ecfdf5; }
.collect-pick-bank { color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff; }
</style>
<script>
document.querySelectorAll('form[id^="collect-holder-"]').forEach(function (form) {
    function rows(bucket) {
        var sel = '.js-collect-row';
        if (bucket) {
            sel += '[data-bucket="' + bucket + '"]';
        }
        return form.querySelectorAll(sel);
    }
    function everyChecked(list) {
        if (!list.length) {
            return false;
        }
        for (var i = 0; i < list.length; i++) {
            if (!list[i].checked) {
                return false;
            }
        }
        return true;
    }
    function anyChecked(list) {
        for (var i = 0; i < list.length; i++) {
            if (list[i].checked) {
                return true;
            }
        }
        return false;
    }
    function paint(box, list) {
        if (!box) {
            return;
        }
        var all = everyChecked(list);
        var some = anyChecked(list);
        box.checked = all;
        box.indeterminate = some && !all;
    }
    function syncMasters() {
        form.querySelectorAll('.js-collect-bucket').forEach(function (box) {
            paint(box, rows(box.getAttribute('data-bucket')));
        });
        paint(form.querySelector('.js-collect-everything'), rows());
    }
    var everything = form.querySelector('.js-collect-everything');
    if (everything) {
        everything.addEventListener('change', function () {
            rows().forEach(function (box) {
                box.checked = everything.checked;
            });
            syncMasters();
        });
    }
    form.querySelectorAll('.js-collect-bucket').forEach(function (all) {
        all.addEventListener('change', function () {
            rows(all.getAttribute('data-bucket')).forEach(function (box) {
                box.checked = all.checked;
            });
            syncMasters();
        });
    });
    form.querySelectorAll('.js-collect-row').forEach(function (box) {
        box.addEventListener('change', syncMasters);
    });
    form.addEventListener('submit', function (ev) {
        if (!form.querySelector('.js-collect-row:checked')) {
            ev.preventDefault();
            alert('Select at least one receipt.');
        }
    });
});
</script>
