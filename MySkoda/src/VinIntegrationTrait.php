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

        if ($vinChanged && $this->ReadPropertyBoolean('CreateVINVariables')) {
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
        }

        return json_encode($form, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function refreshVINConfigurationForm(): void
    {
        try {
            foreach ($this->vinConfigurationCaptions() as $name => $caption) {
                $this->UpdateFormField($name, 'caption', $caption);
            }
        } catch (Throwable) {
        }
    }
}
