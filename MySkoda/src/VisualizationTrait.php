<?php

declare(strict_types=1);

trait MySkodaVisualizationTrait
{
    private function applyHtmlBoxProfile(string $ident): void
    {
        if (!function_exists('IPS_VariableProfileExists') || !IPS_VariableProfileExists('~HTMLBox')) {
            return;
        }
        $id = @$this->GetIDForIdent($ident);
        if ($id !== false) {
            IPS_SetVariableCustomProfile($id, '~HTMLBox');
        }
    }

    private function refreshVisualValues(?array $vehicle = null): void
    {
        $lastUpdate = 0;
        if (@$this->GetIDForIdent('LastUpdate') !== false) {
            $lastUpdate = (int) $this->GetValue('LastUpdate');
        }
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
            $lockedValue = strtoupper((string) ($this->path($vehicle, 'status.overall.doorsLocked', $this->path($vehicle, 'status.overall.locked', 'UNKNOWN'))));
            $locked = $lockedValue === 'YES' || $lockedValue === 'LOCKED';
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

        $climate = $this->coerceBool($this->safeValue('Climate'));
        $climateState = strtoupper((string) $this->path($vehicle, 'airConditioning.state', 'OFF'));
        if ($climate === null) {
            $climate = in_array($climateState, ['COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true);
        }
        $targetTemperature = $this->firstValue([$this->safeValue('TargetTemperature'), $this->path($vehicle, 'airConditioning.targetTemperature.value', null)]);

        [$plugStateIcon, $plugStateLabel, $plugClass] = $this->describePlugState($chargeState, $chargeType, $charging, $chargePowerKw, $remainingMinutes);

        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $rangeText = $this->formatIntegerWithUnit($range, 'km');
        $mileageText = $this->formatIntegerWithUnit($mileage, 'km');
        $socText = $soc !== null ? ((int) round((float) $soc)) . '%' : '--';
        $targetSocText = $targetSoc !== null ? ((int) round((float) $targetSoc)) . '%' : '--';
        $powerText = $chargePowerKw !== null ? number_format($chargePowerKw, 1, ',', '') . ' kW' : '—';
        $timeToFullText = $remainingMinutes !== null ? $this->formatMinutesAsHoursMinutes($remainingMinutes) : '--:--';
        $ageText = $this->formatAgeFromTimestamp($lastUpdate);
        $climateText = $climate ? $this->Translate('Active') : $this->Translate('Off');
        $climateModeText = $climateState !== '' ? $this->humanizeMode($climateState) : $this->Translate('Unknown');
        $tempText = is_numeric($targetTemperature) ? number_format((float) $targetTemperature, 1, ',', '') . ' °C' : '—';
        $lockBadge = $locked ? '🔒 ' . $this->Translate('Locked') : '🔓 ' . $this->Translate('Unlocked');
        $doorsBadge = $doorsOpen ? '🚪 ' . $this->Translate('Doors open') : '🚪 ' . $this->Translate('Doors closed');
        $windowsBadge = $windowsOpen ? '🪟 ' . $this->Translate('Windows open') : '🪟 ' . $this->Translate('Windows closed');
        $lightsBadge = $lightsOn ? '💡 ' . $this->Translate('Lights on') : '💡 ' . $this->Translate('Lights off');
        $chargingBadge = $charging ? '⚡ ' . $this->Translate('Charging') : '🔌 ' . $this->Translate('Not charging');
        $climateBadge = $climate ? '🌡️ ' . $this->Translate('Air conditioning') : '🌡️ ' . $this->Translate('Climate off');

        $svg = $this->buildVehicleSvg($locked, $doorsOpen, $windowsOpen, $lightsOn, $socText, $targetSocText, $rangeText);

        return <<<HTML
<div class="mskoda-card">
  <style>
    .mskoda-card{font-family:Arial,Helvetica,sans-serif;color:inherit;background:transparent;box-sizing:border-box;padding:12px;border:1px solid rgba(127,127,127,.24);border-radius:14px;color-scheme:light dark}
    .mskoda-card *{box-sizing:border-box}
    .mskoda-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px}
    .mskoda-title{font-size:1.2rem;font-weight:700;line-height:1.2}
    .mskoda-sub{font-size:.85rem;opacity:.72;margin-top:2px}
    .mskoda-badges{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}
    .mskoda-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:999px;border:1px solid rgba(127,127,127,.22);background:rgba(127,127,127,.10);font-size:.78rem;white-space:nowrap}
    .mskoda-main{display:grid;grid-template-columns:minmax(280px,1.7fr) minmax(180px,1fr);gap:12px;align-items:stretch}
    .mskoda-panel{border:1px solid rgba(127,127,127,.20);background:rgba(127,127,127,.08);border-radius:12px;padding:12px}
    .mskoda-carwrap{display:flex;flex-direction:column;gap:10px}
    .mskoda-statrow{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
    .mskoda-stat{padding:10px;border-radius:10px;background:rgba(127,127,127,.10);border:1px solid rgba(127,127,127,.18)}
    .mskoda-stat-label{font-size:.78rem;opacity:.72;margin-bottom:4px}
    .mskoda-stat-value{font-size:1rem;font-weight:700}
    .mskoda-plug{display:flex;flex-direction:column;gap:10px;height:100%;justify-content:space-between}
    .mskoda-plug-state{display:flex;align-items:center;gap:10px;padding:12px;border-radius:12px;border:1px solid rgba(127,127,127,.20);background:rgba(127,127,127,.08)}
    .mskoda-plug-icon{font-size:2rem;line-height:1}
    .mskoda-plug-title{font-size:1rem;font-weight:700}
    .mskoda-plug-sub{font-size:.82rem;opacity:.72}
    .mskoda-status-ok{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.28)}
    .mskoda-status-warn{background:rgba(245,158,11,.14);border-color:rgba(245,158,11,.30)}
    .mskoda-status-idle{background:rgba(127,127,127,.10);border-color:rgba(127,127,127,.20)}
    .mskoda-status-done{background:rgba(59,130,246,.14);border-color:rgba(59,130,246,.28)}
    .mskoda-status-error{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.30)}
    .mskoda-sections{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:12px}
    .mskoda-section-title{font-size:.86rem;font-weight:700;margin-bottom:10px;opacity:.9}
    .mskoda-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .mskoda-item{padding:9px 10px;border-radius:10px;background:rgba(127,127,127,.08);border:1px solid rgba(127,127,127,.16)}
    .mskoda-item-label{font-size:.75rem;opacity:.68;margin-bottom:4px}
    .mskoda-item-value{font-size:.95rem;font-weight:700;line-height:1.2}
    .mskoda-foot{margin-top:10px;font-size:.78rem;opacity:.68;text-align:right}
    @media (max-width:700px){.mskoda-main,.mskoda-sections,.mskoda-statrow,.mskoda-grid{grid-template-columns:1fr}.mskoda-badges{justify-content:flex-start}}
  </style>
  <div class="mskoda-head">
    <div>
      <div class="mskoda-title">{$titleEsc}</div>
      <div class="mskoda-sub">{$this->Translate('Last update age')}: {$ageText}</div>
    </div>
    <div class="mskoda-badges">
      <div class="mskoda-badge">{$lockBadge}</div>
      <div class="mskoda-badge">{$doorsBadge}</div>
      <div class="mskoda-badge">{$windowsBadge}</div>
      <div class="mskoda-badge">{$lightsBadge}</div>
    </div>
  </div>

