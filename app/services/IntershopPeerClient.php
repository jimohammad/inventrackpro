<?php

/**
 * HTTP client for the other shop's receive API (server-to-server).
 * Credentials come from .env after database.php loads it.
 */
final class IntershopPeerClient {

    public static function peerName(): string {
        $n = trim((string) (getenv('INTERSHOP_PEER_NAME') ?: ($_ENV['INTERSHOP_PEER_NAME'] ?? '')));
        return $n !== '' ? $n : 'Other shop';
    }

    public static function peerUrl(): string {
        return rtrim((string) (getenv('INTERSHOP_PEER_URL') ?: ($_ENV['INTERSHOP_PEER_URL'] ?? '')), '/');
    }

    public static function peerApiKey(): string {
        return trim((string) (getenv('INTERSHOP_PEER_API_KEY') ?: ($_ENV['INTERSHOP_PEER_API_KEY'] ?? '')));
    }

    public static function isConfigured(): bool {
        return self::peerUrl() !== '' && self::peerApiKey() !== '';
    }

    /**
     * POST stock-only payload to the peer receive endpoint.
     *
     * @param array<string,mixed> $payload
     * @return array{ok:bool,inbound_no:?string,error:?string,http:int}
     */
    public static function postReceive(array $payload): array {
        $base = self::peerUrl();
        $key  = self::peerApiKey();
        if ($base === '' || $key === '') {
            return ['ok' => false, 'inbound_no' => null, 'error' => 'Other shop URL or API key is not set in .env.', 'http' => 0];
        }

        $url = $base . '/api/?endpoint=intershop';
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return ['ok' => false, 'inbound_no' => null, 'error' => 'Could not encode transfer payload.', 'http' => 0];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'inbound_no' => null, 'error' => 'Could not start HTTP request.', 'http' => 0];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . $key,
            ],
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 12,
        ]);

        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'inbound_no' => null, 'error' => 'Network error: ' . $cerr, 'http' => $http];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'inbound_no' => null, 'error' => 'Other shop returned a non-JSON response (HTTP ' . $http . ').', 'http' => $http];
        }

        if (!empty($decoded['success'])) {
            $inbound = (string) ($decoded['inbound_no'] ?? '');
            return ['ok' => true, 'inbound_no' => $inbound !== '' ? $inbound : null, 'error' => null, 'http' => $http];
        }

        $err = trim((string) ($decoded['error'] ?? 'Other shop rejected the transfer.'));
        return ['ok' => false, 'inbound_no' => null, 'error' => $err, 'http' => $http];
    }
}
