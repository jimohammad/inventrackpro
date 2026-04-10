<?php

/**
 * WhatsApp Cloud API Helper
 *
 * Uses Meta's WhatsApp Business Cloud API to send notifications.
 * Configure in Settings > WhatsApp Notifications:
 *   - Phone Number ID: from Meta Business dashboard
 *   - Access Token: permanent token from Meta Business
 *   - Recipient Number: phone number with country code (e.g. 96550123456)
 */
class WhatsApp {

    /**
     * Send a plain text WhatsApp message
     */
    public static function send(string $message): void {
        try {
            $db    = Database::getInstance();
            $phone = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'whatsapp_phone_id'");
            $token = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'whatsapp_token'");
            $to    = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'whatsapp_recipient'");

            if (!$phone || !$token || !$to) return;
            if (empty($phone['value']) || empty($token['value']) || empty($to['value'])) return;

            $url     = "https://graph.facebook.com/v21.0/{$phone['value']}/messages";
            $payload = json_encode([
                'messaging_product' => 'whatsapp',
                'to'                => $to['value'],
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

            // Fire-and-forget: send without waiting for response
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token['value'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT_MS     => 500,
                CURLOPT_NOSIGNAL       => 1,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            // Silent fail — never break the app if WhatsApp API is down
        }
    }

    public static function sale(array $data): void {
        $status = $data['paid'] >= $data['total'] ? 'Fully Paid' : 'Balance Due';
        $msg = "*New Sale Invoice*\n"
             . "----------------------------\n"
             . "Invoice: *{$data['invoice_no']}*\n"
             . "Customer: {$data['party']}\n"
             . "Branch: {$data['branch']}\n"
             . "Total: *{$data['currency']} {$data['total']}*\n"
             . "Paid: {$data['currency']} {$data['paid']}\n"
             . "Status: {$status}\n"
             . date('d M Y, h:i A');
        self::send($msg);
    }

    public static function purchase(array $data): void {
        $msg = "*New Purchase Invoice*\n"
             . "----------------------------\n"
             . "Invoice: *{$data['invoice_no']}*\n"
             . "Supplier: {$data['party']}\n"
             . "Branch: {$data['branch']}\n"
             . "Total: *{$data['currency']} {$data['total']}*\n"
             . date('d M Y, h:i A');
        self::send($msg);
    }

    public static function payment(array $data): void {
        $dir = $data['type'] === 'in' ? 'Payment Received' : 'Payment Sent';
        $msg = "*{$dir}*\n"
             . "----------------------------\n"
             . "Ref: *{$data['payment_no']}*\n"
             . "Party: {$data['party']}\n"
             . "Amount: *{$data['currency']} {$data['amount']}*\n"
             . "Account: {$data['account']}\n"
             . date('d M Y, h:i A');
        self::send($msg);
    }

    public static function expense(array $data): void {
        $msg = "*New Expense*\n"
             . "----------------------------\n"
             . "Ref: *{$data['expense_no']}*\n"
             . "Category: {$data['category']}\n"
             . "Amount: *{$data['currency']} {$data['amount']}*\n"
             . "Note: {$data['description']}\n"
             . date('d M Y, h:i A');
        self::send($msg);
    }
}
