<?php

declare(strict_types=1);

// Integration checks for the official Public API 1.2.0 additions.
require __DIR__ . '/runtime_invariants.php';

$m = new MySkoda();
$m->InstanceID = 88;
$m->Create();
$m->properties['VIN'] = 'TMBJC7NY0N0000001';
$m->properties['SPIN'] = '1234';
$m->properties['EnableRemote'] = true;

$vehicle = [
    'vin' => 'TMBJC7NY0N0000001',
    'charging' => [
        'status' => [
            'state' => 'CHARGING_ERROR',
            'chargingErrorReason' => 'NO_GRID_VOLTAGE',
            'chargePowerInKw' => 0
        ]
    ],
    'auxiliaryHeating' => [
        'targetTemperature' => ['value' => 21, 'unit' => 'CELSIUS'],
        'state' => 'OFF'
    ],
    'operations' => [
        ['name' => 'startAuxiliaryHeating', 'vehicleDependentParameters' => ['targetTemperature', 'startMode']],
        ['name' => 'stopAuxiliaryHeating']
    ]
];
invoke($m, 'ensureVehicleVariables', $vehicle);
invoke($m, 'updateCoreValues', $vehicle);
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('Charging') === false, 'Charging ERROR state must stop active charging indication.');
check($m->GetValue('ChargingState') === 'CHARGING_ERROR', 'Full API charging error state is preserved.');
check($m->GetIDForIdent('ChargingErrorReason') !== false, 'Dedicated charging error reason is created only when returned.');
check($m->GetValue('ChargingErrorReason') === 'NO_GRID_VOLTAGE', 'API charging cause is preserved verbatim.');
check(invoke($m, 'auxiliaryHeatingStartBody', '1234', 23.0) === [
    'spin' => '1234',
    'targetTemperature' => ['value' => 23.0, 'unit' => 'CELSIUS'],
    'startMode' => 'HEATING'
], 'Both optional auxiliary-heating fields are included only when advertised.');

check(invoke($m, 'auxiliaryHeatingStartBody', '1234', 22.0, 'VENTILATION') === [
    'spin' => '1234',
    'targetTemperature' => ['value' => 22.0, 'unit' => 'CELSIUS'],
    'startMode' => 'VENTILATION'
], 'Explicitly advertised ventilation mode may be sent.');

$vehicle['operations'][0]['vehicleDependentParameters'] = ['startMode'];
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
check(invoke($m, 'auxiliaryHeatingStartBody', '1234', 24.0) === [
    'spin' => '1234', 'startMode' => 'HEATING'
], 'Target temperature is excluded without specific vehicle permission.');

$vehicle['operations'][0]['vehicleDependentParameters'] = ['targetTemperature'];
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
check(invoke($m, 'auxiliaryHeatingStartBody', '1234', 31.0, 'VENTILATION') === [
    'spin' => '1234', 'targetTemperature' => ['value' => 30.0, 'unit' => 'CELSIUS']
], 'Start mode is excluded without permission; temperature remains clamped.');

unset($vehicle['operations'][0]['vehicleDependentParameters']);
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
check(invoke($m, 'auxiliaryHeatingStartBody', '1234', 21.0) === ['spin' => '1234'],
    'Older API responses without capability metadata send mandatory PIN only.');

$vehicle['operations'][0]['vehicleDependentParameters'] = [];
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
check(invoke($m, 'auxiliaryHeatingStartBody', '1234', 21.0) === ['spin' => '1234'],
    'Explicitly empty capability list prohibits optional parameters.');

$vehicle['charging']['status']['chargingErrorReason'] = 'ERROR_LOCK';
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('ChargingErrorReason') === 'ERROR_LOCK', 'Charging error cause updates when it changes.');

$vehicle['charging']['status']['chargingErrorReason'] = 'FUTURE_CHARGING_ERROR';
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('ChargingErrorReason') === 'FUTURE_CHARGING_ERROR', 'New, unknown API cause values remain readable.');

unset($vehicle['charging']['status']['state'], $vehicle['charging']['status']['chargingErrorReason']);
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('ChargingErrorReason') === 'FUTURE_CHARGING_ERROR',
    'Omitted charging state must not masquerade as recovery.');

$vehicle['charging']['status']['state'] = 'READY_FOR_CHARGING';
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
check($m->GetValue('ChargingErrorReason') === '', 'Confirmed non-error state clears outdated error reason.');
invoke($m, 'updateCoreValues', $vehicle);
check($m->GetValue('ChargingState') === 'READY_FOR_CHARGING', 'Previously created state ident survives API 1.2.');

echo 'API 1.2.0 contract tests passed (' . $GLOBALS['checks'] . " total checks).\n";
