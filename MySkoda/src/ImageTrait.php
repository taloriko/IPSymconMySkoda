<?php

declare(strict_types=1);

trait MySkodaImageTrait
{
    public function DiagnoseVehicleImages(): string
    {
        if (!$this->configurationValid()) {
            return $this->encodeImageDiagnostic([
                'ok' => false,
                'message' => 'VIN or API token is missing or invalid.'
            ]);
        }

        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return $this->encodeImageDiagnostic([
                'ok' => false,
                'message' => 'No vehicle response is cached yet. Test the connection or update the vehicle first.'
            ]);
        }

        $vehicleMatches = [];
        $this->collectVehicleImageHints($raw, '$', $vehicleMatches);

        $operations = $this->refreshOpenApi(false);
        $apiCandidates = [];
        foreach ($operations as $operation) {
            if (!is_array($operation) || !$this->isVehicleImageOperation($operation)) {
                continue;
            }

            $apiCandidates[] = [
                'method' => strtoupper((string) ($operation['method'] ?? '')),
                'path' => $this->maskImageDiagnosticText((string) ($operation['path'] ?? '')),
                'operationId' => (string) ($operation['operationId'] ?? ''),
                'summary' => (string) ($operation['summary'] ?? '')
            ];
        }

        $probes = [];
        $probeCount = 0;
        foreach ($operations as $operation) {
            if ($probeCount >= 3 || !is_array($operation) || !$this->isVehicleImageOperation($operation)) {
                continue;
            }
            if (strtoupper((string) ($operation['method'] ?? '')) !== 'GET') {
                continue;
            }

            $path = $this->replacePathParameters((string) ($operation['path'] ?? ''));
            if ($path === '' || preg_match('/\{[^}]+\}/', $path) === 1) {
                continue;
            }
            if (!$this->canRequest(true)) {
                $probes[] = [
                    'path' => $this->maskImageDiagnosticText($path),
                    'status' => 0,
                    'message' => 'Skipped because the MySkoda rate limit / waiting period is active.'
                ];
                break;
            }

            $response = $this->request('GET', $path);
            $this->absorbHeaders($response['headers'] ?? []);
            $probeMatches = [];
            if (is_array($response['json'] ?? null)) {
                $this->collectVehicleImageHints($response['json'], '$', $probeMatches);
            }

            $probes[] = [
                'path' => $this->maskImageDiagnosticText($path),
                'status' => (int) ($response['status'] ?? 0),
                'ok' => (bool) ($response['ok'] ?? false),
                'matches' => array_values($probeMatches),
                'error' => ($response['ok'] ?? false) ? '' : $this->problemText($response)
            ];
            ++$probeCount;
        }

        $diagnostic = [
            'ok' => true,
            'source' => 'MySkoda Public API',
            'vehicleResponseMatches' => array_values($vehicleMatches),
            'openApiCandidates' => $apiCandidates,
            'probes' => $probes
        ];

        $json = $this->encodeImageDiagnostic($diagnostic);
        $this->SendDebug('Vehicle image diagnostic', $json, 0);

        return $json;
    }

    private function collectVehicleImageHints(mixed $value, string $path, array &$matches, int $depth = 0): void
    {
        if ($depth > 14 || count($matches) >= 100 || !is_array($value)) {
            return;
        }

        foreach ($value as $key => $child) {
            $childPath = is_int($key)
                ? $path . '[' . $key . ']'
                : $path . '.' . (string) $key;

            $keyLooksRelevant = !is_int($key) && $this->isVehicleImageHint((string) $key);
            $valueLooksRelevant = is_string($child) && $this->isVehicleImageUrl($child);

            if ($keyLooksRelevant || $valueLooksRelevant) {
                $matches[$childPath] = [
                    'path' => $childPath,
                    'value' => $this->imageDiagnosticValue($child)
                ];
            }

            if (is_array($child)) {
                $this->collectVehicleImageHints($child, $childPath, $matches, $depth + 1);
            }
        }
    }

    private function isVehicleImageOperation(array $operation): bool
    {
        $haystack = strtolower(
            (string) ($operation['operationId'] ?? '') . ' '
            . (string) ($operation['summary'] ?? '') . ' '
            . (string) ($operation['path'] ?? '')
        );

        return $this->isVehicleImageHint($haystack);
    }

    private function isVehicleImageHint(string $text): bool
    {
        $text = strtolower($text);
        foreach ([
            'image',
            'render',
            'media',
            'exterior',
            'garage',
            'picture',
            'photo',
            'composite',
            'viewtype',
            'viewpoint'
        ] as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isVehicleImageUrl(string $value): bool
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return false;
        }

        if (str_contains($value, 'iprenders') || str_contains($value, 'blob.core.windows.net')) {
            return true;
        }

        return preg_match('/\.(png|jpe?g|webp|svg)(\?|$)/i', $value) === 1;
    }

    private function imageDiagnosticValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return 'array(' . count($value) . ')';
        }
        if (is_string($value)) {
            return $this->maskImageDiagnosticText($value);
        }
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return get_debug_type($value);
    }

    private function maskImageDiagnosticText(string $value): string
    {
        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        if ($vin === '') {
            return $value;
        }

        return str_ireplace([$vin, rawurlencode($vin)], '{vin}', $value);
    }

    private function encodeImageDiagnostic(array $diagnostic): string
    {
        $json = json_encode(
            $diagnostic,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return is_string($json) ? $this->maskImageDiagnosticText($json) : '{}';
    }
}
