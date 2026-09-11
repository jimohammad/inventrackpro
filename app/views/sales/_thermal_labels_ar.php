<?php
/**
 * Bilingual EN/AR labels for sale invoices + payment receipts (MOCI).
 * Include then call thermalBiLabel('invoice_no') or thermalBiLabelStacked(...).
 */
$thermalLabels = [
    'sale_invoice'       => ['en' => 'Sale Invoice',      'ar' => 'فاتورة بيع'],
    'sale_return'        => ['en' => 'Sale Return',       'ar' => 'مرتجع بيع'],
    'invoice'            => ['en' => 'Invoice',           'ar' => 'فاتورة'],
    'invoice_no'         => ['en' => 'Invoice No',        'ar' => 'رقم الفاتورة'],
    'date'               => ['en' => 'Date',              'ar' => 'التاريخ'],
    'customer'           => ['en' => 'Customer',          'ar' => 'العميل'],
    'phone'              => ['en' => 'Phone',             'ar' => 'الهاتف'],
    'warehouse'          => ['en' => 'Warehouse',         'ar' => 'المخزن'],
    'salesman'           => ['en' => 'Salesman',          'ar' => 'البائع'],
    'item'               => ['en' => 'Item',              'ar' => 'الصنف'],
    'description'        => ['en' => 'Description',       'ar' => 'الوصف'],
    'qty'                => ['en' => 'Qty',               'ar' => 'الكمية'],
    'rate'               => ['en' => 'Rate',              'ar' => 'السعر'],
    'price'              => ['en' => 'Price',             'ar' => 'السعر'],
    'amount'             => ['en' => 'Amount',            'ar' => 'المبلغ'],
    'amt'                => ['en' => 'Amt',               'ar' => 'المبلغ'],
    'disc'               => ['en' => 'Disc',              'ar' => 'خصم'],
    'total_qty'          => ['en' => 'Total Qty',         'ar' => 'إجمالي الكمية'],
    'subtotal'           => ['en' => 'Subtotal',          'ar' => 'المجموع الفرعي'],
    'discount'           => ['en' => 'Discount',          'ar' => 'الخصم'],
    'tax'                => ['en' => 'Tax',               'ar' => 'الضريبة'],
    'shipping'           => ['en' => 'Shipping',          'ar' => 'الشحن'],
    'total'              => ['en' => 'Total',             'ar' => 'الإجمالي'],
    'grand_total'        => ['en' => 'Grand Total',       'ar' => 'الإجمالي'],
    'balance_due'        => ['en' => 'Balance Due',       'ar' => 'المبلغ المستحق'],
    'total_outstanding'  => ['en' => 'Total Outstanding', 'ar' => 'إجمالي المستحق'],
    'notes'              => ['en' => 'Notes',             'ar' => 'ملاحظات'],
    'previous_balance'   => ['en' => 'Previous Balance',  'ar' => 'الرصيد السابق'],
    'this_invoice'       => ['en' => 'This Invoice',      'ar' => 'هذه الفاتورة'],
    'paid_now'           => ['en' => 'Paid Now',          'ar' => 'المدفوع الآن'],
    'current_balance'    => ['en' => 'Current Balance',   'ar' => 'الرصيد الحالي'],
    'thank_you'          => ['en' => 'Thank you for your business!', 'ar' => 'شكراً لتعاملكم معنا!'],
    'computer_generated' => ['en' => 'This is a computer generated invoice.', 'ar' => 'فاتورة صادرة إلكترونياً.'],
    'printed'            => ['en' => 'Printed',           'ar' => 'طُبع'],

    // Payment receipts
    'payment_receipt'            => ['en' => 'Payment Receipt Voucher',   'ar' => 'سند قبض'],
    'payment_voucher'            => ['en' => 'Payment Voucher',           'ar' => 'سند صرف'],
    'payment_in'                 => ['en' => 'Payment In',                'ar' => 'دفعة واردة'],
    'payment_out'                => ['en' => 'Payment Out',                'ar' => 'دفعة صادرة'],
    'original'                   => ['en' => 'Original',                 'ar' => 'أصل'],
    'receipt_no'                 => ['en' => 'Receipt No',                'ar' => 'رقم الإيصال'],
    'voucher_no'                 => ['en' => 'Voucher No',               'ar' => 'رقم السند'],
    'party'                      => ['en' => 'Customer Name',             'ar' => 'اسم العميل'],
    'received_from'              => ['en' => 'Customer Name',            'ar' => 'اسم العميل'],
    'paid_to'                    => ['en' => 'Paid To',                  'ar' => 'صرف إلى'],
    'account'                    => ['en' => 'Account',                   'ar' => 'الحساب'],
    'type'                       => ['en' => 'Type',                      'ar' => 'النوع'],
    'cheque'                     => ['en' => 'Cheque',                    'ar' => 'شيك'],
    'cheque_no'                  => ['en' => 'Cheque No',                 'ar' => 'رقم الشيك'],
    'amount_received'            => ['en' => 'Amount Received',           'ar' => 'المبلغ المستلم'],
    'amount_paid'                => ['en' => 'Amount Paid',               'ar' => 'المبلغ المدفوع'],
    'amount_in_words'            => ['en' => 'Amount in Words',           'ar' => 'المبلغ بالحروف'],
    'payment_received'           => ['en' => 'Payment Received',          'ar' => 'الدفعة المستلمة'],
    'payment_made'               => ['en' => 'Payment Made',              'ar' => 'الدفعة المدفوعة'],
    'received_by'                => ['en' => 'Received By',               'ar' => 'المستلم'],
    'authorized_signatory'       => ['en' => 'Authorized Signatory',      'ar' => 'المفوض بالتوقيع'],
    'company_stamp'             => ['en' => 'Company Stamp',             'ar' => 'ختم الشركة'],
    'customer_ack'               => ['en' => 'Customer Acknowledgement',   'ar' => 'إقرار العميل'],
    'vat_not_applicable'          => ['en' => 'VAT not applicable — State of Kuwait', 'ar' => 'غير خاضع لضريبة القيمة المضافة — دولة الكويت'],
    'cr_no'                      => ['en' => 'CR No',                     'ar' => 'السجل التجاري'],
    'license_no'                 => ['en' => 'Trade License',             'ar' => 'الرخصة التجارية'],
    'state_of_kuwait'            => ['en' => 'State of Kuwait',            'ar' => 'دولة الكويت'],
    'computer_generated_receipt' => ['en' => 'This is a computer generated receipt.', 'ar' => 'إيصال صادر إلكترونياً.'],
    'payment_mode'               => ['en' => 'Payment Mode',              'ar' => 'طريقة الدفع'],
    'cash'                       => ['en' => 'Cash',                      'ar' => 'نقداً'],
    'bank_account'               => ['en' => 'Bank Account',              'ar' => 'حساب بنكي'],
    'card'                       => ['en' => 'Card',                      'ar' => 'بطاقة'],
    'mobile_wallet'              => ['en' => 'Mobile Wallet',             'ar' => 'محفظة إلكترونية'],
];

