<?php

declare(strict_types=1);

trait MySkodaVinDecoderTrait
{
    public function GetVINData(): string
    {
        $data = $this->readVINDecodeData();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '{}';
    }

    private function updateVINDecodeCache(string $vin): bool
    {
        $vin = strtoupper(trim($vin));
        $fingerprint = hash('sha256', $vin);
        $previous = $this->ReadAttributeString('VINDecodeFingerprint');
        $cached = trim($this->ReadAttributeString('VINDecodeData'));

        if ($previous === $fingerprint && $cached !== '' && $cached !== '{}') {
            return false;
        }

        $data = $this->decodeVIN($vin);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->WriteAttributeString('VINDecodeData', is_string($json) ? $json : '{}');
        $this->WriteAttributeString('VINDecodeFingerprint', $fingerprint);

        return true;
    }

    private function ensureVINVariables(): void
    {
        if (!$this->ReadPropertyBoolean('CreateVINVariables')) {
            return;
        }

        $data = $this->readVINDecodeData();
        foreach ($this->vinVariableDefinitions($data) as $definition) {
            $this->registerVariableOnce($definition);
        }
    }

    private function updateVINVariablesFromCache(): void
    {
        $data = $this->readVINDecodeData();
        foreach ($this->vinVariableValueMap($data) as $ident => $value) {
            $id = @$this->GetIDForIdent($ident);
            if ($id === false || !IPS_VariableExists($id)) {
                continue;
            }
            $this->SetValue($ident, (string) $value);
        }
    }

    private function vinVariableDefinitions(array $data): array
    {
        $values = $this->vinVariableValueMap($data);
        $definitions = [
            ['VINWMI', 'VIN WMI', 1100],
            ['VINVDS', 'VIN VDS', 1110],
            ['VINVIS', 'VIN VIS', 1120],
            ['VINManufacturer', 'VIN manufacturer', 1130],
            ['VINCountry', 'VIN country', 1140],
            ['VINModel', 'VIN model', 1150],
            ['VINModelCode', 'VIN model code', 1160],
            ['VINBody', 'VIN body', 1170],
            ['VINSteering', 'VIN steering', 1180],
            ['VINDrive', 'VIN drive', 1190],
            ['VINPower', 'VIN power', 1200],
            ['VINVariant', 'VIN variant', 1210],
            ['VINRestraint', 'VIN restraint system', 1220],
            ['VINModelYear', 'VIN model year', 1230],
            ['VINPlant', 'VIN production plant', 1240],
            ['VINSerialNumber', 'VIN serial number', 1250],
            ['VINCheckDigit', 'VIN check digit', 1260]
        ];

        $result = [];
        foreach ($definitions as [$ident, $name, $position]) {
            $result[] = $this->variable(
                $ident,
                $name,
                VARIABLETYPE_STRING,
                $position,
                [],
                (string) ($values[$ident] ?? '')
            );
        }

        return $result;
    }

    private function vinVariableValueMap(array $data): array
    {
        if (!(bool) ($data['validSyntax'] ?? false)) {
            return [
                'VINWMI' => (string) ($data['wmi'] ?? ''),
                'VINVDS' => (string) ($data['vds'] ?? ''),
                'VINVIS' => (string) ($data['vis'] ?? ''),
                'VINManufacturer' => '',
                'VINCountry' => '',
                'VINModel' => '',
                'VINModelCode' => '',
                'VINBody' => '',
                'VINSteering' => '',
                'VINDrive' => '',
                'VINPower' => '',
                'VINVariant' => '',
                'VINRestraint' => '',
                'VINModelYear' => '',
                'VINPlant' => '',
                'VINSerialNumber' => '',
                'VINCheckDigit' => ''
            ];
        }

        $check = (bool) ($data['checkDigitValid'] ?? false)
            ? $this->Translate('valid')
            : $this->Translate('not confirmed');

        return [
            'VINWMI' => (string) ($data['wmi'] ?? ''),
            'VINVDS' => (string) ($data['vds'] ?? ''),
            'VINVIS' => (string) ($data['vis'] ?? ''),
            'VINManufacturer' => (string) ($data['manufacturer'] ?? ''),
            'VINCountry' => (string) ($data['country'] ?? ''),
            'VINModel' => (string) ($data['model'] ?? ''),
            'VINModelCode' => (string) ($data['modelCode'] ?? ''),
            'VINBody' => (string) ($data['body'] ?? ''),
            'VINSteering' => (string) ($data['steering'] ?? ''),
            'VINDrive' => (string) ($data['drive'] ?? ''),
            'VINPower' => (string) ($data['power'] ?? ''),
            'VINVariant' => (string) ($data['variant'] ?? ''),
            'VINRestraint' => (string) ($data['restraint'] ?? ''),
            'VINModelYear' => isset($data['modelYear']) && $data['modelYear'] !== null ? (string) $data['modelYear'] : '',
            'VINPlant' => (string) ($data['plant'] ?? ''),
            'VINSerialNumber' => (string) ($data['serialNumber'] ?? ''),
            'VINCheckDigit' => trim((string) ($data['checkDigit'] ?? '') . ' (' . $check . ')')
        ];
    }

