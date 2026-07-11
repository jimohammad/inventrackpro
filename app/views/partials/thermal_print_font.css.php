<?php
/**
 * Shared thermal receipt font rules — printer uses its built-in monospace font.
 * Include inside <style> for thermal print views (sales, payments, returns, service).
 */
?>
/* Thermal printer native font (no web fonts) */
body {
    font-family: monospace;
    font-weight: 400;
    letter-spacing: 0;
    line-height: 1.3;
    -webkit-font-smoothing: none;
    -moz-osx-font-smoothing: unset;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.wrap {
    font-family: monospace;
}
