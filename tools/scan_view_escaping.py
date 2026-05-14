"""Scan app/views for <?= outputs that may lack HTML escaping (post SEC3)."""
from __future__ import annotations

import re
from pathlib import Path

VIEWS = Path(__file__).resolve().parents[1] / "app" / "views"
OUT = Path(__file__).resolve().parent / "view_escaping_scan_report.txt"

# If any of these appear on the line, we treat it as having a common safe sink on same line.
SAFE = re.compile(
    r"htmlspecialchars\s*\(|htmlentities\s*\(|json_encode\s*\(|"
    r"number_format\s*\(|\bmoney\s*\(|\bretMoney\s*\(|"
    r"\bdate\s*\(|\bstrtotime\s*\(|\bcount\s*\(|\bround\s*\(|\babs\s*\(|\bmin\s*\(|\bmax\s*\(|"
    r"\barray_sum\s*\(|\bimplode\s*\(|\bexplode\s*\(|\bsubstr\s*\(|\bstrtoupper\s*\(|\bstrtolower\s*\(|\btrim\s*\(|"
    r"Auth::csrfField|Auth::csrfToken|"
    r"\bAPP_[A-Z0-9_]+|\bDECIMAL_PLACES\b|ENT_QUOTES|"
    r"<\?=\s*\(int\)|<\?=\s*\(float\)|<\?=\s*\(bool\)|"
    r"<\?=\s*isset\s*\(|<\?=\s*!\s*empty\s*\(|"
    r"<\?=\s*['\"]|<\?=\s*\d"
)

# Numeric / FK id echoes (lower XSS priority for typical ERP data)
ID_KEY = re.compile(r"\[\s*['\"]id['\"]\s*\]")

# User-/DB-controlled string keys often rendered as text (not exhaustive)
TEXT_KEYS = (
    "name",
    "item_name",
    "party_name",
    "supplier_name",
    "customer_name",
    "warehouse_name",
    "description",
    "notes",
    "reason",
    "address",
    "email",
    "city",
    "sku",
    "brand",
    "model",
    "imei",
    "imei2",
    "invoice_no",
    "return_no",
    "payment_no",
    "expense_no",
    "transfer_no",
    "po_no",
    "ref_no",
    "purchase_invoice",
    "sale_invoice",
    "original_invoice",
    "sale_invoice_no",
    "discount_no",
    "title",
    "desc",
    "message",
    "status",  # sometimes enum-like but can be free text in some modules
)

text_key_re = re.compile(
    r"<\?=[^?]*\[\s*['\"](?P<k>" + "|".join(re.escape(k) for k in TEXT_KEYS) + r")['\"]\s*\]"
)

critical_substrings = ("$content", "$extraJs", "innerHTML", "<script", "javascript:")

# Layout noise: sidebar active comparisons only
layout_noise = re.compile(r"app/views/layout\.php:\d+:")


def classify(line: str) -> str:
    l = line.lower()
    if any(s in line for s in critical_substrings):
        return "CRITICAL"
    if "href=\"<?=" in line or "href='<?=" in line:
        if "htmlspecialchars" not in line:
            return "HIGH"
    if "onclick=" in line and "<?=" in line and "htmlspecialchars" not in line and "json_encode" not in line:
        return "HIGH"
    if text_key_re.search(line) and not SAFE.search(line):
        return "HIGH"
    if re.search(r"<\?=\s*\$[a-zA-Z_]\w*\s*\?", line) and not SAFE.search(line):
        return "REVIEW"
    return "SKIP"


def main() -> None:
    all_lines: list[tuple[str, int, str]] = []
    for path in sorted(VIEWS.rglob("*.php")):
        rel = path.relative_to(VIEWS.parent.parent)
        lines = path.read_text(encoding="utf-8", errors="replace").splitlines()
        for i, line in enumerate(lines, 1):
            if "<?=" not in line:
                continue
            if SAFE.search(line):
                continue
            if ID_KEY.search(line) and not text_key_re.search(line):
                # pure id echo in href/value — keep as low noise unless other signals
                if "href=" not in line and 'value="' not in line and "value='" not in line:
                    continue
            cat = classify(line)
            if cat == "SKIP":
                continue
            if cat == "REVIEW" and layout_noise.match(f"{rel.as_posix()}:{i}:"):
                continue
            all_lines.append((f"{rel.as_posix()}:{i}", cat, line.strip()[:300]))

    by_cat: dict[str, list[tuple[str, str]]] = {"CRITICAL": [], "HIGH": [], "REVIEW": []}
    for loc, cat, s in all_lines:
        by_cat.setdefault(cat, []).append((loc, s))

    lines_out: list[str] = []
    lines_out.append("view_escaping_scan_report (heuristic; same-line only; UTF-8)\n")
    lines_out.append("CRITICAL = template/script injection sinks\n")
    lines_out.append("HIGH = likely user/DB string field echoed without htmlspecialchars on same line\n")
    lines_out.append("REVIEW = other unwrapped <?= (may be numeric, enums, or safe)\n")
    for c in ("CRITICAL", "HIGH", "REVIEW"):
        items = by_cat.get(c, [])
        lines_out.append(f"\n=== {c} ({len(items)}) ===\n")
        for loc, s in items:
            lines_out.append(f"{loc}\n  {s}\n")

    OUT.write_text("\n".join(lines_out), encoding="utf-8")
    print(f"Wrote {OUT} ({len(all_lines)} flagged lines)")


if __name__ == "__main__":
    main()
