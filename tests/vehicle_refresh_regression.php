<?php

declare(strict_types=1);

require __DIR__ . '/runtime_invariants.php';

$m = new MySkoda();
$m->InstanceID = 87;
$m->Create();
$m->ApplyChanges();
$m->properties['ShowChargingLocations'] = true;

$vehicle = [
    'vin' => 'TMBJC7NY0N0000001',
    'odometer' => ['mileageInKm' => 113421],
    'parkingPosition' => [
        'state' => 'PARKED',
        'formattedAddress' => 'Old address',
        'gpsCoordinates' => ['latitude' => 48.390759, 'longitude' => 8.965099]
    ],
    'status' => [
        'overall' => [
            'doorsLocked' => 'YES', 'locked' => 'YES', 'doors' => 'CLOSED',
            'windows' => 'CLOSED', 'lights' => 'OFF', 'reliableLockStatus' => 'LOCKED'
        ],
        'detail' => ['trunk' => 'CLOSED', 'bonnet' => 'CLOSED', 'sunroof' => 'CLOSED']
    ],
    'chargingProfiles' => ['profiles' => [['id' => 1, 'name' => 'Home']]],
    'futureData' => ['value' => 1]
];
$refresh = static function (array $vehicle) use ($m): void {
    invoke($m, 'ensureVehicleVariables', $vehicle);
    invoke($m, 'updateCoreValues', $vehicle);
    invoke($m, 'updateDetailValues', $vehicle, ['vehicle' => $vehicle]);
    invoke($m, 'ensurePublicApiVariables', $vehicle);
    $m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
    invoke($m, 'updatePublicApiValuesFromRawData');
};
$m->RegisterVariableString('APIChargingProfile1Name', 'Legacy profile', [], 2000);
$refresh($vehicle);
check($m->GetValue('Mileage') === 113421, 'Initial mileage');
check($m->GetValue('APIChargingProfile1Name') === 'Home', 'Legacy profile updated');

$vehicle['odometer']['mileageInKm'] = 113450;
$vehicle['parkingPosition']['formattedAddress'] = 'New address';
$vehicle['parkingPosition']['gpsCoordinates']['latitude'] = 48.45;
$vehicle['status']['overall']['doorsLocked'] = 'NO';
$vehicle['status']['overall']['doors'] = 'OPEN';
$vehicle['status']['overall']['windows'] = 'OPEN';
$vehicle['status']['detail']['trunk'] = 'OPEN';
$vehicle['chargingProfiles']['profiles'][0]['name'] = 'Other home';
$vehicle['futureData']['value'] = 1.5; // Existing Integer variable must not block update.
$refresh($vehicle);
check($m->GetValue('Mileage') === 113450, 'Mileage updated');
check($m->GetValue('ParkingAddress') === 'New address', 'Address updated');
check($m->GetValue('Latitude') === 48.45, 'Coordinates updated');
check($m->GetValue('DoorsLocked') === 'NO', 'Lock updated');
check($m->GetValue('DoorsOpen') === 'OPEN', 'Doors updated');
check($m->GetValue('WindowsOpen') === 'OPEN', 'Windows updated');
check($m->GetValue('TrunkOpen') === 'OPEN', 'Trunk updated');
check($m->GetValue('APIChargingProfile1Name') === 'Other home', 'Legacy profile survives update');
check($m->GetValue('APIFutureDataValue') === 1, 'Incompatible dynamic type is skipped');

echo 'Vehicle refresh regression passed (' . $GLOBALS['checks'] . " checks).\n";
