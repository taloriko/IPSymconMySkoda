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
        MySkodaCommandTrait {
        MySkodaCoreTrait::Create as private coreCreate;
        MySkodaCoreTrait::ApplyChanges as private coreApplyChanges;
        MySkodaVariablesTrait::registerVariables as private baseRegisterVariables;

        MySkodaCommandTrait::Create insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::ApplyChanges insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::registerVariables insteadof MySkodaVariablesTrait;
        MySkodaCommandTrait::RequestAction insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::SetChargingLimit insteadof MySkodaCoreTrait;
        MySkodaCommandTrait::SetChargeMode insteadof MySkodaCoreTrait;
    }

    private const API_ROOT = 'https://public.api.connect.skoda-auto.cz';
    private const OPENAPI_URL = self::API_ROOT . '/v3/api-docs';
    private const USER_AGENT = 'Symcon-MySkoda/1.2';
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
