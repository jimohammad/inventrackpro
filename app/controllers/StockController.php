<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/ItemController.php';

class StockController extends BaseController {
    public function index(): void {
        $ctrl = new ItemController();
        $ctrl->stock();
    }

    public function writeOffStock(): void {
        $ctrl = new ItemController();
        $ctrl->writeOffStock();
    }

    /**
     * Customer pricelist as one A4 PDF (in-stock, active items only).
     * Same visual language as public /pricelist. Catalog sale_price; no qty numbers.
     * Session branch only.
     */
    public function pricelistPrint(): void {
        Auth::authorizeAny(['stock', 'inventory'], 'view');

        $whId = Auth::warehouseId();
        if (!$whId) {
            $this->redirect('/?page=warehouse');
            return;
        }

        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT i.id, i.name, i.brand, i.sale_price, i.created_at,
                    COALESCE(i.has_nfc, 0) AS has_nfc,
                    s.quantity AS stock,
                    COALESCE(c.name, '') AS category_name
             FROM stock s
             JOIN items i ON i.id = s.item_id
             LEFT JOIN categories c ON c.id = i.category_id
             WHERE s.warehouse_id = ?
               AND i.is_active = 1
               AND s.quantity > 0
             ORDER BY COALESCE(NULLIF(c.name, ''), 'Other') ASC, i.name ASC",
            [$whId]
        );

        $grouped   = [];
        $catCounts = [];
        foreach ($rows as $row) {
            $cat = trim((string) ($row['category_name'] ?? ''));
            if ($cat === '') {
                $cat = 'Other';
            }
            $grouped[$cat][] = $row;
            $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
        }

        $itemCount         = count($rows);
        $newArrivalCutoff  = date('Y-m-d H:i:s', strtotime('-7 days'));
        $settings          = self::getSettings();
        $companyName       = (string) ($settings['company_name'] ?? PDF_COMPANY_NAME);
        $companyPhone      = (string) ($settings['company_phone'] ?? PDF_COMPANY_PHONE);
        $companyEmail      = (string) ($settings['company_email'] ?? (defined('PDF_COMPANY_EMAIL') ? PDF_COMPANY_EMAIL : ''));
        $logoSrc           = $this->pricelistLogoDataUri();
        $printedAt         = date('d M Y, h:i A');
        $pdfFilename       = 'Iqbal_Pricelist_' . date('Y-m-d') . '.pdf';

        include __DIR__ . '/../views/inventory/pricelist_print.php';
    }

    private function pricelistLogoDataUri(): ?string {
        $root = dirname(__DIR__, 2);
        foreach (['icare-logo.png', 'logo.png'] as $file) {
            $path = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }
            $bin = @file_get_contents($path);
            if ($bin === false || $bin === '') {
                continue;
            }
            return 'data:image/png;base64,' . base64_encode($bin);
        }
        return null;
    }
}
