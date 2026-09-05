<?php

declare(strict_types=1);

/**
 * Native, smartphone-focused visualization tile for MySkoda 1.6.
 *
 * Design and module architecture are inspired by da8ter's public
 * TileVisu-Kachelsammlung. No source code or image assets are copied.
 */
trait MySkodaVisualizationV16Trait
{
    private function refreshVisualValues(?array $vehicle = null): void
    {
        $lastUpdate = @$this->GetIDForIdent('LastUpdate') !== false ? (int) $this->GetValue('LastUpdate') : 0;
        $this->setIfExists('LastUpdateAge', $this->formatAgeFromTimestamp($lastUpdate));

        if ($vehicle === null) {
            $raw = json_decode($this->ReadAttributeString('RawData'), true);
            $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle']) ? $raw['vehicle'] : [];
        }

        // Backward compatibility for existing 1.1-1.5 visualizations.
        if (@$this->GetIDForIdent('VehicleTile') !== false) {
            $this->SetValue('VehicleTile', $this->buildVehicleTileHtml($vehicle, $lastUpdate));
        }

        try {
            $this->UpdateVisualizationValue($this->getVisualizationUpdateMessage($vehicle, $lastUpdate));
        } catch (Throwable $e) {
            // No visualization may currently be open. The next tile load gets a
            // complete initial state from GetVisualizationTile().
        }
    }

    public function GetVisualizationTile(): string
    {
        $module = @file_get_contents(__DIR__ . '/../module.html');
        if ($module === false) {
            return '<div>MySkoda visualization file is missing.</div>';
        }

        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle']) ? $raw['vehicle'] : [];
        $lastUpdate = @$this->GetIDForIdent('LastUpdate') !== false ? (int) $this->GetValue('LastUpdate') : 0;
        $initialHandling = '<script>handleMessage(' . json_encode($this->getVisualizationUpdateMessage($vehicle, $lastUpdate)) . ');</script>';

        return $module . $initialHandling;
    }