  <div class="mskoda-main">
    <div class="mskoda-panel mskoda-carwrap">
      {$svg}
      <div class="mskoda-statrow">
        <div class="mskoda-stat">
          <div class="mskoda-stat-label">{$this->Translate('State of charge')}</div>
          <div class="mskoda-stat-value">{$socText}</div>
        </div>
        <div class="mskoda-stat">
          <div class="mskoda-stat-label">{$this->Translate('Range')}</div>
          <div class="mskoda-stat-value">{$rangeText}</div>
        </div>
        <div class="mskoda-stat">
          <div class="mskoda-stat-label">{$this->Translate('Mileage')}</div>
          <div class="mskoda-stat-value">{$mileageText}</div>
        </div>
      </div>
    </div>

    <div class="mskoda-panel mskoda-plug">
      <div class="mskoda-plug-state {$plugClass}">
        <div class="mskoda-plug-icon">{$plugStateIcon}</div>
        <div>
          <div class="mskoda-plug-title">{$plugStateLabel}</div>
          <div class="mskoda-plug-sub">{$chargingBadge}</div>
        </div>
      </div>
      <div class="mskoda-grid">
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Charging power')}</div>
          <div class="mskoda-item-value">{$powerText}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Time to full')}</div>
          <div class="mskoda-item-value">{$timeToFullText}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Charging limit')}</div>
          <div class="mskoda-item-value">{$targetSocText}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Charging mode')}</div>
          <div class="mskoda-item-value">{$chargeMode}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="mskoda-sections">
    <div class="mskoda-panel">
      <div class="mskoda-section-title">{$this->Translate('Charging control')}</div>
      <div class="mskoda-grid">
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Charging')}</div>
          <div class="mskoda-item-value">{$chargingBadge}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Charge type')}</div>
          <div class="mskoda-item-value">{$this->humanizeMode($chargeType !== '' ? $chargeType : 'UNKNOWN')}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Charging state')}</div>
          <div class="mskoda-item-value">{$this->humanizeMode($chargeState !== '' ? $chargeState : 'UNKNOWN')}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Last update')}</div>
          <div class="mskoda-item-value">{$ageText}</div>
        </div>
      </div>
    </div>

