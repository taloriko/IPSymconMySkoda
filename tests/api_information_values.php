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


$vehicle['charging'] = ['isVehicleInSavedLocation' => true];
$vehicle['chargingProfiles'] = [
    'profiles' => [
        [
            'id' => 1,
            'name' => 'Home',
            'settings' => [
                'autoUnlockPlugWhenCharged' => 'OFF',
                'maxChargingCurrent' => 'MAXIMUM',
                'minBatteryStateOfCharge' => ['minimumBatteryStateOfChargeInPercent' => 20],
                'targetStateOfChargeInPercent' => 90,
                'futureEnabled' => false, 'futureCount' => 0
            ],
            'preferredChargingTimes' => [
                ['id' => 1, 'enabled' => false, 'startTime' => '22:00', 'endTime' => '06:00']
            ],
            'timers' => [
                ['id' => 1, 'enabled' => false, 'type' => 'RECURRING',
                    'recurringOn' => ['MONDAY', 'TUESDAY'], 'time' => '07:00']
            ]
        ],
        ['id' => 3, 'name' => 'Work', 'settings' => ['targetStateOfChargeInPercent' => 75]]
    ]
];
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('AtSavedChargingLocation') === true, 'Basic saved location is independent of details.');
check($m->GetIDForIdent('APIChargingProfile1Name') === false,
    'Legacy API prefix is not used for saved charging location idents.');
check($m->GetIDForIdent('ChargingProfile1Name') === false, 'No profile variables with saved locations disabled.');
$m->properties['ShowDetails'] = true;
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetIDForIdent('ChargingProfile1Name') === false, 'Diagnostics must not create charging locations.');
check(json_decode($m->GetChargingProfiles(), true)['profiles'][0]['name'] === 'Home', 'Full profiles remain readable.');

$m->properties['ShowDetails'] = false;
$m->properties['ShowChargingLocations'] = true;
invoke($m, 'updatePublicApiValuesFromRawData');
$expectedProfiles = [
    'ChargingProfile1ID' => 1,
    'ChargingProfile1Name' => 'Home',
    'ChargingProfile1AutoUnlockPlug' => 'OFF',
    'ChargingProfile1MaxChargingCurrent' => 'MAXIMUM',
    'ChargingProfile1MinimumSOC' => 20,
    'ChargingProfile1TargetSOC' => 90,
    'ChargingProfile1SettingsFutureEnabled' => false,
    'ChargingProfile1SettingsFutureCount' => 0,
    'ChargingProfile1PreferredTime1Enabled' => false,
    'ChargingProfile1PreferredTime1StartTime' => '22:00',
    'ChargingProfile1PreferredTime1EndTime' => '06:00',
    'ChargingProfile1Timer1Enabled' => false,
    'ChargingProfile1Timer1Type' => 'RECURRING',
    'ChargingProfile1Timer1RecurringOn' => 'MONDAY, TUESDAY',
    'ChargingProfile1Timer1Time' => '07:00',
    'ChargingProfile3Name' => 'Work',
    'ChargingProfile3TargetSOC' => 75
];
foreach ($expectedProfiles as $ident => $value) {
    check($m->GetIDForIdent($ident) !== false, 'Missing saved location field: ' . $ident);
    check($m->GetValue($ident) === $value, 'Unexpected saved location value: ' . $ident);
    $presentation = IPS_GetVariable($m->GetIDForIdent($ident))['VariablePresentation'];
    check(($presentation['PRESENTATION'] ?? null) === VARIABLE_PRESENTATION_VALUE_PRESENTATION, 'Missing value presentation: ' . $ident);
    check(trim((string) ($presentation['ICON'] ?? '')) !== '', 'Missing icon: ' . $ident);
}
check(IPS_GetVariable($m->GetIDForIdent('ChargingProfile1TargetSOC'))['VariablePresentation']['SUFFIX'] === ' %',
    'Charge target displays percent.');

