# IqbalERP — Design system

Source of truth for **how the UI should look and behave**. Business rules live in `docs/domain/`. This file is for agents and humans doing frontend work.

**Memorable thing:** this is serious shop software for cashiers in Kuwait — dense, fast, obvious. Not a SaaS marketing site.

Do not invent a second palette, a new font, or a card-dashboard restyle. Match what is already on screen.

## Product

Internal ERP (PHP + Bootstrap 5) for electronics wholesale/retail: sales, stock, IMEI, payments, POs, mandoob van counts. Users scan, they do not read. Primary jobs are list → open → save/print.

Surfaces:

| Surface | Who | Canonical files |
|---------|-----|-----------------|
| Logged-in app | Staff | `app/views/layout.php`, `assets/css/layout.css` |
| Login | Staff | `app/views/login.php` (standalone dark card) |
| A5 / thermal print | Paper, banks, customers | Per-view print CSS (e.g. `app/views/payments/print.php`) |
| Public verify / track | Phone, no login | `app/views/public/*` — keep simple, no ERP chrome |

## Classifier

**App UI** (almost everything): workspace, data-dense, task-focused. Calm surface, strong type, few colors, tables over card mosaics.

**Print / letterhead:** GCC commercial voucher (bilingual EN/AR). Navy + gold, not app indigo.

**Login:** one dark centered card. Do not reuse this look inside the app.

## Tokens (logged-in app)

Canonical CSS variables are on `:root` in `assets/css/layout.css`. Use these. Do not add hex in new views unless the token is missing.

```css
--bg-main: #f6f7f9;
--bg-card: #ffffff;
--bg-sidebar: #ffffff;
--border-color: rgba(15, 23, 42, 0.10);
--text-main: #0f172a;
--text-muted: #64748b;
--primary: #2563eb;
--primary-hover: #1d4ed8;
--success: #16a34a;
--danger: #dc2626;
--warning: #d97706;
--header-height: 56px;
--iq-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

Body: `0.9rem`, line-height `1.5`. Page title: `1.3rem` / weight 700 / letter-spacing `-0.02em`. Subtitle: `0.85rem` muted.

**Nav / brand leftover:** the top-bar mark and some hovers still use indigo (`#4f46e5`, `#818cf8`, `#eef2ff`). That is existing chrome — do not introduce a *third* accent. New buttons and links use `--primary` (`#2563eb`).

`assets/css/app.css` still has older dark/indigo tokens. Prefer `layout.css` for new work. Do not “fix” the whole theme in a drive-by.

### Semantic color

| Meaning | Token / use |
|---------|-------------|
| Primary action | `--primary` / `.btn-primary` |
| Money in / done / paid | `--success` / `.btn-success` |
| Destructive | `--danger` / outline danger |
| Due soon / warning | `--warning` |
| Paused / inactive | slate badge, not a new purple |

### Radius and chrome

- Controls: `8px`
- Dropdown panels: `12px`
- Cards: `border-0 shadow-sm` (Bootstrap), white on `--bg-main`
- Hairline borders via `--border-color`
- Icons: Bootstrap Icons (local `assets/css/bootstrap-icons.min.css`)

## Layout

- Sticky white top bar, 56px. Horizontal nav groups, not a fat left sidebar on desktop.
- Main content on grey canvas; lists in one white table card.
- **Lists are tables.** 25-row caps on payments/sales/expenses are a product choice — do not add DataTables paging unless that page already has it.
- One primary action per row (filled button). Extra actions may stay visible on dense cashier tables; do not hide them behind ⋯ unless the user asks.
- Forms: labels always visible (never placeholder-as-only-label). `btn-sm` on list actions.
- Flash: top-right, not a full-width banner that shoves the page.

### Breakpoints

Mobile must stay usable (touch ~44px on primary actions). Stack action cells; do not shrink type below 16px equivalent on public/phone pages. App lists may stay slightly denser (0.9rem) because they are desktop-first cashier tools.

## Motion

Almost none. Theme/color transitions ~0.15–0.2s. No hero animations, no `transition: all`. Respect `prefers-reduced-motion` if you add motion.

## Copy

Utility language: orientation, status, action. Button labels say the job (`Inventory done`, `Save`, `Add mandoob`) — not Continue / Submit / Learn more.

Empty states: one sentence + the add/record action. No illustrations, no emoji as decoration.

Bilingual EN/AR on **print and public letterhead** when Settings has Arabic name/address. App chrome stays English unless that screen is already bilingual.

## Print (A5 GCC voucher)

Separate system. Do not apply app `--primary` indigo here.

| Role | Hex |
|------|-----|
| Band / company | `#0b1f36` |
| Gold rules | `#b8954a` |
| Ink | `#0b1220` |
| Muted | `#5b6b82` |
| Lines | `#c5d0e0` |
| Receipt in | `#0f766e` |
| Payment out | `#9f1239` |

Type: Segoe UI + Noto Naskh Arabic. Amount in figures and words. Computer-generated — no stamp/signature boxes unless a bank pack explicitly needs them. Thermal 80mm is a second template (`Auth::printTemplate()`), denser, no A5 letterhead chrome.

## Login

Dark slate card (`#0f172a` page, `#1e293b` card, `#6366f1` CTA). Centered, max-width ~420px. Keep it isolated.

## Anti-patterns (do not)

- Purple/violet gradients, 3-column feature grids, icon-in-colored-circle marketing blocks
- Recreating the ERP as person-card rosters or split “SaaS workspaces” without an explicit ask
- Inter / Roboto / Poppins as a *new* display face (stack is already system/Segoe)
- Dark mode for the main app (shell is light-only; `layout.php` forces `data-theme=light`)
- Mixing Main and Fahaheel in one UI filter
- Happy talk (“Welcome to your all-in-one…”)
- QR or extra chrome on thermal receipts
- Loading qrcode.js from a CDN on print pages (use PHP `QrSvg`)

## How to change UI

1. Edit existing `layout.css` tokens or the page’s own small `<style>` — do not start a new CSS framework.
2. One screen per change. Verify in the browser (click the flow, not only a screenshot).
3. After visual behavior changes that staff rely on, note `docs/domain/` if the *job* changed; skip it for pure CSS.
4. Server: upload only the views/CSS you touched.

## History

- **2026-09-09:** First `DESIGN.md`. Documents the live shell (not a redesign). Mandoob list stays the original table after a brief layout experiment was reverted.
