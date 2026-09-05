<?php

declare(strict_types=1);

trait MySkodaVisualizationTrait
{
    private function refreshVisualValues(?array $vehicle = null): void
    {
        $lastUpdate = @$this->GetIDForIdent('LastUpdate') !== false ? (int) $this->GetValue('LastUpdate') : 0;
        $this->SetValue('LastUpdateAge', $this->formatAgeFromTimestamp($lastUpdate));

        if ($vehicle === null) {
            $raw = json_decode($this->ReadAttributeString('RawData'), true);
            $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle']) ? $raw['vehicle'] : [];
        }

        if (@$this->GetIDForIdent('VehicleTile') !== false) {
            $this->SetValue('VehicleTile', $this->buildVehicleTileHtml($vehicle, $lastUpdate));
        }
    }

    private function buildVehicleTileHtml(array $vehicle, int $lastUpdate): string
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

        $soc = $this->firstValue([$this->safeValue('StateOfCharge'), $this->path($vehicle, 'charging.status.battery.stateOfChargeInPercent', null)]);
        $targetSoc = $this->firstValue([$this->safeValue('TargetSOC'), $this->path($vehicle, 'charging.settings.targetStateOfChargeInPercent', null)]);
        $range = $this->firstValue([$this->safeValue('Range'), $this->metersToKm($this->path($vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', null))]);
        $mileage = $this->firstValue([$this->safeValue('Mileage'), $this->path($vehicle, 'odometer.mileageInKm', null)]);
        $chargePowerW = $this->firstValue([$this->safeValue('ChargePower'), $this->kwToW($this->path($vehicle, 'charging.status.chargePowerInKw', null))]);
        $chargePowerKw = is_numeric($chargePowerW) ? ((float) $chargePowerW / 1000.0) : null;
        $remainingMinutes = $this->extractRemainingChargeMinutes($vehicle);
        $chargeMode = $this->extractChargeModeCaption($vehicle);
        $chargeState = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $chargeType = strtoupper((string) $this->path($vehicle, 'charging.status.chargeType', ''));

        $charging = $this->coerceBool($this->safeValue('Charging'));
        if ($charging === null) {
            $charging = in_array($chargeState, ['CHARGING', 'CONSERVING'], true);
        }

        $locked = $this->coerceBool($this->safeValue('Locked'));
        if ($locked === null) {
            $lockValue = strtoupper((string) $this->path($vehicle, 'status.overall.doorsLocked', $this->path($vehicle, 'status.overall.locked', 'UNKNOWN')));
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
        $targetTemperature = $this->firstValue([$this->safeValue('TargetTemperature'), $this->path($vehicle, 'airConditioning.targetTemperature.value', null)]);

        [$plugIcon, $plugLabel, $plugClass] = $this->describePlugState($chargeState, $chargeType, $charging, $chargePowerKw, $remainingMinutes);

        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $rangeText = $this->formatIntegerWithUnit($range, 'km');
        $mileageText = $this->formatIntegerWithUnit($mileage, 'km');
        $socText = is_numeric($soc) ? ((int) round((float) $soc)) . '%' : '--';
        $targetSocText = is_numeric($targetSoc) ? ((int) round((float) $targetSoc)) . '%' : '--';
        $powerText = $chargePowerKw !== null ? number_format($chargePowerKw, 1, ',', '') . ' kW' : '—';
        $timeText = $remainingMinutes !== null ? $this->formatMinutesAsHoursMinutes($remainingMinutes) : '--:--';
        $ageText = $this->formatAgeFromTimestamp($lastUpdate);
        $tempText = is_numeric($targetTemperature) ? number_format((float) $targetTemperature, 1, ',', '') . ' °C' : '—';
        $climateMode = $climateState !== '' ? $this->humanizeMode($climateState) : $this->Translate('Unknown');
        $climateText = $climate ? $this->Translate('Active') : $this->Translate('Off');
        $chargeTypeText = $chargeType !== '' ? $this->humanizeMode($chargeType) : $this->Translate('Unknown');
        $chargeClass = $charging ? 'ms-on' : 'ms-off';
        $climateClass = $climate ? 'ms-on' : 'ms-off';

        $lockIcon = $locked ? '🔒' : '🔓';
        $doorIcon = $doorsOpen ? '🚪!' : '🚪';
        $windowIcon = $windowsOpen ? '🪟!' : '🪟';
        $lightIcon = $lightsOn ? '💡' : '◌';

        $days = $this->keyExpiryDays();
        $keyWarning = ($days !== null && $days <= 30)
            ? '<div class="ms-keywarn">⚠ ' . htmlspecialchars(sprintf($this->Translate('API key expires in %d days'), $days), ENT_QUOTES, 'UTF-8') . '</div>'
            : '';

        $svg = $this->buildVehicleSvg($locked, $doorsOpen, $windowsOpen, $lightsOn, $socText, $targetSocText);
        $rangeLabel = $this->Translate('Range');
        $mileageLabel = $this->Translate('Mileage');
        $updateLabel = $this->Translate('Last update age');
        $powerLabel = $this->Translate('Charging power');
        $timeLabel = $this->Translate('Time to full');
        $limitLabel = $this->Translate('Charging limit');
        $chargingLabel = $this->Translate('Charging');
        $modeLabel = $this->Translate('Charging mode');
        $climateLabel = $this->Translate('Climate');
        $tempLabel = $this->Translate('Target temperature');

        return <<<HTML
<div class="ms-card">
<style>
.ms-card{font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif;color:inherit;color-scheme:light dark;background:transparent;padding:8px 10px;box-sizing:border-box;line-height:1.2;max-width:520px;margin:0 auto}
.ms-card *{box-sizing:border-box}.ms-head{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:2px 0 7px;border-bottom:1px solid rgba(127,127,127,.22)}
.ms-title{min-width:0;font-size:17px;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ms-age{font-size:11px;opacity:.62;white-space:nowrap}.ms-range{font-size:12px;opacity:.72;margin-top:2px}.ms-range b{font-size:15px;opacity:1}
.ms-lock{font-size:19px;line-height:1}.ms-keywarn{margin:7px 0 0;padding:6px 8px;border-radius:8px;background:rgba(245,158,11,.13);font-size:11px;font-weight:650}
.ms-hero{display:grid;grid-template-columns:minmax(128px,44%) 1fr;gap:8px;align-items:center;padding:7px 0}.ms-car{min-width:0}.ms-charge{min-width:0;padding-left:9px;border-left:1px solid rgba(127,127,127,.22)}
.ms-plugline{display:flex;align-items:center;gap:7px;margin-bottom:6px}.ms-plug{font-size:26px;line-height:1}.ms-plugtext{min-width:0}.ms-plugtitle{font-size:13px;font-weight:750}.ms-plugtype{font-size:10px;opacity:.62;margin-top:1px}
.ms-ok .ms-plugtitle{color:#22a35a}.ms-warn .ms-plugtitle{color:#d58a00}.ms-error .ms-plugtitle{color:#d94b4b}.ms-done .ms-plugtitle{color:#3f7fd8}
.ms-chargemetrics{display:grid;grid-template-columns:1fr 1fr;gap:5px 8px}.ms-metric{min-width:0}.ms-label{font-size:9px;opacity:.58;text-transform:uppercase;letter-spacing:.02em}.ms-value{font-size:13px;font-weight:720;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
.ms-status{display:flex;align-items:center;justify-content:space-between;gap:6px;padding:5px 0;border-top:1px solid rgba(127,127,127,.18);border-bottom:1px solid rgba(127,127,127,.18);font-size:11px}.ms-icons{display:flex;gap:8px;font-size:14px}.ms-km{opacity:.74;white-space:nowrap}.ms-rows{padding-top:2px}.ms-row{display:grid;grid-template-columns:62px 1fr;align-items:center;gap:5px;padding:7px 0;border-bottom:1px solid rgba(127,127,127,.16)}.ms-row:last-child{border-bottom:0}.ms-rowname{font-size:12px;font-weight:760}.ms-rowvals{display:flex;justify-content:flex-end;gap:7px;min-width:0;flex-wrap:wrap;font-size:11px}.ms-rowvals span{white-space:nowrap}.ms-strong{font-weight:720}.ms-on{color:#22a35a}.ms-off{opacity:.62}
@media(max-width:360px){.ms-card{padding:7px 7px}.ms-hero{grid-template-columns:122px 1fr;gap:5px}.ms-charge{padding-left:6px}.ms-chargemetrics{gap:4px 5px}.ms-value{font-size:12px}.ms-row{grid-template-columns:54px 1fr}.ms-rowvals{gap:5px}}
</style>
<div class="ms-head">
  <div style="min-width:0">
    <div class="ms-title">{$titleEsc}</div>
    <div class="ms-range">{$rangeLabel} <b>{$rangeText}</b></div>
  </div>
  <div style="text-align:right"><div class="ms-lock">{$lockIcon}</div><div class="ms-age">{$updateLabel} {$ageText}</div></div>
</div>
{$keyWarning}
<div class="ms-hero">
  <div class="ms-car">{$svg}</div>
  <div class="ms-charge {$plugClass}">
    <div class="ms-plugline"><div class="ms-plug">{$plugIcon}</div><div class="ms-plugtext"><div class="ms-plugtitle">{$plugLabel}</div><div class="ms-plugtype">{$chargeTypeText}</div></div></div>
    <div class="ms-chargemetrics">
      <div class="ms-metric"><div class="ms-label">{$powerLabel}</div><div class="ms-value">{$powerText}</div></div>
      <div class="ms-metric"><div class="ms-label">{$timeLabel}</div><div class="ms-value">{$timeText}</div></div>
      <div class="ms-metric"><div class="ms-label">{$limitLabel}</div><div class="ms-value">{$targetSocText}</div></div>
      <div class="ms-metric"><div class="ms-label">{$modeLabel}</div><div class="ms-value">{$chargeMode}</div></div>
    </div>
  </div>
</div>
<div class="ms-status"><div class="ms-icons"><span title="Lock">{$lockIcon}</span><span title="Doors">{$doorIcon}</span><span title="Windows">{$windowIcon}</span><span title="Lights">{$lightIcon}</span></div><div class="ms-km">{$mileageLabel}: <b>{$mileageText}</b></div></div>
<div class="ms-rows">
  <div class="ms-row"><div class="ms-rowname">{$chargingLabel}</div><div class="ms-rowvals"><span class="ms-strong {$chargeClass}">{$plugLabel}</span><span>{$targetSocText}</span><span>{$chargeMode}</span></div></div>
  <div class="ms-row"><div class="ms-rowname">{$climateLabel}</div><div class="ms-rowvals"><span class="ms-strong {$climateClass}">{$climateText}</span><span>{$tempLabel}: {$tempText}</span><span>{$climateMode}</span></div></div>
</div>
</div>
HTML;
    }

    private function buildVehicleSvg(bool $locked, bool $doorsOpen, bool $windowsOpen, bool $lightsOn, string $socText, string $targetSocText): string
    {
        $outline = $locked ? '#27a35b' : '#d58a00';
        $door = $doorsOpen ? '#d58a00' : 'rgba(127,127,127,.20)';
        $window = $windowsOpen ? '#d58a00' : 'rgba(127,127,127,.15)';
        $light = $lightsOn ? '#d9ab00' : 'rgba(127,127,127,.18)';
        $limitLabel = htmlspecialchars($this->Translate('Charging limit'), ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg viewBox="0 0 160 190" width="100%" height="165" role="img" aria-label="Vehicle">
  <path d="M51 13 Q80 2 109 13 Q126 29 128 57 L132 129 Q130 158 110 176 Q80 187 50 176 Q30 158 28 129 L32 57 Q34 29 51 13Z" fill="rgba(127,127,127,.055)" stroke="{$outline}" stroke-width="2.8"/>
  <path d="M50 31 Q80 21 110 31 L113 67 Q80 58 47 67Z" fill="{$window}" stroke="rgba(127,127,127,.28)"/>
  <path d="M47 124 Q80 133 113 124 L110 159 Q80 169 50 159Z" fill="{$window}" stroke="rgba(127,127,127,.28)"/>
  <rect x="19" y="61" width="11" height="30" rx="5" fill="{$door}"/><rect x="130" y="61" width="11" height="30" rx="5" fill="{$door}"/>
  <rect x="19" y="102" width="11" height="30" rx="5" fill="{$door}"/><rect x="130" y="102" width="11" height="30" rx="5" fill="{$door}"/>
  <rect x="54" y="14" width="52" height="5" rx="2.5" fill="{$light}"/><rect x="54" y="171" width="52" height="5" rx="2.5" fill="{$light}"/>
  <ellipse cx="28" cy="49" rx="7" ry="15" fill="rgba(127,127,127,.27)"/><ellipse cx="132" cy="49" rx="7" ry="15" fill="rgba(127,127,127,.27)"/>
  <ellipse cx="28" cy="143" rx="7" ry="15" fill="rgba(127,127,127,.27)"/><ellipse cx="132" cy="143" rx="7" ry="15" fill="rgba(127,127,127,.27)"/>
  <rect x="48" y="72" width="64" height="47" rx="9" fill="rgba(35,157,210,.16)" stroke="rgba(35,157,210,.65)" stroke-width="1.5"/>
  <text x="80" y="94" text-anchor="middle" style="font:750 24px system-ui;fill:currentColor">{$socText}</text>
  <text x="80" y="110" text-anchor="middle" style="font:600 9px system-ui;fill:currentColor;opacity:.68">{$limitLabel} {$targetSocText}</text>
</svg>
SVG;
    }

    private function describePlugState(string $chargeState, string $chargeType, ?bool $charging, ?float $chargePowerKw, ?int $remainingMinutes): array
    {
        $state = strtoupper($chargeState);
        $type = strtoupper($chargeType);

        if (in_array($state, ['ERROR', 'FAULT', 'UNAVAILABLE'], true)) {
            return ['⛔', $this->Translate('Charging error'), 'ms-error'];
        }
        if ($state === 'CHARGING_INTERRUPTED') {
            return ['⚠️', $this->Translate('Charging interrupted'), 'ms-warn'];
        }
        if ($state === 'CONNECT_CABLE') {
            return ['🔌', $this->Translate('Connect charging cable'), 'ms-warn'];
        }
        if ($state === 'DISCHARGING') {
            return ['↔️', $this->Translate('Discharging'), 'ms-warn'];
        }
        if ($charging || ($chargePowerKw !== null && $chargePowerKw > 0.05) || in_array($state, ['CHARGING', 'CONSERVING'], true)) {
            return ['⚡', $state === 'CONSERVING' ? $this->Translate('Conserving charge') : $this->Translate('Charging in progress'), 'ms-ok'];
        }
        if (in_array($state, ['COMPLETED', 'FINISHED', 'TARGET_REACHED'], true) || ($remainingMinutes === 0 && $type !== '' && $type !== 'OFF')) {
            return ['✓', $this->Translate('Charge target reached'), 'ms-done'];
        }
        if ($state === 'READY_FOR_CHARGING' || in_array($state, ['CONNECTED', 'AVAILABLE', 'WAITING_FOR_CHARGING', 'READY'], true)) {
            return ['🔌', $this->Translate('Ready for charging'), 'ms-done'];
        }
        if ($type !== '' && $type !== 'OFF') {
            return ['🔌', $this->Translate('Connected'), 'ms-warn'];
        }
        return ['○', $this->Translate('Not connected'), 'ms-idle'];
    }

    private function extractRemainingChargeMinutes(array $vehicle): ?int
    {
        $minutes = $this->firstValue([
            $this->path($vehicle, 'charging.status.remainingTimeToFullyChargedInMinutes', null),
            $this->path($vehicle, 'charging.status.remainingChargingTimeInMinutes', null)
        ]);
        if (is_numeric($minutes)) {
            return max(0, (int) round((float) $minutes));
        }
        $fullAt = $this->toTimestamp($this->path($vehicle, 'charging.status.fullyChargedAt', null));
        if ($fullAt > 0) {
            return max(0, (int) floor(($fullAt - time()) / 60));
        }
        return null;
    }

    private function extractChargeModeCaption(array $vehicle): string
    {
        $mode = $this->path($vehicle, 'charging.settings.preferredChargeMode', '');
        if ($mode === '' && @$this->GetIDForIdent('ChargeMode') !== false) {
            $map = json_decode($this->ReadAttributeString('ChargeModeMap'), true);
            if (is_array($map)) {
                $mode = (string) ($map[(string) ((int) $this->GetValue('ChargeMode'))] ?? '');
            }
        }
        return $mode !== '' ? $this->humanizeMode((string) $mode) : $this->Translate('Unknown');
    }

    private function formatAgeFromTimestamp(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return '--:--';
        }
        $seconds = max(0, time() - $timestamp);
        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    private function formatMinutesAsHoursMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function formatIntegerWithUnit(mixed $value, string $unit): string
    {
        if (!is_numeric($value)) {
            return '—';
        }
        return number_format((float) $value, 0, ',', '.') . ' ' . $unit;
    }
}