// Names start with a compact stable location ID; preferred charging times use "Zeit".
$compactNames = [
    'ChargingProfile1ID' => 'LO1 · Profile ID',
    'ChargingProfile1Name' => 'LO1 · Name',
    'ChargingProfile1AutoUnlockPlug' => 'LO1 · Unlock plug',
    'ChargingProfile1MaxChargingCurrent' => 'LO1 · AC current',
    'ChargingProfile1MinimumSOC' => 'LO1 · Min. SOC',
    'ChargingProfile1TargetSOC' => 'LO1 · Target SOC',
    'ChargingProfile1SettingsFutureEnabled' => 'LO1 · Future Enabled',
    'ChargingProfile1PreferredTime1Enabled' => 'LO1 · Active Zeit1',
    'ChargingProfile1PreferredTime1StartTime' => 'LO1 · Start Zeit1',
    'ChargingProfile1PreferredTime1EndTime' => 'LO1 · End Zeit1',
    'ChargingProfile1Timer1Enabled' => 'LO1 · Active T1',
    'ChargingProfile1Timer1Type' => 'LO1 · Type T1',
    'ChargingProfile1Timer1RecurringOn' => 'LO1 · Days T1',
    'ChargingProfile1Timer1Time' => 'LO1 · Time T1',
    'ChargingProfile3Name' => 'LO3 · Name',
    'ChargingProfile3TargetSOC' => 'LO3 · Target SOC'
];
foreach ($compactNames as $ident => $expectedName) {
    $actualName = IPS_GetObject($m->GetIDForIdent($ident))['ObjectName'];
    check($actualName === $expectedName, 'Expected compact display name for ' . $ident);
    check(strlen($actualName) <= 22, 'Mobile label should be compact: ' . $ident);
    check(!str_starts_with($actualName, 'Charging location'), 'Avoid long common prefix: ' . $ident);
    check(str_starts_with($actualName, str_contains($ident, 'Profile3') ? 'LO3 · ' : 'LO1 · '), 'Location ID must come first: ' . $ident);
}
$germanNames = json_decode(file_get_contents(__DIR__ . '/../MySkoda/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
foreach (['Profile ID', 'Unlock plug', 'AC current', 'Min. SOC', 'Target SOC', 'Active', 'Start', 'End', 'Type', 'Days'] as $key) {
    check(isset($germanNames[$key]) && trim($germanNames[$key]) !== '', 'German compact label translation is missing: ' . $key);
}
check($germanNames['Target SOC'] === 'Ziel-SoC', 'Localized target SOC stays concise.');
$varId = $m->GetIDForIdent('ChargingProfile1Name');
$GLOBALS['objects'][$varId]['ObjectName'] = 'Own label';
$registrations = $m->registrations;
$vehicle['chargingProfiles']['profiles'] = array_reverse($vehicle['chargingProfiles']['profiles']);
$vehicle['chargingProfiles']['profiles'][1]['name'] = 'Home changed';
$vehicle['chargingProfiles']['profiles'][1]['timers'][0]['time'] = '08:00';
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('ChargingProfile1Name') === 'Home changed', 'Stable ID after reordering.');
check($m->GetValue('ChargingProfile1Timer1Time') === '08:00', 'Timer ID survives reordering.');
check($m->registrations === $registrations, 'No duplicate profile variables.');
check($GLOBALS['objects'][$varId]['ObjectName'] === 'Own label', 'User-owned metadata preserved.');

$m->properties['ShowChargingLocations'] = false;
$vehicle['chargingProfiles']['profiles'][] = ['id' => 2, 'name' => 'New location'];
$vehicle['chargingProfiles']['profiles'][1]['name'] = 'Home again';
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetIDForIdent('ChargingProfile2Name') === false, 'No new profiles while details disabled.');
check($m->GetValue('ChargingProfile1Name') === 'Home again', 'Existing detail values keep updating.');

// Simulate a user deleting an old variable while the option is off, then
// re-enabling the option. Creation uses the new short name and keeps its ident.
$oldTargetId = $m->GetIDForIdent('ChargingProfile1TargetSOC');
unset($GLOBALS['objects'][$oldTargetId]);
check($m->GetIDForIdent('ChargingProfile1TargetSOC') === false, 'Manually deleted variable is absent.');
$m->properties['ShowChargingLocations'] = true;
invoke($m, 'updatePublicApiValuesFromRawData');
$newTargetId = $m->GetIDForIdent('ChargingProfile1TargetSOC');
check($newTargetId !== false && $newTargetId !== $oldTargetId, 'Enabling location details recreates missing variables.');
check(IPS_GetObject($newTargetId)['ObjectName'] === 'LO1 · Target SOC', 'Recreated variable gets compact display name.');
check($m->GetValue('ChargingProfile1TargetSOC') === 90, 'Recreated variable receives the current API value.');
check($m->GetIDForIdent('APIChargingProfile1TargetSOC') === false,
    'New saved charging locations never recreate old API-prefixed idents.');
check($m->GetIDForIdent('APIFutureDataNewNumber') !== false,
    'Ordinary dynamically discovered API fields retain their API prefix.');

echo 'Dynamic API vehicle checks passed (' . $GLOBALS['checks'] . " total checks).\n";
