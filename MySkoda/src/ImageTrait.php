<?php

declare(strict_types=1);

trait MySkodaImageTrait
{
    private const VEHICLE_IMAGE_IDENT = 'VehicleImage';
    private const VEHICLE_IMAGE_NAME = 'Vehicle image';
    private const VEHICLE_IMAGE_MAX_BYTES = 15_000_000;
    private const VEHICLE_IMAGE_VARIANTS = [
        'EXTERIOR_FRONT' => [
            'ident' => 'VehicleImageFront',
            'name' => 'Vehicle image front',
            'position' => 41,
            'suffix' => '19201080dayvext_front1080.png'
        ],
        'EXTERIOR_REAR' => [
            'ident' => 'VehicleImageRear',
            'name' => 'Vehicle image rear',
            'position' => 42,
            'suffix' => '19201080dayvext_rear1080.png'
        ],
        'INTERIOR_FRONT' => [
            'ident' => 'VehicleImageInteriorFront',
            'name' => 'Vehicle image interior front',
            'position' => 43,
            'suffix' => '19201080studiovint_front1080.png'
        ],
        'INTERIOR_SIDE' => [
            'ident' => 'VehicleImageInteriorSide',
            'name' => 'Vehicle image interior side',
            'position' => 44,
            'suffix' => '19201080studiovint_side1080.png'
        ],
        'INTERIOR_BOOT' => [
            'ident' => 'VehicleImageInteriorBoot',
            'name' => 'Vehicle image interior boot',
            'position' => 45,
            'suffix' => '19201080studiovint_boot1080.png'
        ]
    ];

    public function RefreshVehicleImage(): bool
    {
        return $this->syncVehicleImage(true);
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

        $primaryResult = $this->syncVehicleImageMedium(
            self::VEHICLE_IMAGE_IDENT,
            self::VEHICLE_IMAGE_NAME,
            40,
            $url,
            'vehicle.renderUrl',
            $force
        );

        $variantFingerprint = hash(
            'sha256',
            $this->vehicleImageFingerprint() . '|' . $url
        );
        $scanVariants = $force
            || $variantFingerprint !== $this->ReadAttributeString('VehicleImageVariantsFingerprint');

        if (!$scanVariants) {
            return $primaryResult;
        }

        foreach ($this->derivedVehicleRenderUrls($url) as $viewType => $variant) {
            $this->syncVehicleImageMedium(
                $variant['ident'],
                $variant['name'],
                $variant['position'],
                $variant['url'],
                'vehicle.renderUrl:' . $viewType,
                true
            );
        }

        $this->WriteAttributeString('VehicleImageVariantsFingerprint', $variantFingerprint);
        return $primaryResult;
    }

    private function syncVehicleImageMedium(
        string $ident,
        string $name,
        int $position,
        string $url,
        string $sourceField,
        bool $force
    ): bool {
        if (!$this->isAllowedVehicleImageUrl($url)) {
            $this->SendDebug($ident, 'Rejected unexpected render host.', 0);
            return false;
        }

        $mediaId = $this->vehicleImageMediaId($ident);
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
            IPS_SetIdent($mediaId, $ident);
            IPS_SetName($mediaId, $this->Translate($name));
            IPS_SetPosition($mediaId, $position);
        }

        $fileStem = $ident === self::VEHICLE_IMAGE_IDENT
            ? 'myskoda_vehicle_' . $this->InstanceID
            : 'myskoda_vehicle_' . $this->InstanceID . '_' . strtolower($ident);
        $filePath = IPS_GetKernelDir()
            . 'media'
            . DIRECTORY_SEPARATOR
            . $fileStem
            . '.'
            . $download['extension'];

        try {
            if (!IPS_SetMediaFile($mediaId, $filePath, false)) {
                throw new RuntimeException('Could not assign the local media file.');
            }
            if (!IPS_SetMediaContent($mediaId, base64_encode($download['content']))) {
                throw new RuntimeException('Could not write vehicle image content.');
            }
            IPS_SetInfo($mediaId, json_encode([
                'managedBy' => 'MySkoda',
                'sourceField' => $sourceField,
                'vehicleFingerprint' => $this->vehicleImageFingerprint(),
                'updatedAt' => time()
            ], JSON_UNESCAPED_SLASHES) ?: '');
            IPS_SendMediaEvent($mediaId);
        } catch (Throwable $error) {
            if ($created && IPS_MediaExists($mediaId)) {
                IPS_DeleteMedia($mediaId, true);
            }
            $this->SendDebug($ident, $error->getMessage(), 0);
            return false;
        }

        $this->SendDebug(
            $ident,
            sprintf('%s updated (%d bytes).', $ident, strlen($download['content'])),
            0
        );
        return true;
    }

    private function derivedVehicleRenderUrls(string $sourceUrl): array
    {
        $result = [];
        foreach (self::VEHICLE_IMAGE_VARIANTS as $viewType => $definition) {
            $url = $this->deriveVehicleRenderUrl($sourceUrl, $definition['suffix']);
            if ($url === '' || $url === $sourceUrl) {
                continue;
            }
            $result[$viewType] = [
                'ident' => $definition['ident'],
                'name' => $definition['name'],
                'position' => $definition['position'],
                'url' => $url
            ];
        }
        return $result;
    }

    private function deriveVehicleRenderUrl(string $sourceUrl, string $suffix): string
    {
        $parts = parse_url($sourceUrl);
        if (!is_array($parts)) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '') {
            return '';
        }

        $pattern = '~-19201080(?:dayvext_(?:front|side|rear)|studiovint_(?:front|side|boot))1080\.png$~i';
        $newPath = preg_replace($pattern, '-' . $suffix, $path, 1, $count);
        if (!is_string($newPath) || $count !== 1) {
            return '';
        }

        $scheme = (string) ($parts['scheme'] ?? '');
        $host = (string) ($parts['host'] ?? '');
        if ($scheme === '' || $host === '') {
            return '';
        }

        $url = $scheme . '://' . $host;
        if (isset($parts['port'])) {
            $url .= ':' . (int) $parts['port'];
        }
        $url .= $newPath;
        if (isset($parts['query']) && $parts['query'] !== '') {
            $url .= '?' . $parts['query'];
        }
        return $url;
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

    private function vehicleImageMediaId(string $ident = self::VEHICLE_IMAGE_IDENT): int|false
    {
        $objectId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if ($objectId === false) {
            return false;
        }
        $object = IPS_GetObject($objectId);
        if ((int) ($object['ObjectType'] ?? -1) !== 5) {
            $this->SendDebug($ident, 'Object ident already exists but is not a media object.', 0);
            return false;
        }
        $media = IPS_GetMedia($objectId);
        if ((int) ($media['MediaType'] ?? -1) !== 1) {
            $this->SendDebug($ident, 'Object ident already exists but is not an image medium.', 0);
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
            CURLOPT_HTTPHEADER => ['Accept: image/*', 'User-Agent: ' . self::USER_AGENT]
        ]);
        $content = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $effectiveUrl = (string) curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
        curl_close($handle);
        if ($curlError !== '' || $httpCode < 200 || $httpCode >= 300 || !is_string($content) || $content === '') {
            $this->SendDebug('Vehicle image', sprintf('Download failed: HTTP %d%s', $httpCode, $curlError !== '' ? ' - ' . $curlError : ''), 0);
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
        return ['content' => $content, 'extension' => $extension];
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
}
