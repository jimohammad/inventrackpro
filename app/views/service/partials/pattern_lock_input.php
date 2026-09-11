<?php
/**
 * Interactive 3×3 pattern lock input.
 *
 * @var string $lockPatternValue  Existing pattern (comma-separated indices) or ''
 */
require_once __DIR__ . '/../../../helpers/ServiceLockPattern.php';

$lockPatternValue = ServiceLockPattern::parse($lockPatternValue ?? '') ?? '';
$inputId = 'svcPatternLockInput';
$gridId = 'svcPatternLockGrid';
?>
<style>
.svc-pattern-wrap {
    margin-top: 4px;
}
.svc-pattern-wrap .svc-pattern-hint {
    font-size: .72rem;
    color: var(--text-muted);
    margin-bottom: 10px;
    line-height: 1.4;
}
.svc-pattern-stage {
    position: relative;
    width: 220px;
    max-width: 100%;
    margin: 0 auto;
    touch-action: none;
    user-select: none;
}
.svc-pattern-stage svg {
    display: block;
    width: 100%;
    height: auto;
    color: var(--primary);
}
.svc-pattern-actions {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 10px;
}
.svc-pattern-clear {
    padding: 6px 14px;
    border-radius: 0;
    border: 1.5px solid var(--border-color);
    background: var(--bg-main);
    color: var(--text-muted);
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
}
.svc-pattern-clear:hover {
    border-color: #ef4444;
    color: #ef4444;
}
</style>

<div class="svc-f svc-pattern-wrap">
    <label>Screen lock pattern <span style="font-weight:500;text-transform:none;letter-spacing:0;">(optional — draw customer pattern)</span></label>
    <div class="svc-pattern-hint">Drag across the dots like on the phone. Stored for staff; printed only if you enable it below.</div>
    <input type="hidden" name="lock_pattern" id="<?= htmlspecialchars($inputId) ?>" value="<?= htmlspecialchars($lockPatternValue) ?>">
    <div class="svc-pattern-stage" id="<?= htmlspecialchars($gridId) ?>" aria-label="Draw screen lock pattern"></div>
    <div class="svc-pattern-actions">
        <button type="button" class="svc-pattern-clear" id="svcPatternClearBtn">Clear pattern</button>
    </div>
</div>

<script>
(function () {
    var GRID = 3;
    var SIZE = 220;
    var PAD = 36;
    var SPAN = SIZE - PAD * 2;
    var STEP = SPAN / (GRID - 1);
    var DOT_R = 11;
    var LINE_W = 4;

    var hidden = document.getElementById(<?= json_encode($inputId) ?>);
    var stage = document.getElementById(<?= json_encode($gridId) ?>);
    var clearBtn = document.getElementById('svcPatternClearBtn');
    if (!hidden || !stage) return;

    var path = [];
    var drawing = false;

    function coord(index) {
        var row = Math.floor(index / GRID);
        var col = index % GRID;
        return { x: PAD + col * STEP, y: PAD + row * STEP };
    }

    function indexAt(x, y) {
        for (var i = 0; i < 9; i++) {
            var c = coord(i);
            var dx = x - c.x;
            var dy = y - c.y;
            if (Math.sqrt(dx * dx + dy * dy) <= DOT_R + 8) return i;
        }
        return -1;
    }

    function intermediate(from, to) {
        var r1 = Math.floor(from / GRID), c1 = from % GRID;
        var r2 = Math.floor(to / GRID), c2 = to % GRID;
        var dr = r2 - r1, dc = c2 - c1;
        var steps = Math.max(Math.abs(dr), Math.abs(dc));
        if (steps <= 1) return [];
        if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) return [];
        var out = [];
        for (var s = 1; s < steps; s++) {
            out.push((r1 + Math.round(dr * s / steps)) * GRID + (c1 + Math.round(dc * s / steps)));
        }
        return out;
    }

    function contains(idx) {
        return path.indexOf(idx) !== -1;
    }

    function appendDot(idx) {
        if (idx < 0 || contains(idx)) return;
        if (path.length > 0) {
            var mids = intermediate(path[path.length - 1], idx);
            for (var m = 0; m < mids.length; m++) {
                if (!contains(mids[m])) path.push(mids[m]);
            }
        }
        if (!contains(idx)) path.push(idx);
        sync();
    }

    function sync() {
        hidden.value = path.length >= 2 ? path.join(',') : '';
        render();
    }

    function render() {
        var lines = '';
        for (var i = 0; i < path.length - 1; i++) {
            var a = coord(path[i]);
            var b = coord(path[i + 1]);
            lines += '<line x1="' + a.x + '" y1="' + a.y + '" x2="' + b.x + '" y2="' + b.y + '" stroke="currentColor" stroke-width="' + LINE_W + '" stroke-linecap="round"/>';
        }
        var circles = '';
        for (var d = 0; d < 9; d++) {
            var c = coord(d);
            var active = contains(d);
            if (active) {
                circles += '<circle cx="' + c.x + '" cy="' + c.y + '" r="' + DOT_R + '" fill="currentColor"/>';
            } else {
                circles += '<circle cx="' + c.x + '" cy="' + c.y + '" r="' + DOT_R + '" fill="none" stroke="currentColor" stroke-width="2.5"/>';
            }
        }
        stage.innerHTML = '<svg viewBox="0 0 ' + SIZE + ' ' + SIZE + '" xmlns="http://www.w3.org/2000/svg">' + lines + circles + '</svg>';
    }

    function pointerPos(evt) {
        var svg = stage.querySelector('svg');
        if (!svg) return null;
        var rect = svg.getBoundingClientRect();
        var clientX = evt.clientX;
        var clientY = evt.clientY;
        if (evt.touches && evt.touches[0]) {
            clientX = evt.touches[0].clientX;
            clientY = evt.touches[0].clientY;
        }
        var scaleX = SIZE / rect.width;
        var scaleY = SIZE / rect.height;
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function onDown(evt) {
        drawing = true;
        path = [];
        var pos = pointerPos(evt);
        if (!pos) return;
        appendDot(indexAt(pos.x, pos.y));
        evt.preventDefault();
    }

    function onMove(evt) {
        if (!drawing) return;
        var pos = pointerPos(evt);
        if (!pos) return;
        appendDot(indexAt(pos.x, pos.y));
        evt.preventDefault();
    }

    function onUp() {
        drawing = false;
    }

    function loadInitial() {
        var raw = (hidden.value || '').trim();
        if (!raw) {
            render();
            return;
        }
        path = raw.split(',').map(function (v) { return parseInt(v, 10); }).filter(function (n) {
            return !isNaN(n) && n >= 0 && n <= 8;
        });
        render();
    }

    stage.addEventListener('mousedown', onDown);
    stage.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
    stage.addEventListener('touchstart', onDown, { passive: false });
    stage.addEventListener('touchmove', onMove, { passive: false });
    stage.addEventListener('touchend', onUp);

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            path = [];
            sync();
        });
    }

    loadInitial();
})();
</script>
