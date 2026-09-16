<?php

declare(strict_types=1);

trait MySkodaVinIntegrationTrait
{
    public function Create(): void
    {
        $this->climateCreate();

        $this->RegisterPropertyBoolean('CreateVINVariables', false);
        $this->RegisterAttributeString('VINDecodeFingerprint', '');
        $this->RegisterAttributeString('VINDecodeData', '{}');
    }

    public function ApplyChanges(): void
    {
        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $vinChanged = $this->updateVINDecodeCache($vin);

        $this->climateApplyChanges();
        $this->ensureVINVariables();

        // The option controls creation only. Once VIN variables exist, they stay
        // maintained when the configured VIN changes, just like other module data.
        if ($vinChanged) {
            $this->updateVINVariablesFromCache();
        }

        $this->refreshVINConfigurationForm();
    }

    public function GetConfigurationForm(): string
    {
        $form = json_decode($this->coreGetConfigurationForm(), true);
        if (!is_array($form)) {
            return '{}';
        }

        if (isset($form['elements']) && is_array($form['elements'])) {
            $this->prepareVINConfigurationElements($form['elements']);
            $this->prepareVINCodeElements($form['elements']);
        }

        return json_encode($form, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function refreshVINConfigurationForm(): void
    {
        try {
            $captions = array_merge($this->vinConfigurationCaptions(), $this->vinConfigurationCodeCaptions());
            foreach ($captions as $name => $caption) {
                $this->UpdateFormField($name, 'caption', $caption);
            }
        } catch (Throwable) {
        }
    }

    private function prepareVINCodeElements(array &$elements): void
    {
        $captions = $this->vinConfigurationCodeCaptions();
        foreach ($elements as &$element) {
            if (!is_array($element)) {
                continue;
            }

            $name = (string) ($element['name'] ?? '');
            if ($name !== '' && isset($captions[$name])) {
                $element['caption'] = $captions[$name];
            }

            if (isset($element['items']) && is_array($element['items'])) {
                $this->prepareVINCodeElements($element['items']);
            }
        }
        unset($element);
    }

    private function vinConfigurationCodeCaptions(): array
    {
        $empty = [
            'VINCodeManufacturer' => '',
            'VINCodeModel' => '',
            'VINCodeDrive' => '',
            'VINCodeProduction' => '',
            'VINCodeRestraint' => '',
            'VINCodeValidation' => ''
        ];

        $data = $this->readVINDecodeData();
        if (!(bool) ($data['validSyntax'] ?? false)) {
            return $empty;
        }

        return [
            'VINCodeManufacturer' => (string) ($data['wmi'] ?? ''),
            'VINCodeModel' => $this->joinVINCodeParts([
                (string) ($data['bodyCode'] ?? ''),
                (string) ($data['modelCode'] ?? '')
            ]),
            'VINCodeDrive' => $this->joinVINCodeParts([
                (string) ($data['bodyCode'] ?? ''),
                (string) ($data['engineCode'] ?? '')
            ]),
            'VINCodeProduction' => $this->joinVINCodeParts([
                (string) ($data['modelYearCode'] ?? ''),
                (string) ($data['plantCode'] ?? ''),
                (string) ($data['serialNumber'] ?? '')
            ]),
            'VINCodeRestraint' => (string) ($data['restraintCode'] ?? ''),
            'VINCodeValidation' => (string) ($data['checkDigit'] ?? '')
        ];
    }

    private function joinVINCodeParts(array $parts): string
    {
        $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));
        return implode(' | ', $parts);
    }
}