    private function getVisualizationUpdateMessage(array $vehicle, int $lastUpdate): string
    {
        return json_encode(
            $this->getVisualizationState($vehicle, $lastUpdate),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function getVisualizationState(array $vehicle, int $lastUpdate): array
    {
        $title = trim((string) $this->path($vehicle, 'name', ''));
        if ($title === '') {
            $title = trim((string) $this->path($vehicle, 'licensePlate', ''));
        }
        if ($title === '') {
            $title = trim((string) $this->ReadPropertyString('VIN'));
        }
        if ($title === '') {
            $title = $this->Translate('Vehicle');
        }

        $soc = $this->firstValue([
            $this->safeValue('StateOfCharge'),
            $this->path($vehicle, 'charging.status.battery.stateOfChargeInPercent', null)
        ]);
        $soc = is_numeric($soc) ? max(0, min(100, (int) round((float) $soc))) : null;

        $targetSoc = $this->firstValue([
            $this->safeValue('TargetSOC'),
            $this->path($vehicle, 'charging.settings.targetStateOfChargeInPercent', null)
        ]);
        $targetSoc = is_numeric($targetSoc) ? max(0, min(100, (int) round((float) $targetSoc))) : null;

        $range = $this->firstValue([
            $this->safeValue('Range'),
            $this->metersToKm($this->path($vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', null))
        ]);
        $mileage = $this->firstValue([
            $this->safeValue('Mileage'),
            $this->path($vehicle, 'odometer.mileageInKm', null)
        ]);

        $chargePowerW = $this->firstValue([
            $this->safeValue('ChargePower'),
            $this->kwToW($this->path($vehicle, 'charging.status.chargePowerInKw', null))
        ]);
        $chargePowerKw = is_numeric($chargePowerW) ? ((float) $chargePowerW / 1000.0) : null;
        $chargeState = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $chargeType = strtoupper((string) $this->path($vehicle, 'charging.status.chargeType', ''));
        $remainingMinutes = $this->extractRemainingChargeMinutes($vehicle);
        $chargeMode = $this->extractChargeModeCaption($vehicle);

        $charging = $this->coerceBool($this->safeValue('Charging'));
        if ($charging === null) {
            $charging = in_array($chargeState, ['CHARGING', 'CONSERVING'], true);
        }

        $locked = $this->coerceBool($this->safeValue('Locked'));
        if ($locked === null) {
            $lockValue = strtoupper((string) $this->path(
                $vehicle,
                'status.overall.doorsLocked',
                $this->path($vehicle, 'status.overall.locked', 'UNKNOWN')
            ));
            $locked = in_array($lockValue, ['YES', 'LOCKED'], true);
        }

        $doorsOpen = $this->coerceBool($this->safeValue('DoorsOpen'));
        if ($doorsOpen === null) {
            $doorsOpen = strtoupper((string) $this->path($vehicle, 'status.overall.doors', 'CLOSED')) === 'OPEN';
        }
        $windowsOpen = $this->coerceBool($this->safeValue('WindowsOpen'));
        if ($windowsOpen === null) {
            $windowsOpen = strtoupper((string) $this->path($vehicle, 'status.overall.windows', 'CLOSED')) === 'OPEN';
        }
        $lightsOn = strtoupper((string) $this->path($vehicle, 'status.overall.lights', 'OFF')) === 'ON';

        $climateState = strtoupper((string) $this->path($vehicle, 'airConditioning.state', 'OFF'));
        $climate = $this->coerceBool($this->safeValue('Climate'));
        if ($climate === null) {
            $climate = in_array($climateState, ['COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true);
        }
        $targetTemperature = $this->firstValue([
            $this->safeValue('TargetTemperature'),
            $this->path($vehicle, 'airConditioning.targetTemperature.value', null)
        ]);

        [$plugIcon, $plugLabel, $plugClass] = $this->describePlugState(
            $chargeState,
            $chargeType,
            $charging,
            $chargePowerKw,
            $remainingMinutes
        );

        $cableConnected = $this->isChargeCableConnected($chargeState, $chargeType);
        $days = $this->keyExpiryDays();

        return [
            'title' => $title,
            'age' => $this->formatAgeFromTimestamp($lastUpdate),
            'range' => $this->formatIntegerWithUnit($range, 'km'),
            'mileage' => $this->formatIntegerWithUnit($mileage, 'km'),
            'soc' => $soc,
            'targetSoc' => $targetSoc,
            'targetSocText' => $targetSoc !== null ? $targetSoc . '%' : '—',
            'locked' => (bool) $locked,
            'doorsOpen' => (bool) $doorsOpen,
            'windowsOpen' => (bool) $windowsOpen,
            'lightsOn' => (bool) $lightsOn,
            'lockText' => $locked ? $this->Translate('Locked') : $this->Translate('Unlocked'),
            'doorsText' => $doorsOpen ? $this->Translate('Doors open') : $this->Translate('Doors closed'),
            'windowsText' => $windowsOpen ? $this->Translate('Windows open') : $this->Translate('Windows closed'),
            'lightsText' => $lightsOn ? $this->Translate('Lights on') : $this->Translate('Lights off'),
            'charge' => [
                'icon' => $plugIcon,
                'label' => $plugLabel,
                'stateClass' => str_replace('ms-', '', $plugClass),
                'type' => ($chargeType !== '' && $chargeType !== 'OFF') ? $this->humanizeMode($chargeType) : '',
                'power' => $this->formatChargingPower($chargePowerKw, $cableConnected),
                'time' => $remainingMinutes !== null ? $this->formatMinutesAsHoursMinutes($remainingMinutes) : '—',
                'limit' => $targetSoc !== null ? $targetSoc . '%' : '—',
                'mode' => $chargeMode
            ],
            'climate' => [
                'active' => (bool) $climate,
                'mode' => $this->climateModeCaption($climateState, (bool) $climate),
                'symbol' => $this->climateSymbol($climateState),
                'temperature' => is_numeric($targetTemperature)
                    ? number_format((float) $targetTemperature, 1, ',', '') . ' °C'
                    : '—'
            ],
            'keyWarning' => $days !== null && $days <= 30,
            'keyWarningText' => ($days !== null && $days <= 30)
                ? sprintf($this->Translate('API key expires in %d days'), $days)
                : '',
            'labels' => [
                'range' => $this->Translate('Range'),
                'updated' => $this->Translate('Last update age'),
                'power' => $this->Translate('Charging power'),
                'time' => $this->Translate('Time to full'),
                'limit' => $this->Translate('Charging limit'),
                'mode' => $this->Translate('Charging mode'),
                'climate' => $this->Translate('Climate'),
                'targetTemperature' => $this->Translate('Target temperature'),
                'mileage' => $this->Translate('Mileage')
            ]
        ];
    }
}
