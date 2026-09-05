<?php

declare(strict_types=1);

/**
 * Compact smartphone-focused vehicle tile for MySkoda 1.5.
 *
 * Visual design inspiration: da8ter/TileVisu-Kachelsammlung
 * https://github.com/da8ter/TileVisu-Kachelsammlung
 *
 * This implementation and its SVG are original. No source code or graphical
 * assets from the referenced project are copied into this module.
 */
trait MySkodaVisualizationV15Trait
{
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

        $soc = $this->firstValue([
            $this->safeValue('StateOfCharge'),
            $this->path($vehicle, 'charging.status.battery.stateOfChargeInPercent', null)
        ]);
        $targetSoc = $this->firstValue([
            $this->safeValue('TargetSOC'),
            $this->path($vehicle, 'charging.settings.targetStateOfChargeInPercent', null)
        ]);
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
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $rangeText = $this->formatIntegerWithUnit($range, 'km');
        $mileageText = $this->formatIntegerWithUnit($mileage, 'km');
        $socText = is_numeric($soc) ? ((int) round((float) $soc)) . '%' : '--';
        $targetSocText = is_numeric($targetSoc) ? ((int) round((float) $targetSoc)) . '%' : '--';
        $powerText = $this->formatChargingPower($chargePowerKw, $cableConnected);
        $timeText = $remainingMinutes !== null ? $this->formatMinutesAsHoursMinutes($remainingMinutes) : '—';
        $ageText = $this->formatAgeFromTimestamp($lastUpdate);
        $tempText = is_numeric($targetTemperature) ? number_format((float) $targetTemperature, 1, ',', '') . ' °C' : '—';
        $chargeTypeText = ($chargeType !== '' && $chargeType !== 'OFF') ? $this->humanizeMode($chargeType) : '';
        $climateMode = $this->climateModeCaption($climateState, $climate);

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
        $modeLabel = $this->Translate('Charging mode');
        $climateLabel = $this->Translate('Climate');
        $tempLabel = $this->Translate('Target temperature');

        $lockStatus = $this->statusPhrase($locked ? $this->Translate('Locked') : $this->Translate('Unlocked'), $locked);
        $doorStatus = $this->statusPhrase($doorsOpen ? $this->Translate('Doors open') : $this->Translate('Doors closed'), !$doorsOpen);
        $windowStatus = $this->statusPhrase($windowsOpen ? $this->Translate('Windows open') : $this->Translate('Windows closed'), !$windowsOpen);
        $lightStatus = $this->statusPhrase($lightsOn ? $this->Translate('Lights on') : $this->Translate('Lights off'), !$lightsOn);

        $climateClass = $climate ? 'ms-climate-on' : 'ms-climate-off';
        $chargeTypeBadge = $chargeTypeText !== ''
            ? '<span class="ms-type">' . htmlspecialchars($chargeTypeText, ENT_QUOTES, 'UTF-8') . '</span>'
            : '';