    <div class="mskoda-panel">
      <div class="mskoda-section-title">{$this->Translate('Climate')}</div>
      <div class="mskoda-grid">
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Air conditioning')}</div>
          <div class="mskoda-item-value">{$climateBadge}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Climate state')}</div>
          <div class="mskoda-item-value">{$climateModeText}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Target temperature')}</div>
          <div class="mskoda-item-value">{$tempText}</div>
        </div>
        <div class="mskoda-item">
          <div class="mskoda-item-label">{$this->Translate('Status')}</div>
          <div class="mskoda-item-value">{$climateText}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="mskoda-foot">{$plugStateLabel} • {$climateText} • {$rangeText}</div>
</div>
HTML;
    }

    private function buildVehicleSvg(bool $locked, bool $doorsOpen, bool $windowsOpen, bool $lightsOn, string $socText, string $targetSocText, string $rangeText): string
    {
        $bodyStroke = $locked ? '#22c55e' : '#ef4444';
        $doorFill = $doorsOpen ? '#f59e0b' : 'rgba(127,127,127,0.18)';
        $windowFill = $windowsOpen ? '#38bdf8' : 'rgba(127,127,127,0.14)';
        $lightFill = $lightsOn ? '#facc15' : 'rgba(127,127,127,0.18)';
        $lockText = $locked ? '🔒' : '🔓';

        return <<<SVG
<svg viewBox="0 0 360 220" width="100%" height="220" role="img" aria-label="Vehicle overview">
  <rect x="122" y="18" width="116" height="184" rx="40" fill="rgba(127,127,127,0.08)" stroke="{$bodyStroke}" stroke-width="4" />
  <rect x="137" y="44" width="86" height="46" rx="18" fill="{$windowFill}" stroke="rgba(127,127,127,0.25)" stroke-width="2" />
  <rect x="137" y="130" width="86" height="46" rx="18" fill="{$windowFill}" stroke="rgba(127,127,127,0.25)" stroke-width="2" />
  <rect x="104" y="64" width="16" height="38" rx="6" fill="{$doorFill}" />
  <rect x="240" y="64" width="16" height="38" rx="6" fill="{$doorFill}" />
  <rect x="104" y="118" width="16" height="38" rx="6" fill="{$doorFill}" />
  <rect x="240" y="118" width="16" height="38" rx="6" fill="{$doorFill}" />
  <rect x="150" y="24" width="60" height="8" rx="3" fill="{$lightFill}" />
  <rect x="150" y="188" width="60" height="8" rx="3" fill="{$lightFill}" />
  <ellipse cx="112" cy="72" rx="10" ry="22" fill="rgba(127,127,127,0.24)" />
  <ellipse cx="248" cy="72" rx="10" ry="22" fill="rgba(127,127,127,0.24)" />
  <ellipse cx="112" cy="148" rx="10" ry="22" fill="rgba(127,127,127,0.24)" />
  <ellipse cx="248" cy="148" rx="10" ry="22" fill="rgba(127,127,127,0.24)" />
  <text x="180" y="101" text-anchor="middle" style="font:700 24px Arial,Helvetica,sans-serif;fill:currentColor">{$socText}</text>
  <text x="180" y="122" text-anchor="middle" style="font:600 12px Arial,Helvetica,sans-serif;fill:currentColor;opacity:.78">{$this->Translate('Charging limit')}: {$targetSocText}</text>
  <text x="180" y="144" text-anchor="middle" style="font:600 12px Arial,Helvetica,sans-serif;fill:currentColor;opacity:.78">{$this->Translate('Range')}: {$rangeText}</text>
  <text x="180" y="164" text-anchor="middle" style="font:600 18px Arial,Helvetica,sans-serif;fill:currentColor">{$lockText}</text>
</svg>
SVG;
    }

    private function describePlugState(string $chargeState, string $chargeType, ?bool $charging, ?float $chargePowerKw, ?int $remainingMinutes): array
    {
        $state = strtoupper($chargeState);
        $type = strtoupper($chargeType);
        if (in_array($state, ['ERROR', 'FAULT', 'UNAVAILABLE'], true)) {
            return ['⛔', $this->Translate('Charging error'), 'mskoda-status-error'];
        }
        if ($charging || ($chargePowerKw !== null && $chargePowerKw > 0.05) || in_array($state, ['CHARGING', 'CONSERVING'], true)) {
            return ['⚡', $this->Translate('Charging in progress'), 'mskoda-status-ok'];
        }
        if (in_array($state, ['COMPLETED', 'FINISHED', 'READY_FOR_CHARGING', 'TARGET_REACHED'], true) || ($remainingMinutes === 0 && $type !== '')) {
            return ['✅', $this->Translate('Ready / target reached'), 'mskoda-status-done'];
        }
        if ($type !== '' || in_array($state, ['CONNECTED', 'AVAILABLE', 'WAITING_FOR_CHARGING', 'READY'], true)) {
            return ['🔌', $this->Translate('Connected'), 'mskoda-status-warn'];
        }
        return ['🔌', $this->Translate('Not connected'), 'mskoda-status-idle'];
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
                $index = (string) ((int) $this->GetValue('ChargeMode'));
                $mode = (string) ($map[$index] ?? '');
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
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return sprintf('%02d:%02d', $hours, $minutes);
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
