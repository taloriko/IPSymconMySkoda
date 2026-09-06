<?php

declare(strict_types=1);

trait MySkodaApiTrait
{
    private function sendSimpleCommand(string $relativePath): bool
    {
        $vin = rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN'))));
        $path = '/api/v1/vehicles/' . $vin . '/' . ltrim($relativePath, '/');

        return $this->sendCommand('POST', $path, []);
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

        return $this->sendCommand(
            'POST',
            '/api/v1/vehicles/' . $vin . '/air-conditioning/start',
            $body
        );
    }

    private function sendDiscoveredScalarCommand(string $purpose, int|string $value): bool
    {
        $operation = $this->findOperation($this->refreshOpenApi(false), $purpose);
        if ($operation === null) {
            $message = sprintf(
                $this->Translate('OpenAPI operation not found: %s'),
                $purpose
            );
            $this->WriteAttributeString('LastError', $message);
            $this->SetStatus(202);
            return false;
        }

        $path = $this->replacePathParameters((string) $operation['path']);
        $body = $this->buildScalarPayload($operation, $purpose, $value);

        return $this->sendCommand((string) $operation['method'], $path, $body);
    }

    private function sendCommand(string $method, string $path, ?array $body): bool
    {
        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            $this->WriteAttributeString(
                'LastError',
                $this->Translate('Remote control is disabled in this instance.')
            );
            return false;
        }

        if (!$this->canRequest(true)) {
            $this->WriteAttributeString(
                'LastError',
                $this->Translate('MySkoda rate limit / waiting period is active.')
            );
            $this->SetStatus(203);
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

    /**
     * Executes one HTTP request against the MySkoda Public API.
     *
     * The returned array has a stable internal shape so callers can handle
     * transport errors, HTTP errors and JSON responses in the same way.
     */
    private function request(
        string $method,
        string $path,
        ?array $body = null,
        bool $withApiKey = true
    ): array {
        $headers = [
            'Accept: application/json, application/problem+json',
            'User-Agent: ' . self::USER_AGENT
        ];

        if ($withApiKey) {
            $headers[] = 'X-API-Key: ' . trim($this->ReadPropertyString('APIToken'));
        }

        $encodedBody = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $encodedBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($encodedBody)) {
                return $this->failedRequestResult('Request body could not be encoded as JSON.');
            }
        }

        $handle = curl_init();
        if ($handle === false) {
            return $this->failedRequestResult('cURL could not be initialized.');
        }

        $responseHeaders = [];
        $url = str_starts_with($path, 'http') ? $path : self::API_ROOT . $path;

        curl_setopt_array($handle, [
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

        if ($encodedBody !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedBody);
        }

        $raw = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        $json = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $this->SendDebug(
            'HTTP',
            strtoupper($method) . ' ' . $this->debugPath($path) . ' -> ' . $httpCode,
            0
        );

        return [
            'ok' => $curlError === '' && $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'headers' => $responseHeaders,
            'raw' => is_string($raw) ? $raw : '',
            'json' => $json,
            'curlError' => $curlError
        ];
    }

    private function failedRequestResult(string $error): array
    {
        return [
            'ok' => false,
            'status' => 0,
            'headers' => [],
            'raw' => '',
            'json' => null,
            'curlError' => $error
        ];
    }

    /**
     * Masks the VIN in debug output while keeping the endpoint recognizable.
     */
    private function debugPath(string $path): string
    {
        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        if ($vin === '') {
            return $path;
        }

        return str_replace(rawurlencode($vin), '{vin}', $path);
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
            $this->WriteAttributeInteger(
                'RateLimitResetAt',
                time() + max(0, (int) $headers['ratelimit-reset'])
            );
        }

        if (isset($headers['retry-after']) && is_numeric($headers['retry-after'])) {
            $this->WriteAttributeInteger(
                'BlockedUntil',
                time() + max(0, (int) $headers['retry-after'])
            );
        }

        if (isset($headers['x-api-key-expires-at'])) {
            $timestamp = strtotime((string) $headers['x-api-key-expires-at']);
            if ($timestamp !== false) {
                $this->WriteAttributeInteger('ApiKeyExpiresAt', $timestamp);
            }
        }

        $this->updateKeyExpiryWarning();
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
        $message = $this->problemText($response);
        $this->WriteAttributeString('LastError', $message);
        $this->SendDebug('API error', $message, 0);
        $this->SetStatus(((int) ($response['status'] ?? 0)) === 429 ? 203 : 202);
    }

    private function problemText(array $response): string
    {
        $curlError = (string) ($response['curlError'] ?? '');
        if ($curlError !== '') {
            return 'cURL: ' . $curlError;
        }

        $status = (int) ($response['status'] ?? 0);
        $json = $response['json'] ?? null;
        if (is_array($json)) {
            $detail = $json['detail'] ?? $json['title'] ?? null;
            $type = $json['type'] ?? null;
            if ($detail !== null) {
                $suffix = $type ? ' [' . basename((string) $type) . ']' : '';
                return 'HTTP ' . $status . ': ' . (string) $detail . $suffix;
            }
        }

        return 'HTTP ' . $status;
    }
}
