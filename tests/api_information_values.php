<?php

declare(strict_types=1);

require __DIR__ . '/runtime_invariants.php';

$informationModule = new MySkoda();
$informationModule->InstanceID = 30;
$informationModule->Create();
$informationModule->ApplyChanges();

check($informationModule->GetIDForIdent('APICarType') === false, 'API information is not pre-created without response data.');
check($informationModule->GetIDForIdent('APIAuxiliaryHeatingState') === false, 'Optional services are not pre-created.');

$store = static function (array $vehicle, ?array $errors = null) use ($informationModule): void {
    $envelope = ['vehicle' => $vehicle];
    if ($errors !== null) {
        $envelope['errors'] = $errors;
    }

    $informationModule->WriteAttributeString('RawData', json_encode($envelope, JSON_THROW_ON_ERROR));
    invoke($informationModule, 'syncVehicleVariablesFromRawData');
};

$vin = 'TMBJC7NY0N0000002';
$store(['vin' => $vin]);
check($informationModule->GetValue('VIN') === $vin, 'A delivered VIN creates the VIN variable.');
check($informationModule->GetIDForIdent('APICarType') === false, 'Undelivered fuel type still does not exist.');

$store([
    'vin' => $vin,
    'fuelStatus' => [
        'carType' => 'GASOLINE',
        'primaryEngineRange' => [
            'engineType' => 'GASOLINE',
            'currentFuelLevelInPercent' => 64,
            'remainingRangeInKm' => 430
        ],
        'totalRangeInKm' => 430
    ],
    'auxiliaryHeating' => [
        'state' => 'OFF',
        'durationInSeconds' => 2400
    ],
    'operations' => [
        ['name' => 'startAuxiliaryHeating'],
        ['name' => 'stopAuxiliaryHeating']
    ]
]);

check($informationModule->GetValue('APICarType') === 'GASOLINE', 'Delivered car type creates a known variable.');
check($informationModule->GetValue('APIPrimaryEngineType') === 'GASOLINE', 'Delivered engine type creates a known variable.');
check($informationModule->GetValue('FuelLevel') === 64, 'Delivered fuel level creates a known presentation.');
check($informationModule->GetValue('PrimaryEngineRange') === 430, 'Delivered engine range creates a known presentation.');
check($informationModule->GetValue('TotalRange') === 430, 'Delivered total range creates a known presentation.');
check($informationModule->GetValue('APIAuxiliaryHeatingState') === 'OFF', 'Delivered auxiliary heating state creates a variable.');
check($informationModule->GetValue('AuxiliaryHeatingDuration') === 2400, 'Delivered auxiliary heating duration creates a variable.');
check(str_contains($informationModule->GetValue('APIRemoteOperations'), 'startAuxiliaryHeating'), 'Delivered operation list is preserved.');

// Missing fields on a later response do not become unsupported/unknown and do not lose their last value.
$registrations = $informationModule->registrations;
$store(['vin' => $vin]);
check($informationModule->GetValue('APICarType') === 'GASOLINE', 'Missing later car type retains the last value.');
check($informationModule->GetValue('FuelLevel') === 64, 'Missing later fuel level retains the last value.');
check($informationModule->GetValue('APIAuxiliaryHeatingState') === 'OFF', 'Missing later service state retains the last value.');
check($informationModule->registrations === $registrations, 'A smaller response does not register duplicates.');

// A newly appearing known service is added later.
$store([
    'vin' => $vin,
    'activeVentilation' => ['state' => 'ON']
]);
check($informationModule->GetValue('APIActiveVentilationState') === 'ON', 'A later known field is created when it first appears.');

// Response-level errors are represented only when the response actually contains them.
check($informationModule->GetIDForIdent('PartialErrors') === false, 'No errors field means no error variable.');
$store(
    ['vin' => $vin],
    [['type' => 'CHARGING_UNAVAILABLE']]
);
check($informationModule->GetIDForIdent('PartialErrors') !== false, 'An errors field creates the diagnostic variable.');
check(str_contains($informationModule->GetValue('PartialErrors'), 'CHARGING_UNAVAILABLE'), 'Partial errors retain the API payload.');

// APISupportedFeatures is intentionally no longer synthesized on new installations.
check($informationModule->GetIDForIdent('APISupportedFeatures') === false, 'Only response-backed vehicle variables are created.');

echo 'Dynamic API information checks passed (' . $GLOBALS['checks'] . " total checks).\n";
