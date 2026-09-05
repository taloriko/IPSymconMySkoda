<?php

declare(strict_types=1);

/**
 * MySkoda 1.7 model-aware visualization state.
 *
 * Model selection priorities:
 *   1. Explicit instance configuration
 *   2. Model / specification metadata returned by MySkoda
 *   3. Known system model IDs
 *   4. Weak vehicle-name hint
 *   5. Generic vehicle
 *
 * The VIN alone is intentionally not used to force Enyaq vs. Elroq because
 * both families can share the Skoda vehicle-type code NY.
 */
trait MySkodaVisualizationV17Trait
{
    private function getVisualizationState(array $vehicle, int $lastUpdate): array
    {
        $state = $this->getVisualizationStateV16($vehicle, $lastUpdate);

        [$design, $detectedLabel] = $this->resolveVehicleDesign($vehicle);
        $state['vehicleDesign'] = $design;
        $state['vehicleDesignLabel'] = $detectedLabel;

        $sunroofRaw = $this->firstPresentPath($vehicle, [
            'status.detail.sunroof',
            'status.overall.sunroof',
            'status.detail.sunRoof'
        ]);
        $trunkRaw = $this->firstPresentPath($vehicle, [
            'status.detail.trunk',
            'status.overall.trunk'
        ]);
        $bonnetRaw = $this->firstPresentPath($vehicle, [
            'status.detail.bonnet',
            'status.overall.bonnet',
            'status.detail.hood'
        ]);

        $sunroof = $this->normalizeOpeningStatus($sunroofRaw);
        $trunk = $this->normalizeOpeningStatus($trunkRaw);
        $bonnet = $this->normalizeOpeningStatus($bonnetRaw);

        $state['sunroofAvailable'] = $sunroof['available'];
        $state['sunroofOpen'] = $sunroof['open'];
        $state['sunroofText'] = $sunroof['available']
            ? ($sunroof['open'] ? $this->Translate('Open') : $this->Translate('Closed'))
            : $this->Translate('Not available');

        $state['trunkAvailable'] = $trunk['available'];
        $state['trunkOpen'] = $trunk['open'];
        $state['trunkText'] = $trunk['available']
            ? ($trunk['open'] ? $this->Translate('Open') : $this->Translate('Closed'))
            : $this->Translate('Not available');

        $state['bonnetAvailable'] = $bonnet['available'];
        $state['bonnetOpen'] = $bonnet['open'];
        $state['bonnetText'] = $bonnet['available']
            ? ($bonnet['open'] ? $this->Translate('Open') : $this->Translate('Closed'))
            : $this->Translate('Not available');

        $state['labels']['vehicleLocked'] = $this->Translate('Vehicle locked');
        $state['labels']['doors'] = $this->Translate('Doors');
        $state['labels']['windows'] = $this->Translate('Windows');
        $state['labels']['sunroof'] = $this->Translate('Sunroof');
        $state['labels']['lights'] = $this->Translate('Lights');
        $state['labels']['trunk'] = $this->Translate('Trunk');
        $state['labels']['bonnet'] = $this->Translate('Bonnet');
        $state['labels']['closed'] = $this->Translate('Closed');
        $state['labels']['open'] = $this->Translate('Open');
        $state['labels']['on'] = $this->Translate('On');
        $state['labels']['off'] = $this->Translate('Off');
        $state['labels']['yes'] = $this->Translate('Yes');
        $state['labels']['no'] = $this->Translate('No');

        $chargeState = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $chargeType = strtoupper((string) $this->path($vehicle, 'charging.status.chargeType', ''));
        $cableConnected = $this->isChargeCableConnected($chargeState, $chargeType);
        $state['charge']['hint'] = $cableConnected
            ? (string) ($state['charge']['type'] ?? '')
            : $this->Translate('Vehicle is not connected');

        return $state;
    }

    /** @return array{0:string,1:string} */
    private function resolveVehicleDesign(array $vehicle): array
    {
        $configured = strtolower(trim($this->ReadPropertyString('VehicleDesign')));
        $allowed = ['auto', 'enyaq', 'elroq', 'epiq', 'generic'];
        if (!in_array($configured, $allowed, true)) {
            $configured = 'auto';
        }

        if ($configured !== 'auto') {
            return [$configured, ucfirst($configured)];
        }

        $strongCandidates = [
            $this->path($vehicle, 'specification.model', null),
            $this->path($vehicle, 'specification.title', null),
            $this->path($vehicle, 'model', null),
            $this->path($vehicle, 'modelName', null),
            $this->path($vehicle, 'vehicleModel', null),
            $this->path($vehicle, 'vehicle.model', null)
        ];

        foreach ($strongCandidates as $candidate) {
            $model = $this->modelFromText($candidate);
            if ($model !== null) {
                return [$model, ucfirst($model)];
            }
        }

        $systemCode = strtoupper(trim((string) $this->firstValue([
            $this->path($vehicle, 'specification.systemModelId', null),
            $this->path($vehicle, 'systemModelId', null),
            $this->path($vehicle, 'specification.systemCode', null)
        ])));

        if ($systemCode !== '') {
            // Known identifiers from Skoda vehicle metadata. Keep this list
            // intentionally conservative; manual override is always available.
            if (str_starts_with($systemCode, '5A')) {
                return ['enyaq', 'Enyaq'];
            }
            if (str_starts_with($systemCode, 'PYL')) {
                return ['elroq', 'Elroq'];
            }
            if (str_starts_with($systemCode, 'PX1')) {
                return ['epiq', 'Epiq'];
            }
        }

        // Vehicle name is user-editable, therefore only a weak last hint.
        $weakModel = $this->modelFromText($this->path($vehicle, 'name', null), true);
        if ($weakModel !== null) {
            return [$weakModel, ucfirst($weakModel)];
        }

        return ['generic', $this->Translate('Generic vehicle')];
    }

    private function modelFromText(mixed $value, bool $allowNickname = false): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $text = strtoupper(trim($value));
        if (str_contains($text, 'ELROQ')) {
            return 'elroq';
        }
        if (str_contains($text, 'EPIQ')) {
            return 'epiq';
        }
        if (str_contains($text, 'ENYAQ')) {
            return 'enyaq';
        }
        if ($allowNickname && preg_match('/(^|[^A-Z])ENY([^A-Z]|$)/', $text) === 1) {
            return 'enyaq';
        }
        return null;
    }

    private function firstPresentPath(array $vehicle, array $paths): mixed
    {
        $sentinel = new stdClass();
        foreach ($paths as $path) {
            $value = $this->path($vehicle, $path, $sentinel);
            if ($value !== $sentinel) {
                return $value;
            }
        }
        return null;
    }

    /** @return array{available:bool,open:bool} */
    private function normalizeOpeningStatus(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['available' => false, 'open' => false];
        }

        $value = strtoupper(trim((string) $raw));
        if (in_array($value, ['UNKNOWN', 'UNSUPPORTED', 'NOT_SUPPORTED', 'NOT_AVAILABLE'], true)) {
            return ['available' => false, 'open' => false];
        }

        return [
            'available' => true,
            'open' => in_array($value, ['OPEN', 'OPENED', 'YES'], true)
        ];
    }
}
