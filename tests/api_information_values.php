<?php

declare(strict_types=1);

require __DIR__ . '/runtime_invariants.php';

$m = new MySkoda();
$m->InstanceID = 20;
$m->Create();
$m->ApplyChanges();

$vehicle = [
    'vin' => 'TMBJC7NY0N0000002',
    'fuelStatus' => [
        'carType' => 'GASOLINE',
        'primaryEngineRange' => [
            'currentFuelLevelInPercent' => 64,
            'currentSoCInPercent' => 64,
            'engineType' => 'GASOLINE',
            'remainingRangeInKm' => 430
        ],
        'totalRangeInKm' => 430
    ],
    'auxiliaryHeating' => [
        'state' => 'OFF',
        'durationInSeconds' => 2400
    ],
    'odometer' => ['mileageInKm' => 37953],
    'status' => [
        'overall' => [
            'doorsLocked' => 'YES',
            'locked' => 'YES',
            'doors' => 'CLOSED',
            'windows' => 'CLOSED',
            'lights' => 'OFF',
            'reliableLockStatus' => 'LOCKED'
        ],
        'detail' => [
            'sunroof' => 'CLOSED',
            'trunk' => 'CLOSED',
            'bonnet' => 'CLOSED'
        ]
    ],
    'parkingPosition' => [
        'state' => 'PARKED',
        'formattedAddress' => 'Fixture address',
        'gpsCoordinates' => ['latitude' => 48.1, 'longitude' => 9.1]
    ],
    'operations' => [
        ['name' => 'startAuxiliaryHeating'],
        ['name' => 'stopAuxiliaryHeating']
    ],
    'futureData' => [
        'newBoolean' => true,
        'newNumber' => 12.5,
        'newText' => 'AVAILABLE'
    ]
];

invoke($m, 'ensureVehicleVariables', $vehicle);
invoke($m, 'ensurePublicApiVariables', $vehicle);
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updateCoreValues', $vehicle);
invoke($m, 'updateDetailValues', $vehicle, ['vehicle' => $vehicle]);
invoke($m, 'updatePublicApiValuesFromRawData');

check($m->GetIDForIdent('StateOfCharge') === false, 'EV battery state must not exist for the combustion fixture.');
check($m->GetIDForIdent('Charging') === false, 'Charging action must not exist without charging data.');
check($m->GetIDForIdent('Climate') === false, 'Climate action must not exist without air-conditioning data.');

check($m->GetValue('FuelLevelPercent') === 64, 'Fuel level is created and populated.');
check($m->GetValue('PrimaryEngineSOC') === 64, 'Primary engine SoC is created and populated.');
check($m->GetValue('FuelRange') === 430, 'Fuel range is created and populated.');
check($m->GetValue('TotalRange') === 430, 'Total range is created and populated.');
check($m->GetValue('APICarType') === 'GASOLINE', 'Car type is preserved.');
check($m->GetValue('APIPrimaryEngineType') === 'GASOLINE', 'Engine type is preserved.');
check($m->GetValue('APIAuxiliaryHeatingState') === 'OFF', 'Auxiliary heating state is preserved.');
check($m->GetValue('AuxiliaryHeatingDuration') === 40, 'Auxiliary heating duration is converted to minutes.');
check(invoke($m, 'auxiliaryHeatingStartBody', '1234', 22.0) === ['spin' => '1234'], 'Auxiliary heating sends only the required S-PIN when the vehicle exposes no target temperature.');
check(invoke($m, 'vehicleProvidesValue', 'airConditioning.airConditioningWithoutExternalPower') === false, 'Combustion fixture does not expose the external-power climate setting.');
check($m->GetIDForIdent('AuxiliaryHeating') !== false, 'Auxiliary heating control is created when status is delivered.');
check($m->GetValue('AuxiliaryHeating') === false, 'Auxiliary heating control follows OFF state.');
$m->properties['SPIN'] = '1234';
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'applyActions');
$auxiliaryHeatingId = $m->GetIDForIdent('AuxiliaryHeating');
check(($GLOBALS['objects'][$auxiliaryHeatingId]['Action'] ?? false) === true, 'Auxiliary heating becomes actionable with S-PIN and start/stop operations.');


check($m->GetValue('APIFutureDataNewBoolean') === true, 'Unknown boolean field is added dynamically.');
check($m->GetValue('APIFutureDataNewNumber') === 12.5, 'Unknown numeric field is added dynamically.');
check($m->GetValue('APIFutureDataNewText') === 'AVAILABLE', 'Unknown string field is added dynamically.');

check($m->GetIDForIdent('APIFuelStatusCarCapturedTimestamp') === false, 'Capture timestamps are not duplicated as dynamic variables.');

$metadataId = $m->GetIDForIdent('FuelLevelPercent');
$before = $GLOBALS['objects'][$metadataId];
$vehicle['fuelStatus']['primaryEngineRange']['currentFuelLevelInPercent'] = 63;
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'ensurePublicApiVariables', $vehicle);
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('FuelLevelPercent') === 63, 'Existing variables continue to update.');
foreach (['ObjectName', 'ObjectPosition', 'ObjectIcon', 'Presentation'] as $key) {
    check($GLOBALS['objects'][$metadataId][$key] === $before[$key], 'Existing metadata remains untouched: ' . $key);
}

echo 'Dynamic API vehicle checks passed (' . $GLOBALS['checks'] . " total checks).\n";
