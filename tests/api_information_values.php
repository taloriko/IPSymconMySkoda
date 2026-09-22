<?php

declare(strict_types=1);

// Load the isolated Symcon host and its existing regression checks.
require __DIR__ . '/runtime_invariants.php';

$informationModule = new MySkoda();
$informationModule->InstanceID = 20;
$informationModule->Create();
$informationModule->ApplyChanges();

$informationIdents = [
    'APICarType', 'APIPrimaryEngineType', 'APISecondaryEngineType',
    'APISupportedFeatures', 'APIAvailableChargeModes', 'APIRemoteOperations',
    'APIAuxiliaryHeatingState', 'APIActiveVentilationState'
];
$metadata = [];
foreach ($informationIdents as $ident) {
    check($informationModule->GetValue($ident) === 'Not retrieved yet', 'Initial information state: ' . $ident);
    $id = $informationModule->GetIDForIdent($ident);
    $metadata[$id] = $GLOBALS['objects'][$id];
    unset($metadata[$id]['Value']);
}
$informationRegistrations = $informationModule->registrations;
$informationMetadataWrites = $GLOBALS['metadataWrites'];
$store = static function (array $vehicle, mixed $errors = null) use ($informationModule): void {
    $envelope = ['vehicle' => $vehicle];
    if ($errors !== null) {
        $envelope['errors'] = $errors;
    }
    $informationModule->WriteAttributeString('RawData', json_encode($envelope, JSON_THROW_ON_ERROR));
    invoke($informationModule, 'updatePublicApiValuesFromRawData');
};
$vehicleInfo = ['vin' => 'TMBJC7NY0N0000002']; // Synthetic fixture only.
$scalarIdents = ['APICarType', 'APIPrimaryEngineType', 'APISecondaryEngineType', 'APIAuxiliaryHeatingState', 'APIActiveVentilationState'];

// A normal response without errors or optional parts is not a support verdict.
$store($vehicleInfo);
foreach ($informationIdents as $ident) {
    check($informationModule->GetValue($ident) === 'Not provided', 'Missing information without errors: ' . $ident);
}
foreach ([null, '', '   '] as $empty) {
    $store($vehicleInfo + [
        'fuelStatus' => ['carType' => $empty, 'primaryEngineRange' => ['engineType' => $empty], 'secondaryEngineRange' => ['engineType' => $empty]],
        'auxiliaryHeating' => ['state' => $empty],
        'activeVentilation' => ['state' => $empty]
    ], []);
    foreach ($scalarIdents as $ident) {
        check($informationModule->GetValue($ident) === 'Not provided', 'Null/empty information: ' . $ident);
    }
}

// Non-empty API values and existing list serialization remain unchanged.
$operations = [['name' => 'startCharging'], ['name' => 'stopCharging']];
$store($vehicleInfo + [
    'fuelStatus' => ['carType' => ' bev ', 'primaryEngineRange' => ['engineType' => 'electric'], 'secondaryEngineRange' => ['engineType' => 'UNKNOWN']],
    'auxiliaryHeating' => ['state' => 'OFF'],
    'activeVentilation' => ['state' => 'ON'],
    'charging' => ['settings' => ['availableChargeModes' => ['MANUAL', 'TIMER']]],
    'operations' => $operations
]);
foreach (['APICarType' => 'BEV', 'APIPrimaryEngineType' => 'ELECTRIC', 'APISecondaryEngineType' => 'UNKNOWN', 'APIAuxiliaryHeatingState' => 'OFF', 'APIActiveVentilationState' => 'ON', 'APIAvailableChargeModes' => 'MANUAL, TIMER'] as $ident => $expected) {
    check($informationModule->GetValue($ident) === $expected, 'Actual API information: ' . $ident);
}
check($informationModule->GetValue('APIRemoteOperations') === json_encode($operations), 'Structured operations remain JSON.');
check($informationModule->GetValue('APISupportedFeatures') === 'fuelStatus, auxiliaryHeating, activeVentilation, charging', 'Feature summary retains its existing order.');
$store($vehicleInfo, []);
foreach ($scalarIdents as $ident) {
    check($informationModule->GetValue($ident) === 'Not provided', 'Missing information must replace stale values: ' . $ident);
}

