<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/** Minimal HTTPS client for gateway APIs (cURL; TLS verification always on). */
class Http
{
    public function __construct(private int $timeout = 20)
    {
    }

    /**
     * @param array<string,string> $headers
     * @return array{code:int, body:string, json:mixed}
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            throw new GatewayError('The PHP cURL extension is required for online payments.');
        }
        $ch = curl_init($url);
        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = "$k: $v";
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $h,
            CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_USERAGENT => 'ChannelSoftech-Website/1.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            throw new GatewayError('Could not reach the payment gateway (' . $err . '). Please try again.');
        }
        return ['code' => $code, 'body' => (string) $resp, 'json' => json_decode((string) $resp, true)];
    }
}
