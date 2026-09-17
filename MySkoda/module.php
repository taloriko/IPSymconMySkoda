<?php

declare(strict_types=1);

require_once __DIR__ . '/src/CoreTrait.php';
require_once __DIR__ . '/src/VariablesTrait.php';
require_once __DIR__ . '/src/HistoryTrait.php';
require_once __DIR__ . '/src/ApiTrait.php';
require_once __DIR__ . '/src/OpenApiTrait.php';
require_once __DIR__ . '/src/ImageTrait.php';
require_once __DIR__ . '/src/DiagnosticsTrait.php';
require_once __DIR__ . '/src/NotificationTrait.php';
require_once __DIR__ . '/src/HelpersTrait.php';
require_once __DIR__ . '/src/CommandTrait.php';
require_once __DIR__ . '/src/ClimateSelectionTrait.php';
require_once __DIR__ . '/src/VinDecoderTrait.php';
require_once __DIR__ . '/src/VinIntegrationTrait.php';

final class MySkoda extends IPSModuleStrict
{
    use MySkodaCoreTrait,
        MySkodaVariablesTrait,
        MySkodaHistoryTrait,
        MySkodaApiTrait,
        MySkodaOpenApiTrait,
        MySkodaImageTrait,
        MySkodaDiagnosticsTrait,
        MySkodaNotificationTrait,
        MySkodaHelpersTrait,
        MySkodaCommandTrait,
        MySkodaClimateSelectionTrait,
        MySkodaVinDecoderTrait,
        MySkodaVinIntegrationTrait {
        MySkodaCoreTrait::Create as private coreCreate;
        MySkodaCoreTrait::ApplyChanges as private coreApplyChanges;
        MySkodaCoreTrait::GetConfigurationForm as private coreGetConfigurationForm;
        MySkodaVariablesTrait::registerVariables as private baseRegisterVariables;
        MySkodaVariablesTrait::setPathValue as private baseSetPathValue;

        MySkodaCommandTrait::Create as private commandCreate;
        MySkodaCommandTrait::ApplyChanges as private commandApplyChanges;
        MySkodaCommandTrait::RequestAction as private commandRequestAction;

        MySkodaClimateSelectionTrait::Create as private climateCreate;
        MySkodaClimateSelectionTrait::ApplyChanges as private climateApplyChanges;

        MySkodaVinIntegrationTrait::Create insteadof MySkodaCoreTrait, MySkodaCommandTrait, MySkodaClimateSelectionTrait;
        MySkodaVinIntegrationTrait::ApplyChanges insteadof MySkodaCoreTrait, MySkodaCommandTrait, MySkodaClimateSelectionTrait;
        MySkodaVinIntegrationTrait::GetConfigurationForm insteadof MySkodaCoreTrait;

        MySkodaClimateSelectionTrait::RequestAction insteadof MySkodaCoreTrait, MySkodaCommandTrait;
        MySkodaClimateSelectionTrait::setPathValue insteadof MySkodaVariablesTrait;

        MySkodaCommandTrait::registerVariables insteadof MySkodaVariablesTrait;
        MySkodaCommandTrait::SetChargingLimit insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::SetChargeMode insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::UpdateChargingProfile insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::StartAuxiliaryHeating insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::StopAuxiliaryHeating insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::StartVentilation insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::StopVentilation insteadof MySkodaCoreTrait;
    }

    private const API_ROOT = 'https://public.api.connect.skoda-auto.cz';
    private const OPENAPI_URL = self::API_ROOT . '/v3/api-docs';
    private const USER_AGENT = 'Symcon-MySkoda/1.5';
    private const QUOTA_RESERVE = 2;

    public function Update(): void
    {
        if ($this->fetchVehicle(false)) {
            $this->updatePublicApiValuesFromRawData();
            $this->syncVehicleImage(false);
            $this->refreshOpenApi(false);
        }
    }

    public function TestConnection(): bool
    {
        $ok = $this->fetchVehicle(true);
        if ($ok) {
            $this->updatePublicApiValuesFromRawData();
            $this->syncVehicleImage(false);
            $this->refreshOpenApi(false);
        }
        return $ok;
    }
}