$GLOBALS['thermalLabels'] = $thermalLabels;

if (!function_exists('thermalBiLabel')) {
    /**
     * HTML: English / Arabic (escaped). Inline for receipt rows.
     */
    function thermalBiLabel(string $key): string
    {
        $pair = $GLOBALS['thermalLabels'][$key] ?? null;
        if ($pair === null) {
            return htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        }
        $en = htmlspecialchars((string) $pair['en'], ENT_QUOTES, 'UTF-8');
        $ar = htmlspecialchars((string) $pair['ar'], ENT_QUOTES, 'UTF-8');

        // Outer LTR isolate keeps the whole pair together when two labels sit on
        // one line (otherwise: "Amount Received / Type / النوع"). Inner RTL bdi
        // + LRM keep following dates/numbers from jumping: "Date / 31 :التاريخ".
        return '<bdi dir="ltr">' . $en . ' / <bdi class="ar" dir="rtl" lang="ar">' . $ar . '</bdi></bdi>&lrm;';
    }
}

if (!function_exists('thermalBiLabelStacked')) {
    /**
     * HTML: stacked column header. English above Arabic by default;
     * pass $arabicOnTop for Arabic above English (A5 Description).
     */
    function thermalBiLabelStacked(string $key, bool $arabicOnTop = false): string
    {
        $pair = $GLOBALS['thermalLabels'][$key] ?? null;
        if ($pair === null) {
            return htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        }
        $en = htmlspecialchars((string) $pair['en'], ENT_QUOTES, 'UTF-8');
        $ar = htmlspecialchars((string) $pair['ar'], ENT_QUOTES, 'UTF-8');
        $arHtml = '<bdi class="ar" dir="rtl" lang="ar">' . $ar . '</bdi>';

        return $arabicOnTop ? $arHtml . '<br>' . $en : $en . '<br>' . $arHtml;
    }
}

if (!function_exists('thermalMixedBidiHtml')) {
    /**
     * Mixed EN/AR item names for html2pdf.
     * Latin/model tokens (A7 Pro, 4GB) use bdo+LTR override so they do not
     * reverse or paint on top of Arabic when the line is RTL.
     */
    function thermalMixedBidiHtml(string $text): string
    {
        if ($text === '') {
            return '';
        }
        if (!preg_match('/\p{Arabic}/u', $text)) {
            return '<bdo class="bidi-ltr" dir="ltr">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</bdo>';
        }
        if (!preg_match_all('/\p{Arabic}+|[^\p{Arabic}]+/u', $text, $matches)) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
        $html = '';
        foreach ($matches[0] as $run) {
            $esc = htmlspecialchars($run, ENT_QUOTES, 'UTF-8');
            if (preg_match('/\p{Arabic}/u', $run)) {
                $html .= '<span class="bidi-rtl" dir="rtl" lang="ar">' . $esc . '</span>';
            } else {
                $html .= '<bdo class="bidi-ltr" dir="ltr">' . $esc . '</bdo>';
            }
        }
        return $html;
    }
}