// Only exact, matching component reports may establish a specific reason.
foreach (['UNSUPPORTED' => 'Unsupported', 'DISABLED' => 'Service disabled', 'UNAVAILABLE' => 'Temporarily unavailable'] as $suffix => $expected) {
    $store($vehicleInfo, [
        ['type' => 'FUEL_STATUS_' . $suffix],
        ['type' => 'AUXILIARY_HEATING_' . $suffix],
        ['type' => 'ACTIVE_VENTILATION_' . $suffix],
        ['type' => 'CHARGING_' . $suffix]
    ]);
    foreach (array_merge($scalarIdents, ['APIAvailableChargeModes']) as $ident) {
        check($informationModule->GetValue($ident) === $expected, 'Explicit component reason: ' . $ident . '/' . $suffix);
    }
    check($informationModule->GetValue('APIRemoteOperations') === 'Not provided', 'Component reports must not affect unrelated operations.');
}
$store($vehicleInfo, [
    ['type' => 'CHARGING_PROFILES_UNSUPPORTED'], ['type' => 'FUEL_STATUS_OTHER'],
    ['type' => ['FUEL_STATUS_UNSUPPORTED']], ['type' => null], [], 'invalid', null
]);
foreach (array_merge($scalarIdents, ['APIAvailableChargeModes']) as $ident) {
    check($informationModule->GetValue($ident) === 'Not provided', 'Unknown/malformed/unrelated reports must not imply unsupported: ' . $ident);
}
$store($vehicleInfo, 'invalid');
check($informationModule->GetValue('APICarType') === 'Not provided', 'Malformed errors collection is ignored.');
$store($vehicleInfo + ['fuelStatus' => ['carType' => 'BEV']], [['type' => 'FUEL_STATUS_UNSUPPORTED']]);
check($informationModule->GetValue('APICarType') === 'BEV', 'A delivered field remains authoritative.');
check($informationModule->GetValue('APIPrimaryEngineType') === 'Unsupported', 'Missing sibling uses explicit component reason.');

// Delivered empty lists are distinguishable from absent data.
$store($vehicleInfo + ['charging' => ['settings' => ['availableChargeModes' => []]], 'operations' => []]);
check($informationModule->GetValue('APIAvailableChargeModes') === 'No entries', 'Explicit empty modes list.');
check($informationModule->GetValue('APIRemoteOperations') === 'No entries', 'Explicit empty operations list.');
$store($vehicleInfo + ['remoteOperations' => ['startCharging']]);
check($informationModule->GetValue('APIRemoteOperations') === 'startCharging', 'Existing alternate operations field still works.');
foreach ([0, '0'] as $zero) {
    invoke($informationModule, 'setPublicApiInformation', 'APICarType', $zero);
    check($informationModule->GetValue('APICarType') === '0', 'Zero is a supplied value, not missing.');
}
invoke($informationModule, 'setPublicApiInformation', 'APICarType', false);
check($informationModule->GetValue('APICarType') === 'false', 'False is a supplied value, not missing.');

// The change writes values only: no object migration, metadata repair or new IDs.
check($informationModule->registrations === $informationRegistrations, 'Updates must not register more variables.');
check($GLOBALS['metadataWrites'] === $informationMetadataWrites, 'Updates must not change names, icons or positions.');
foreach ($metadata as $id => $expected) {
    $actual = $GLOBALS['objects'][$id];
    unset($actual['Value']);
    check($actual === $expected, 'Information variable metadata must be preserved.');
}
$locale = json_decode(file_get_contents($root . '/MySkoda/locale.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (['Not retrieved yet', 'Not provided', 'No entries', 'Unsupported', 'Service disabled', 'Temporarily unavailable'] as $caption) {
    check(isset($locale['translations']['de'][$caption]), 'German information caption missing: ' . $caption);
}
echo 'API information checks passed (' . $GLOBALS['checks'] . " total checks).\n";
