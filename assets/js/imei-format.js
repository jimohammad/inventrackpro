/**
 * Phone IMEI (digits) vs tablet carton serial (alphanumeric, e.g. Samsung R8YL60BBJBD).
 * Keep in sync with app/helpers/ImeiFormat.php
 */
(function (w) {
    function normalize(raw) {
        return String(raw || '').toUpperCase().replace(/[\r\n\t\s]/g, '').replace(/[^A-Z0-9]/g, '');
    }

    function resolveKind(kind, name, category) {
        var k = String(kind || '').toLowerCase();
        if (k === 'tablet') return 'tablet';
        var blob = (String(name || '') + ' ' + String(category || '')).toLowerCase();
        if (blob.indexOf('tablet') !== -1 || blob.indexOf('galaxy tab') !== -1) return 'tablet';
        return 'phone';
    }

    function rule(kind, name, category, opts) {
        opts = opts || {};
        var resolved = resolveKind(kind, name, category);
        if (resolved === 'tablet') {
            return {
                kind: 'tablet',
                min: 11,
                max: 20,
                label: '11–20 letters/numbers',
                test: function (v) {
                    var s = normalize(v);
                    return /^[A-Z0-9]{11,20}$/.test(s) && /[A-Z]/.test(s);
                }
            };
        }
        var n = String(name || '').toLowerCase();
        var cat = String(category || '').toLowerCase();
        if (n.indexOf('h40') !== -1) {
            return {
                kind: 'phone',
                min: 13,
                max: 13,
                label: '13 digits',
                test: function (v) { return /^\d{13}$/.test(normalize(v)); }
            };
        }
        var min = opts.phoneMin != null ? opts.phoneMin : 15;
        var max = opts.phoneMax != null ? opts.phoneMax : 18;
        if (cat.indexOf('bud') !== -1) {
            min = 15;
            max = 18;
        }
        return {
            kind: 'phone',
            min: min,
            max: max,
            label: min === max ? (min + ' digits') : (min + '–' + max + ' digits'),
            test: function (v) {
                var s = normalize(v);
                return /^\d+$/.test(s) && s.length >= min && s.length <= max;
            }
        };
    }

    function isPlausible(value) {
        var s = normalize(value);
        if (!s) return false;
        if (rule('tablet', '', '').test(s)) return true;
        if (/^\d{13}$/.test(s)) return true;
        return /^\d{15,18}$/.test(s);
    }

    function shouldAutoConfirm(value, r) {
        var s = normalize(value);
        if (!r || !r.test(s)) return false;
        if (r.kind === 'tablet') return s.length >= 11;
        return s.length >= r.min && s.length <= r.max;
    }

    function shouldAutoConfirmAny(value) {
        var s = normalize(value);
        if (/^\d{13}$/.test(s) || /^\d{15,18}$/.test(s)) return true;
        return /^[A-Z0-9]{11,20}$/.test(s) && /[A-Z]/.test(s);
    }

    w.IqbalImei = {
        normalize: normalize,
        resolveKind: resolveKind,
        rule: rule,
        isPlausible: isPlausible,
        shouldAutoConfirm: shouldAutoConfirm,
        shouldAutoConfirmAny: shouldAutoConfirmAny
    };
})(window);
