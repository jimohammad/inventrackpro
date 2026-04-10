<?php

class Telegram {

    public static function send(string $message): void {
        try {
            $db    = Database::getInstance();
            $token = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'telegram_bot_token'");
            $chat  = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'telegram_chat_id'");

            if (!$token || !$chat || empty($token['value']) || empty($chat['value'])) return;

            $url     = "https://api.telegram.org/bot{$token['value']}/sendMessage";
            $payload = json_encode([
                'chat_id'    => $chat['value'],
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            // Silent fail — never break the app if Telegram is down
        }
    }

    public static function sale(array $data): void {
        $emoji  = '🧾';
        $status = $data['paid'] >= $data['total'] ? '✅ Fully Paid' : '⏳ Balance Due';
        $msg    = "{$emoji} <b>New Sale Invoice</b>\n"
                . "━━━━━━━━━━━━━━━\n"
                . "📋 Invoice: <b>{$data['invoice_no']}</b>\n"
                . "👤 Customer: {$data['party']}\n"
                . "🏪 Branch: {$data['branch']}\n"
                . "💰 Total: <b>{$data['currency']} {$data['total']}</b>\n"
                . "💵 Paid: {$data['currency']} {$data['paid']}\n"
                . "📌 Status: {$status}\n"
                . "🕐 " . date('d M Y, h:i A');
        self::send($msg);
    }

    public static function purchase(array $data): void {
        $msg = "🛒 <b>New Purchase Invoice</b>\n"
             . "━━━━━━━━━━━━━━━\n"
             . "📋 Invoice: <b>{$data['invoice_no']}</b>\n"
             . "🏭 Supplier: {$data['party']}\n"
             . "🏪 Branch: {$data['branch']}\n"
             . "💰 Total: <b>{$data['currency']} {$data['total']}</b>\n"
             . "🕐 " . date('d M Y, h:i A');
        self::send($msg);
    }

    public static function payment(array $data): void {
        $dir  = $data['type'] === 'in' ? '💚 Payment Received' : '🔴 Payment Sent';
        $icon = $data['type'] === 'in' ? '⬇️' : '⬆️';
        $msg  = "{$icon} <b>{$dir}</b>\n"
              . "━━━━━━━━━━━━━━━\n"
              . "📋 Ref: <b>{$data['payment_no']}</b>\n"
              . "👤 Party: {$data['party']}\n"
              . "💰 Amount: <b>{$data['currency']} {$data['amount']}</b>\n"
              . "🏦 Account: {$data['account']}\n"
              . "🕐 " . date('d M Y, h:i A');
        self::send($msg);
    }

    public static function expense(array $data): void {
        $msg = "💸 <b>New Expense</b>\n"
             . "━━━━━━━━━━━━━━━\n"
             . "📋 Ref: <b>{$data['expense_no']}</b>\n"
             . "🏷️ Category: {$data['category']}\n"
             . "💰 Amount: <b>{$data['currency']} {$data['amount']}</b>\n"
             . "📝 Note: {$data['description']}\n"
             . "🕐 " . date('d M Y, h:i A');
        self::send($msg);
    }

    public static function transfer(array $data): void {
        $msg = "🔄 <b>Account Transfer</b>\n"
             . "━━━━━━━━━━━━━━━\n"
             . "📋 Ref: <b>{$data['transfer_no']}</b>\n"
             . "➡️ From: {$data['from']}  →  {$data['to']}\n"
             . "💰 Amount: <b>{$data['currency']} {$data['amount']}</b>\n"
             . "🕐 " . date('d M Y, h:i A');
        self::send($msg);
    }
}
