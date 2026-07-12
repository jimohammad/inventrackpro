/* Shared: Home chip when running as installed PWA */
(function () {
  var isStandalone = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;
  if (!isStandalone) {
    return;
  }
  if (document.getElementById('iqbal-pwa-home')) {
    return;
  }

  var link = document.createElement('a');
  link.id = 'iqbal-pwa-home';
  link.href = '/apps';
  link.setAttribute('aria-label', 'Back to menu');
  link.textContent = 'Menu';
  link.style.cssText = [
    'position:fixed',
    'top:max(12px, env(safe-area-inset-top))',
    'left:12px',
    'z-index:9999',
    'display:inline-flex',
    'align-items:center',
    'gap:6px',
    'padding:8px 12px',
    'border-radius:999px',
    'background:rgba(15,23,42,0.92)',
    'color:#fff',
    'font:600 13px/1 system-ui,-apple-system,sans-serif',
    'text-decoration:none',
    'box-shadow:0 8px 24px rgba(2,6,23,0.25)'
  ].join(';');

  function mount() {
    document.body.appendChild(link);
  }
  if (document.body) {
    mount();
  } else {
    document.addEventListener('DOMContentLoaded', mount);
  }
})();
