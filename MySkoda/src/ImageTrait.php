<?php

declare(strict_types=1);

trait MySkodaImageTrait
{
    private const VEHICLE_IMAGE_IDENT = 'VehicleImage';
    private const VEHICLE_IMAGE_NAME = 'Vehicle image';
    private const VEHICLE_IMAGE_MAX_BYTES = 15_000_000;

    public function RefreshVehicleImage(): bool
    {
        return $this->syncVehicleImage(true);
    }

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

    private function syncVehicleImage(bool $force): bool
    {
        $url = $this->vehicleRenderUrl();
        if ($url === '') {
            $this->SendDebug('Vehicle image', 'No vehicle.renderUrl was returned by MySkoda.', 0);
            return false;
        }

        if (!$this->isAllowedVehicleImageUrl($url)) {
            $this->SendDebug('Vehicle image', 'Rejected unexpected render host.', 0);
            return false;
        }

        $mediaId = $this->vehicleImageMediaId();
        if ($mediaId !== false && !$force && $this->vehicleImageBelongsToCurrentVehicle($mediaId)) {
            return true;
        }

        $download = $this->downloadVehicleImage($url);
        if ($download === null) {
            return false;
        }

        $created = false;
        if ($mediaId === false) {
            $mediaId = IPS_CreateMedia(1);
            $created = true;
            IPS_SetParent($mediaId, $this->InstanceID);
            IPS_SetIdent($mediaId, self::VEHICLE_IMAGE_IDENT);
            IPS_SetName($mediaId, $this->Translate(self::VEHICLE_IMAGE_NAME));
            IPS_SetPosition($mediaId, 970);
        }

        $fileName = 'myskoda_vehicle_' . $this->InstanceID . '.' . $download['extension'];
        $filePath = IPS_GetKernelDir() . 'media' . DIRECTORY_SEPARATOR . $fileName;

        try {
            if (!IPS_SetMediaFile($mediaId, $filePath, false)) {
                throw new RuntimeException('Could not assign the local media file.');
            }
            if (!IPS_SetMediaContent($mediaId, base64_encode($download['content']))) {
                throw new RuntimeException('Could not write vehicle image content.');
            }

            IPS_SetInfo(
                $mediaId,
                json_encode([
                    'managedBy' => 'MySkoda',
                    'sourceField' => 'vehicle.renderUrl',
                    'vehicleFingerprint' => $this->vehicleImageFingerprint(),
                    'updatedAt' => time()
                ], JSON_UNESCAPED_SLASHES) ?: ''
            );
            IPS_SendMediaEvent($mediaId);
        } catch (Throwable $error) {
            if ($created && IPS_MediaExists($mediaId)) {
                IPS_DeleteMedia($mediaId, true);
            }
            $this->SendDebug('Vehicle image', $error->getMessage(), 0);
            return false;
        }

        $this->SendDebug(
            'Vehicle image',
            sprintf('VehicleImage updated (%d bytes).', strlen($download['content'])),
            0
        );
        return true;
    }

    private function vehicleRenderUrl(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return '';
        }

        $url = $raw['vehicle']['renderUrl'] ?? '';
        return is_string($url) ? trim($url) : '';
    }

    private function vehicleImageMediaId(): int|false
    {
        $objectId = @IPS_GetObjectIDByIdent(self::VEHICLE_IMAGE_IDENT, $this->InstanceID);
        if ($objectId === false) {
            return false;
        }

        $object = IPS_GetObject($objectId);
        if ((int) ($object['ObjectType'] ?? -1) !== 5) {
            $this->SendDebug(
                'Vehicle image',
                'Object ident VehicleImage already exists but is not a media object.',
                0
            );
            return false;
        }

        $media = IPS_GetMedia($objectId);
        if ((int) ($media['MediaType'] ?? -1) !== 1) {
            $this->SendDebug(
                'Vehicle image',
                'Object ident VehicleImage already exists but is not an image medium.',
                0
            );
            return false;
        }

        return $objectId;
    }

    private function vehicleImageBelongsToCurrentVehicle(int $mediaId): bool
    {
        $media = IPS_GetMedia($mediaId);
        if (!(bool) ($media['MediaIsAvailable'] ?? false)) {
            return false;
        }

        $object = IPS_GetObject($mediaId);
        $info = json_decode((string) ($object['ObjectInfo'] ?? ''), true);
        if (!is_array($info)) {
            return false;
        }

        $stored = (string) ($info['vehicleFingerprint'] ?? '');
        $current = $this->vehicleImageFingerprint();

        return $stored !== '' && hash_equals($stored, $current);
    }

    private function vehicleImageFingerprint(): string
    {
        return hash('sha256', strtoupper(trim($this->ReadPropertyString('VIN'))));
    }

    private function isAllowedVehicleImageUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'https' && $host === 'iprenders.blob.core.windows.net';
    }

    private function downloadVehicleImage(string $url): ?array
    {
        $handle = curl_init();
        if ($handle === false) {
            $this->SendDebug('Vehicle image', 'cURL could not be initialized.', 0);
            return null;
        }

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPHEADER => [
                'Accept: image/*',
                'User-Agent: ' . self::USER_AGENT
            ]
        ]);

        $content = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $effectiveUrl = (string) curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
        curl_close($handle);

        if ($curlError !== '' || $httpCode < 200 || $httpCode >= 300 || !is_string($content) || $content === '') {
            $this->SendDebug(
                'Vehicle image',
                sprintf('Download failed: HTTP %d%s', $httpCode, $curlError !== '' ? ' - ' . $curlError : ''),
                0
            );
            return null;
        }

        if (!$this->isAllowedVehicleImageUrl($effectiveUrl !== '' ? $effectiveUrl : $url)) {
            $this->SendDebug('Vehicle image', 'Download redirected to an unexpected host.', 0);
            return null;
        }

        if (strlen($content) > self::VEHICLE_IMAGE_MAX_BYTES) {
            $this->SendDebug('Vehicle image', 'Downloaded vehicle image is larger than 15 MB.', 0);
            return null;
        }

        $extension = $this->detectVehicleImageExtension($content);
        if ($extension === null) {
            $this->SendDebug('Vehicle image', 'Downloaded data is not a supported image format.', 0);
            return null;
        }

        return [
            'content' => $content,
            'extension' => $extension
        ];
    }

    private function detectVehicleImageExtension(string $content): ?string
    {
        if (str_starts_with($content, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($content, "\xff\xd8\xff")) {
            return 'jpg';
        }
        if (str_starts_with($content, 'GIF87a') || str_starts_with($content, 'GIF89a')) {
            return 'gif';
        }
        if (str_starts_with($content, "\x00\x00\x01\x00")) {
            return 'ico';
        }

        return null;
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

        return preg_match('/\.(png|jpe?g|gif|ico)(\?|$)/i', $value) === 1;
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