    private function prepareVINConfigurationElements(array &$elements): void
    {
        $captions = $this->vinConfigurationCaptions();
        foreach ($elements as &$element) {
            if (!is_array($element)) {
                continue;
            }

            $name = (string) ($element['name'] ?? '');
            if ($name !== '' && isset($captions[$name])) {
                $element['caption'] = $captions[$name];
            }

            if (isset($element['items']) && is_array($element['items'])) {
                $this->prepareVINConfigurationElements($element['items']);
            }
        }
        unset($element);
    }

    private function vinConfigurationCaptions(): array
    {
        $data = $this->readVINDecodeData();
        if (!(bool) ($data['validSyntax'] ?? false)) {
            return [
                'VINDecodeStructure' => $this->Translate('Enter and apply a valid VIN to decode it.'),
                'VINDecodeManufacturer' => '',
                'VINDecodeModel' => '',
                'VINDecodeDrive' => '',
                'VINDecodeProduction' => '',
                'VINDecodeRestraint' => '',
                'VINDecodeValidation' => ''
            ];
        }

        $parts = (array) ($data['parts'] ?? []);
        $structure = implode(' | ', array_map('strval', $parts));
        $manufacturer = trim((string) ($data['manufacturer'] ?? ''));
        $country = trim((string) ($data['country'] ?? ''));
        $model = trim((string) ($data['model'] ?? ''));
        $modelCode = trim((string) ($data['modelCode'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $steering = trim((string) ($data['steering'] ?? ''));
        $drive = trim((string) ($data['drive'] ?? ''));
        $power = trim((string) ($data['power'] ?? ''));
        $variant = trim((string) ($data['variant'] ?? ''));
        $year = isset($data['modelYear']) && $data['modelYear'] !== null ? (string) $data['modelYear'] : $this->Translate('Unknown');
        $plant = trim((string) ($data['plant'] ?? ''));
        $serial = trim((string) ($data['serialNumber'] ?? ''));
        $restraint = trim((string) ($data['restraint'] ?? ''));
        $check = (bool) ($data['checkDigitValid'] ?? false) ? $this->Translate('valid') : $this->Translate('not confirmed');

        return [
            'VINDecodeStructure' => $this->Translate('VIN structure') . ': ' . $structure,
            'VINDecodeManufacturer' => $this->Translate('Manufacturer') . ': ' . ($manufacturer !== '' ? $manufacturer : $this->Translate('Unknown'))
                . ' | ' . $this->Translate('Country') . ': ' . ($country !== '' ? $country : $this->Translate('Unknown')),
            'VINDecodeModel' => $this->Translate('Model') . ': ' . ($model !== '' ? $model : $this->Translate('Unknown'))
                . ' | ' . $this->Translate('Model code') . ': ' . ($modelCode !== '' ? $modelCode : $this->Translate('Unknown'))
                . ($body !== '' ? ' | ' . $this->Translate('Body') . ': ' . $body : ''),
            'VINDecodeDrive' => $this->Translate('Drive') . ': ' . ($drive !== '' ? $drive : $this->Translate('Unknown'))
                . ($steering !== '' ? ' | ' . $this->Translate('Steering') . ': ' . $steering : '')
                . ($power !== '' ? ' | ' . $this->Translate('Power') . ': ' . $power : '')
                . ($variant !== '' ? ' | ' . $this->Translate('Variant') . ': ' . $variant : ''),
            'VINDecodeProduction' => $this->Translate('Model year') . ': ' . $year
                . ' | ' . $this->Translate('Production plant') . ': ' . ($plant !== '' ? $plant : $this->Translate('Unknown'))
                . ' | ' . $this->Translate('Serial number') . ': ' . $serial,
            'VINDecodeRestraint' => $restraint !== ''
                ? $this->Translate('Restraint system') . ': ' . $restraint
                : $this->Translate('Restraint system') . ': ' . $this->Translate('Unknown') . ' (' . (string) ($data['restraintCode'] ?? '') . ')',
            'VINDecodeValidation' => $this->Translate('Check digit') . ': ' . (string) ($data['checkDigit'] ?? '')
                . ' (' . $check . ', ' . $this->Translate('calculated') . ': ' . (string) ($data['calculatedCheckDigit'] ?? '') . ')'
        ];
    }

    private function readVINDecodeData(): array
    {
        $data = json_decode($this->ReadAttributeString('VINDecodeData'), true);
        return is_array($data) ? $data : [];
    }

    private function decodeVIN(string $vin): array
    {
        $vin = strtoupper(trim($vin));
        $validSyntax = preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin) === 1;
        $data = [
            'vin' => $vin,
            'validSyntax' => $validSyntax,
            'wmi' => strlen($vin) >= 3 ? substr($vin, 0, 3) : '',
            'vds' => strlen($vin) >= 9 ? substr($vin, 3, 6) : '',
            'vis' => strlen($vin) >= 17 ? substr($vin, 9, 8) : ''
        ];

        if (!$validSyntax) {
            return $data;
        }

        $wmi = substr($vin, 0, 3);
        $bodyCode = $vin[3];
        $engineCode = $vin[4];
        $restraintCode = $vin[5];
        $modelCode = substr($vin, 6, 2);
        $checkDigit = $vin[8];
        $modelYearCode = $vin[9];
        $plantCode = $vin[10];
        $serialNumber = substr($vin, 11, 6);
        $modelYear = $this->decodeVINModelYear($modelYearCode, $modelCode);
        $model = $this->decodeVINModel($modelCode, $bodyCode, $modelYear);

        $manufacturer = '';
        $country = '';
        if ($wmi === 'TMB') {
            $manufacturer = 'Škoda Auto';
            $country = $this->Translate('Czech Republic');
        }

        $body = '';
        $steering = '';
        $drive = '';
        $powerKw = null;
        $variant = '';
        $restraint = '';

        if ($model === 'Enyaq') {
            [$body, $steering, $drive] = $this->decodeEnyaqBody($bodyCode);
            $powerKw = $this->decodeElectricPower($engineCode);
            $variant = $this->decodeEnyaqVariant($engineCode, $drive, $modelYear);
            $restraint = $this->decodeEnyaqRestraint($restraintCode);
        } elseif ($model === 'Elroq') {
            $body = 'SUV';
            $powerKw = $this->decodeElectricPower($engineCode);
            $variant = $this->decodeElroqVariant($engineCode);
            $restraint = $this->decodeEnyaqRestraint($restraintCode);
        } elseif ($model === 'Octavia IV') {
            if ($bodyCode === 'J') {
                $body = $this->Translate('Estate');
                $steering = $this->Translate('Left-hand drive');
                $drive = $this->Translate('Single-axle drive');
            }
            if ($engineCode === 'R') {
                $powerKw = 110;
                $variant = '1.5 TSI';
            }
            if ($restraintCode === '8') {
                $restraint = $this->Translate('2 front airbags, 4 side airbags and 2 head airbags');
            }
        }

        $calculatedCheckDigit = $this->calculateVINCheckDigit($vin);
        $plant = $this->decodeVINPlant($plantCode, $modelCode);
        $power = '';
        if (is_int($powerKw)) {
            $power = $powerKw . ' kW / ' . (string) round($powerKw * 1.359621617) . ' PS';
        }

        return array_merge($data, [
            'manufacturer' => $manufacturer,
            'country' => $country,
            'bodyCode' => $bodyCode,
            'engineCode' => $engineCode,
            'restraintCode' => $restraintCode,
            'modelCode' => $modelCode,
            'checkDigit' => $checkDigit,
            'modelYearCode' => $modelYearCode,
            'plantCode' => $plantCode,
            'serialNumber' => $serialNumber,
            'model' => $model,
            'body' => $body,
            'steering' => $steering,
            'drive' => $drive,
            'power' => $power,
            'variant' => $variant,
            'restraint' => $restraint,
            'modelYear' => $modelYear,
            'plant' => $plant,
            'calculatedCheckDigit' => $calculatedCheckDigit,
            'checkDigitValid' => $calculatedCheckDigit !== '' && $calculatedCheckDigit === $checkDigit,
            'parts' => [$wmi, $bodyCode, $engineCode, $restraintCode, $modelCode, $checkDigit, $modelYearCode, $plantCode, $serialNumber]
        ]);
    }

    private function decodeVINModel(string $modelCode, string $bodyCode, ?int $modelYear): string
    {
        if ($modelCode === 'NY') {
            if (in_array($bodyCode, ['E', 'F', 'G', 'H', 'J', 'K', 'L', 'M'], true)) {
                return 'Enyaq';
            }
            if (($modelYear ?? 0) >= 2025 && in_array($bodyCode, ['N', 'P', 'R'], true)) {
                return 'Elroq';
            }
            return 'Enyaq / Elroq';
        }

        $models = [
            '6Y' => 'Fabia I',
            '5J' => 'Fabia II / Roomster',
            'NJ' => 'Fabia III',
            'PJ' => 'Fabia IV',
            '1U' => 'Octavia I',
            '1Z' => 'Octavia II',
            '5E' => 'Octavia III',
            'NX' => 'Octavia IV',
            '3U' => 'Superb I',
            '3T' => 'Superb II',
            '3V' => 'Superb III',
            'NZ' => 'Superb IV',
            '5L' => 'Yeti',
            'NU' => 'Karoq',
            'NS' => 'Kodiaq I',
            'PS' => 'Kodiaq II',
            'NW' => 'Scala / Kamiq',
            'NF' => 'Citigo',
            'NH' => 'Rapid',
            'NK' => 'Rapid'
        ];

        return $models[$modelCode] ?? '';
    }

    private function decodeVINModelYear(string $code, string $modelCode): ?int
    {
        $sequence = 'ABCDEFGHJKLMNPRSTVWXY123456789';
        $index = strpos($sequence, $code);
        if ($index === false) {
            return null;
        }

        $ranges = [
            '6Y' => [1999, 2008], '5J' => [2006, 2015], 'NJ' => [2014, 2022], 'PJ' => [2021, 2039],
            '1U' => [1996, 2011], '1Z' => [2004, 2013], '5E' => [2012, 2021], 'NX' => [2019, 2039],
            '3U' => [2001, 2008], '3T' => [2008, 2015], '3V' => [2015, 2024], 'NZ' => [2023, 2039],
            '5L' => [2009, 2018], 'NU' => [2017, 2039], 'NS' => [2016, 2024], 'PS' => [2023, 2039],
            'NW' => [2019, 2039], 'NF' => [2011, 2021], 'NH' => [2012, 2021], 'NK' => [2012, 2021],
            'NY' => [2020, 2039]
        ];

        $candidates = [1980 + $index, 2010 + $index, 2040 + $index];
        if (isset($ranges[$modelCode])) {
            [$from, $to] = $ranges[$modelCode];
            foreach ($candidates as $candidate) {
                if ($candidate >= $from && $candidate <= $to) {
                    return $candidate;
                }
            }
        }

        $latest = null;
        $maxYear = (int) date('Y') + 1;
        foreach ($candidates as $candidate) {
            if ($candidate >= 1984 && $candidate <= $maxYear) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    private function decodeVINPlant(string $code, string $modelCode): string
    {
        if (in_array($code, ['0', '1', '2', '3', '4'], true)) {
            return 'Mladá Boleslav';
        }
        if (in_array($code, ['5', '6', '7', '8', '9'], true)) {
            return 'Kvasiny';
        }
        if ($code === 'Y') {
            return 'Mladá Boleslav';
        }
        if ($code === 'F' && $modelCode === 'NY') {
            return 'Mladá Boleslav';
        }

        return '';
    }

    private function decodeEnyaqBody(string $code): array
    {
        $map = [
            'E' => [$this->Translate('Coupe'), $this->Translate('Left-hand drive'), $this->Translate('Rear-wheel drive')],
            'F' => [$this->Translate('Coupe'), $this->Translate('Right-hand drive'), $this->Translate('Rear-wheel drive')],
            'G' => [$this->Translate('Coupe'), $this->Translate('Left-hand drive'), $this->Translate('All-wheel drive')],
            'H' => [$this->Translate('Coupe'), $this->Translate('Right-hand drive'), $this->Translate('All-wheel drive')],
            'J' => ['SUV', $this->Translate('Left-hand drive'), $this->Translate('Rear-wheel drive')],
            'K' => ['SUV', $this->Translate('Right-hand drive'), $this->Translate('Rear-wheel drive')],
            'L' => ['SUV', $this->Translate('Left-hand drive'), $this->Translate('All-wheel drive')],
            'M' => ['SUV', $this->Translate('Right-hand drive'), $this->Translate('All-wheel drive')]
        ];

        return $map[$code] ?? ['', '', ''];
    }

    private function decodeElectricPower(string $code): ?int
    {
        $map = [
            'A' => 109,
            'B' => 132,
            'C' => 150,
            'D' => 165,
            'E' => 195,
            'F' => 220,
            'H' => 210,
            'J' => 250
        ];
        return $map[$code] ?? null;
    }

    private function decodeEnyaqVariant(string $engineCode, string $drive, ?int $modelYear): string
    {
        if (($modelYear ?? 0) <= 2023) {
            $legacy = [
                'A' => 'Enyaq iV 50',
                'B' => 'Enyaq iV 60',
                'C' => 'Enyaq iV 80',
                'E' => 'Enyaq iV 80x',
                'F' => 'Enyaq iV RS'
            ];
            if (isset($legacy[$engineCode])) {
                return $legacy[$engineCode];
            }
        }

        if ($engineCode === 'H') {
            return $drive === $this->Translate('All-wheel drive') ? 'Enyaq 85x' : 'Enyaq 85';
        }
        if ($engineCode === 'J') {
            return 'Enyaq RS';
        }

        return '';
    }

    private function decodeElroqVariant(string $engineCode): string
    {
        $map = [
            'C' => 'Elroq 60',
            'H' => 'Elroq 85',
            'J' => 'Elroq RS'
        ];
        return $map[$engineCode] ?? '';
    }

    private function decodeEnyaqRestraint(string $code): string
    {
        if ($code === '7') {
            return $this->Translate('2 front airbags, 2 side airbags, 2 head airbags and 1 center airbag');
        }
        if ($code === '9') {
            return $this->Translate('2 front airbags, 4 side airbags, 2 head airbags and 1 center airbag');
        }
        return '';
    }

    private function calculateVINCheckDigit(string $vin): string
    {
        if (strlen($vin) !== 17) {
            return '';
        }

        $values = [
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8,
            'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9,
            'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8, 'Z' => 9
        ];
        $weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 17; $i++) {
            $char = $vin[$i];
            if (ctype_digit($char)) {
                $value = (int) $char;
            } elseif (isset($values[$char])) {
                $value = $values[$char];
            } else {
                return '';
            }
            $sum += $value * $weights[$i];
        }

        $remainder = $sum % 11;
        return $remainder === 10 ? 'X' : (string) $remainder;
    }
}