        return <<<HTML
<div class="ms-card">
<style>
.ms-card{--ok:#22a35a;--warn:#d58a00;--error:#dc4040;--line:rgba(127,127,127,.22);font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif;color:inherit;color-scheme:light dark;background:transparent;padding:7px 9px;box-sizing:border-box;line-height:1.15;max-width:500px;margin:0 auto}
.ms-card *{box-sizing:border-box}.ms-head{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:start;padding-bottom:5px;border-bottom:1px solid var(--line)}
.ms-title{font-size:17px;font-weight:760;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ms-meta{margin-top:3px;font-size:10px;opacity:.72}.ms-meta b{font-size:12px;opacity:1}.ms-age{text-align:right;font-size:10px;opacity:.66;white-space:nowrap}.ms-lockhead{font-size:16px;text-align:right;margin-bottom:2px}
.ms-keywarn{margin-top:5px;padding:5px 7px;border-left:3px solid var(--warn);background:rgba(213,138,0,.09);font-size:10px;font-weight:650}
.ms-main{display:grid;grid-template-columns:minmax(135px,43%) 1fr;gap:9px;align-items:center;padding:6px 0 5px}.ms-car{min-width:0;display:flex;justify-content:center}.ms-charge{min-width:0;padding-left:9px;border-left:1px dotted rgba(40,205,171,.75)}
.ms-chargehead{display:flex;align-items:center;gap:7px;margin-bottom:7px}.ms-plug{font-size:23px;line-height:1;width:26px;text-align:center}.ms-chargecopy{min-width:0;flex:1}.ms-chargetitle{font-size:13px;font-weight:780;line-height:1.15}.ms-chargesub{display:flex;gap:5px;align-items:center;margin-top:2px;font-size:9px;opacity:.65}.ms-type{padding:1px 5px;border:1px solid var(--line);border-radius:8px;opacity:.9}
.ms-ok .ms-chargetitle{color:var(--ok)}.ms-warn .ms-chargetitle{color:var(--warn)}.ms-error .ms-chargetitle{color:var(--error)}.ms-done .ms-chargetitle{color:var(--ok)}.ms-idle .ms-chargetitle{opacity:.72}
.ms-metrics{display:grid;grid-template-columns:1fr 1fr;gap:6px 10px}.ms-label{font-size:8px;opacity:.58;text-transform:uppercase;letter-spacing:.025em}.ms-value{font-size:13px;font-weight:730;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ms-statusbar{display:grid;grid-template-columns:repeat(4,1fr);gap:3px;padding:5px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}.ms-statusitem{text-align:center;min-width:0;font-size:8px;font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ms-dot{display:inline-block;width:6px;height:6px;border-radius:50%;margin-right:3px;vertical-align:1px}.ms-good .ms-dot{background:var(--ok)}.ms-attn .ms-dot{background:var(--warn)}
.ms-bottom{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;padding-top:6px}.ms-climate{min-width:0;display:flex;align-items:center;gap:7px}.ms-climateicon{width:25px;height:25px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(127,127,127,.10);font-size:13px}.ms-climate-on .ms-climateicon{color:var(--ok);background:rgba(34,163,90,.12)}.ms-climatecopy{min-width:0}.ms-climateline{font-size:11px;font-weight:760;white-space:nowrap}.ms-climatesub{font-size:9px;opacity:.63;margin-top:1px}.ms-km{text-align:right;font-size:9px;opacity:.65;white-space:nowrap}.ms-km b{display:block;font-size:11px;opacity:1;margin-top:1px}
@media(max-width:380px){.ms-card{padding:6px 7px}.ms-main{grid-template-columns:125px 1fr;gap:6px}.ms-charge{padding-left:6px}.ms-metrics{gap:5px 6px}.ms-value{font-size:12px}.ms-statusbar{gap:1px}.ms-statusitem{font-size:7px}}
</style>
<div class="ms-head">
  <div style="min-width:0"><div class="ms-title">{$titleEsc}</div><div class="ms-meta">{$rangeLabel} <b>{$rangeText}</b></div></div>
  <div><div class="ms-lockhead">{$this->lockSymbol($locked)}</div><div class="ms-age">{$updateLabel} {$ageText}</div></div>
</div>
{$keyWarning}
<div class="ms-main">
  <div class="ms-car">{$svg}</div>
  <div class="ms-charge {$plugClass}">
    <div class="ms-chargehead"><div class="ms-plug">{$plugIcon}</div><div class="ms-chargecopy"><div class="ms-chargetitle">{$plugLabel}</div><div class="ms-chargesub">{$chargeTypeBadge}</div></div></div>
    <div class="ms-metrics">
      <div><div class="ms-label">{$powerLabel}</div><div class="ms-value">{$powerText}</div></div>
      <div><div class="ms-label">{$timeLabel}</div><div class="ms-value">{$timeText}</div></div>
      <div><div class="ms-label">{$limitLabel}</div><div class="ms-value">{$targetSocText}</div></div>
      <div><div class="ms-label">{$modeLabel}</div><div class="ms-value">{$chargeMode}</div></div>
    </div>
  </div>
</div>
<div class="ms-statusbar">{$lockStatus}{$doorStatus}{$windowStatus}{$lightStatus}</div>
<div class="ms-bottom">
  <div class="ms-climate {$climateClass}"><div class="ms-climateicon">{$this->climateSymbol($climateState)}</div><div class="ms-climatecopy"><div class="ms-climateline">{$climateLabel}: {$climateMode}</div><div class="ms-climatesub">{$tempLabel} {$tempText}</div></div></div>
  <div class="ms-km">{$mileageLabel}<b>{$mileageText}</b></div>
</div>
</div>
HTML;
    }

    private function buildVehicleSvg(bool $locked, bool $doorsOpen, bool $windowsOpen, bool $lightsOn, string $socText, string $targetSocText): string
    {
        $outline = $locked ? '#22a35a' : '#d58a00';
        $attention = '#d58a00';
        $windowStroke = $windowsOpen ? $attention : 'rgba(127,127,127,.30)';
        $doorStroke = $doorsOpen ? $attention : 'rgba(127,127,127,.22)';
        $lamp = $lightsOn ? '#f2b400' : 'rgba(127,127,127,.18)';
        $limitLabel = htmlspecialchars($this->Translate('Charging limit'), ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg viewBox="0 0 190 255" width="100%" height="174" role="img" aria-label="Vehicle">
  <defs>
    <linearGradient id="msBody" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity=".16"/><stop offset=".45" stop-color="currentColor" stop-opacity=".055"/><stop offset="1" stop-color="currentColor" stop-opacity=".13"/></linearGradient>
    <linearGradient id="msGlass" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8aa7b7" stop-opacity=".44"/><stop offset="1" stop-color="#3b5666" stop-opacity=".20"/></linearGradient>
  </defs>
  <ellipse cx="95" cy="132" rx="63" ry="108" fill="rgba(0,0,0,.08)"/>
  <rect x="24" y="58" width="13" height="46" rx="6" fill="rgba(45,45,45,.62)"/><rect x="153" y="58" width="13" height="46" rx="6" fill="rgba(45,45,45,.62)"/>
  <rect x="24" y="153" width="13" height="46" rx="6" fill="rgba(45,45,45,.62)"/><rect x="153" y="153" width="13" height="46" rx="6" fill="rgba(45,45,45,.62)"/>
  <path d="M65 13 C82 7 108 7 125 13 C142 23 151 43 153 68 L158 183 C157 209 144 231 124 242 C107 249 83 249 66 242 C46 231 33 209 32 183 L37 68 C39 43 48 23 65 13Z" fill="url(#msBody)" stroke="{$outline}" stroke-width="3"/>
  <path d="M62 34 C79 27 111 27 128 34 L135 76 C116 69 74 69 55 76Z" fill="url(#msGlass)" stroke="{$windowStroke}" stroke-width="2"/>
  <path d="M54 170 C74 178 116 178 136 170 L128 219 C111 226 79 226 62 219Z" fill="url(#msGlass)" stroke="{$windowStroke}" stroke-width="2"/>
  <path d="M48 84 L39 90 L37 145 L49 153" fill="none" stroke="{$doorStroke}" stroke-width="5" stroke-linecap="round"/><path d="M142 84 L151 90 L153 145 L141 153" fill="none" stroke="{$doorStroke}" stroke-width="5" stroke-linecap="round"/>
  <path d="M58 80 C78 73 112 73 132 80 L137 160 C115 169 75 169 53 160Z" fill="rgba(127,127,127,.045)" stroke="rgba(127,127,127,.18)" stroke-width="1.2"/>
  <path d="M39 64 Q28 58 27 72 Q29 82 38 78" fill="url(#msBody)" stroke="rgba(127,127,127,.30)"/><path d="M151 64 Q162 58 163 72 Q161 82 152 78" fill="url(#msBody)" stroke="rgba(127,127,127,.30)"/>
  <path d="M62 20 Q95 11 128 20" fill="none" stroke="{$lamp}" stroke-width="5" stroke-linecap="round"/><path d="M65 235 Q95 243 125 235" fill="none" stroke="{$lamp}" stroke-width="5" stroke-linecap="round"/>
  <rect x="61" y="100" width="68" height="45" rx="9" fill="rgba(31,157,205,.18)" stroke="rgba(31,157,205,.72)" stroke-width="1.5"/>
  <text x="95" y="122" text-anchor="middle" style="font:780 24px system-ui;fill:currentColor">{$socText}</text><text x="95" y="137" text-anchor="middle" style="font:600 8px system-ui;fill:currentColor;opacity:.68">{$limitLabel} {$targetSocText}</text>
</svg>
SVG;
    }

    private function extractRemainingChargeMinutes(array $vehicle): ?int
    {
        $state = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $type = strtoupper((string) $this->path($vehicle, 'charging.status.chargeType', ''));
        if ($state === 'CONNECT_CABLE' || $type === 'OFF') {
            return null;
        }

        $minutes = $this->firstValue([
            $this->path($vehicle, 'charging.status.remainingTimeToFullyChargedInMinutes', null),
            $this->path($vehicle, 'charging.status.remainingChargingTimeInMinutes', null)
        ]);
        if (is_numeric($minutes)) {
            return max(0, (int) round((float) $minutes));
        }

        $fullAt = $this->toTimestamp($this->path($vehicle, 'charging.status.fullyChargedAt', null));
        if ($fullAt > time()) {
            return max(0, (int) floor(($fullAt - time()) / 60));
        }
        return null;
    }

    private function isChargeCableConnected(string $chargeState, string $chargeType): bool
    {
        $state = strtoupper($chargeState);
        $type = strtoupper($chargeType);
        if ($state === 'CONNECT_CABLE' || $type === 'OFF') {
            return false;
        }
        return $type !== '' || in_array($state, [
            'CHARGING', 'CONSERVING', 'READY_FOR_CHARGING', 'CONNECTED', 'AVAILABLE',
            'WAITING_FOR_CHARGING', 'READY', 'COMPLETED', 'FINISHED', 'TARGET_REACHED',
            'CHARGING_INTERRUPTED', 'DISCHARGING'
        ], true);
    }

    private function formatChargingPower(?float $chargePowerKw, bool $cableConnected): string
    {
        if (!$cableConnected || $chargePowerKw === null) {
            return '—';
        }
        return number_format($chargePowerKw, 1, ',', '') . ' kW';
    }

    private function climateModeCaption(string $state, bool $active): string
    {
        if (!$active || $state === '' || $state === 'OFF') {
            return $this->Translate('Off');
        }
        return $this->humanizeMode($state);
    }

    private function climateSymbol(string $state): string
    {
        return match (strtoupper($state)) {
            'COOLING' => '❄',
            'HEATING', 'HEATING_AUXILIARY' => '♨',
            'VENTILATION' => '≋',
            default => '○'
        };
    }

    private function lockSymbol(bool $locked): string
    {
        return $locked ? '🔒' : '🔓';
    }

    private function statusPhrase(string $text, bool $good): string
    {
        $class = $good ? 'ms-good' : 'ms-attn';
        return '<div class="ms-statusitem ' . $class . '"><span class="ms-dot"></span>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
