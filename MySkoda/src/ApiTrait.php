<?php

declare(strict_types=1);

trait MySkodaApiTrait
{
    private function sendSimpleCommand(string $relative): bool
    {
        $vin = rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN'))));
        return $this->sendCommand('POST', '/api/v1/vehicles/' . $vin . '/' . ltrim($relative, '/'), []);
    }

    private function startClimateInternal(float $temperature): bool
    {
        $vin = rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN'))));
        $body = [
            'targetTemperature' => [
                'value' => max(16.0, min(30.0, $temperature)),
                'unit' => 'CELSIUS'
            ],
            'airConditioningWithoutExternalPower' => $this->ReadPropertyBoolean('ClimateWithoutExternalPower')
        ];
        return $this->sendCommand('POST', '/api/v1/vehicles/' . $vin . '/air-conditioning/start', $body);
    }

    private function sendDiscoveredScalarCommand(string $purpose, int|string $value): bool
    {
        $operation = $this->findOperation($this->refreshOpenApi(false), $purpose);
        if ($operation === null) {
            $this->WriteAttributeString('LastError', 'OpenAPI-Operation nicht gefunden: ' . $purpose);
            $this->SetStatus(202);
            return false;
        }
        $path = $this->replacePathParameters((string) $operation['path']);
        $body = $this->buildScalarPayload($operation, $purpose, $value);
        return $this->sendCommand((string) $operation['method'], $path, $body);
    }

    private function sendCommand(string $method, string $path, ?array $body): bool
    {
        if (!$this->ReadPropertyBoolean('EnableRemote') || !$this->canRequest(true)) {
            return false;
        }
        $response = $this->request($method, $path, $body);
        $this->absorbHeaders($response['headers']);
        if (!$response['ok']) {
            $this->setApiError($response);
            return false;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(102);
        return true;
    }

    private function request(string $method, string $path, ?array $body = null, bool $withApiKey = true): array
    {
        $headers = [
            'Accept: application/json, application/problem+json',
            'User-Agent: ' . self::USER_AGENT
        ];
        if ($withApiKey) {
            $headers[] = 'X-API-Key: ' . trim($this->ReadPropertyString('APIToken'));
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $responseHeaders = [];
        $url = str_starts_with($path, 'http') ? $path : self::API_ROOT . $path;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$responseHeaders): int {
                $length = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $length;
            }
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $this->SendDebug('HTTP', strtoupper($method) . ' ' . $path . ' -> ' . $httpCode, 0);

        return [
            'ok' => $curlError === '' && $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'headers' => $responseHeaders,
            'raw' => is_string($raw) ? $raw : '',
            'json' => $json,
            'curlError' => $curlError
        ];
    }

    private function absorbHeaders(array $headers): void
    {
        if (isset($headers['ratelimit-limit']) && is_numeric($headers['ratelimit-limit'])) {
            $this->WriteAttributeInteger('RateLimitLimit', (int) $headers['ratelimit-limit']);
        }
        if (isset($headers['ratelimit-remaining']) && is_numeric($headers['ratelimit-remaining'])) {
            $this->WriteAttributeInteger('RateLimitRemaining', (int) $headers['ratelimit-remaining']);
        }
        if (isset($headers['ratelimit-reset']) && is_numeric($headers['ratelimit-reset'])) {
            $this->WriteAttributeInteger('RateLimitResetAt', time() + (int) $headers['ratelimit-reset']);
        }
        if (isset($headers['retry-after']) && is_numeric($headers['retry-after'])) {
            $this->WriteAttributeInteger('BlockedUntil', time() + (int) $headers['retry-after']);
        }
        if (isset($headers['x-api-key-expires-at'])) {
            $timestamp = strtotime((string) $headers['x-api-key-expires-at']);
            if ($timestamp !== false) {
                $this->WriteAttributeInteger('ApiKeyExpiresAt', $timestamp);
            }
        }
    }

    private function canRequest(bool $userAction): bool
    {
        $now = time();
        if ($this->ReadAttributeInteger('BlockedUntil') > $now) {
            return false;
        }
        $remaining = $this->ReadAttributeInteger('RateLimitRemaining');
        $resetAt = $this->ReadAttributeInteger('RateLimitResetAt');
        if ($remaining < 0 || $resetAt <= $now) {
            return true;
        }
        if ($userAction) {
            return $remaining > 0;
        }
        return $remaining > self::QUOTA_RESERVE;
    }

    private function setApiError(array $response): void
    {
        $this->absorbHeaders($response['headers'] ?? []);
        $text = $this->problemText($response);
        $this->WriteAttributeString('LastError', $text);
        $this->SendDebug('API-Fehler', $text, 0);
        $this->SetStatus(((int) ($response['status'] ?? 0)) === 429 ? 203 : 202);
    }

    private function problemText(array $response): string
    {
        if (($response['curlError'] ?? '') !== '') {
            return 'cURL: ' . $response['curlError'];
        }
        $json = $response['json'] ?? null;
        if (is_array($json)) {
            $detail = $json['detail'] ?? $json['title'] ?? null;
            $type = $json['type'] ?? null;
            if ($detail !== null) {
                return 'HTTP ' . ((int) ($response['status'] ?? 0)) . ': ' . $detail . ($type ? ' [' . basename((string) $type) . ']' : '');
            }
        }
        return 'HTTP ' . ((int) ($response['status'] ?? 0));
    }

}
