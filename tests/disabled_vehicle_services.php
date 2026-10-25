<?php

declare(strict_types=1);

// Additional Symcon host doubles for notification integration. No real VIN,
// token, remote access or network call is used.
$GLOBALS['serviceNotificationCalls'] = [];
$GLOBALS['serviceNotificationFail'] = false;

function IPS_InstanceExists(int $instanceId): bool
{
    return $instanceId === 900;
}

function IPS_GetInstance(int $instanceId): array
{
    return [
        'ModuleInfo' => [
            'ModuleType' => $instanceId === 900 ? 6 : 3,
            'ModuleName' => 'Tile Visualization',
            'ModuleID' => '{11111111-1111-1111-1111-111111111111}'
        ]
    ];
}

function VISU_PostNotification(int $instanceId, string $title, string $text, string $type, int $duration): bool
{
    $GLOBALS['serviceNotificationCalls'][] = compact('instanceId', 'title', 'text', 'type', 'duration');
    return !$GLOBALS['serviceNotificationFail'];
}

require __DIR__ . '/runtime_invariants.php';

$n = new MySkoda();
$n->InstanceID = 99;
$n->Create();
$n->properties['VIN'] = 'TMBJC7NY0N0000001';
$n->properties['APIToken'] = 'test-token-only';
$n->properties['NotifyKeyExpiry'] = true;
$n->properties['NotificationInstanceID'] = 900;

$errors = [
    ['type' => 'VEHICLE_STATUS_DISABLED', 'description' => 'Vehicle status disabled by Skoda'],
    ['type' => 'ODOMETER_DISABLED', 'description' => 'Odometer disabled by Skoda'],
    ['type' => 'PARKING_POSITION_DISABLED', 'description' => 'Parking disabled by Skoda']
];
$envelope = [
    'vehicle' => ['vin' => $n->properties['VIN'], 'name' => 'Synthetic test vehicle'],
    'errors' => $errors
];

$n->WriteAttributeString('RawData', json_encode($envelope, JSON_THROW_ON_ERROR));
$form = json_decode($n->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$connection = $form['elements'][0]['caption'] === 'Connection'
    ? $form['elements'][0]
    : array_values(array_filter($form['elements'], static fn (array $e): bool => ($e['caption'] ?? '') === 'Connection'))[0];
$names = array_map(static fn (array $item): string => (string) ($item['name'] ?? ''), $connection['items']);
$tokenIndex = array_search('APIToken', $names, true);
check($tokenIndex !== false, 'Token control exists');
check(($names[$tokenIndex + 1] ?? '') === 'DisabledVehicleServicesWarning', 'Warning is immediately after token');
$warning = $connection['items'][$tokenIndex + 1];
check($warning['visible'] === true, 'Warning is visible for known disabled services');
check(str_contains($warning['caption'], 'Vehicle status'), 'Warning explains vehicle status');
check(str_contains($warning['caption'], 'Odometer reading'), 'Warning explains odometer');
check(str_contains($warning['caption'], 'Parking position'), 'Warning explains parking position');
check(str_contains($warning['caption'], 'not being updated'), 'Warning identifies stale data');
check(str_contains($warning['caption'], 'possible reason'), 'Service expiry is only a possible reason');

invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 1, 'One info notification is sent for disabled services');
$first = $GLOBALS['serviceNotificationCalls'][0];
check($first['instanceId'] === 900 && $first['type'] === 'Info', 'Existing visualization receives an Info notification');
check(str_contains($first['text'], 'not being updated'), 'Notification describes missing updates');
check(str_contains($first['text'], 'Car Connect'), 'Notification mentions only possible service expiry');
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 1, 'Polling does not repeat the same notification');

$reverse = $envelope;
$reverse['errors'] = array_reverse($errors);
invoke($n, 'updateDisabledVehicleServices', $reverse);
check(count($GLOBALS['serviceNotificationCalls']) === 1, 'Error order cannot retrigger notification');

$healthy = $envelope;
$healthy['errors'] = [['type' => 'AIR_CONDITIONING_UNAVAILABLE']];
$n->WriteAttributeString('RawData', json_encode($healthy, JSON_THROW_ON_ERROR));
invoke($n, 'updateDisabledVehicleServices', $healthy);
$form = json_decode($n->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$connection = array_values(array_filter($form['elements'], static fn (array $e): bool => ($e['caption'] ?? '') === 'Connection'))[0];
$warning = $connection['items'][$tokenIndex + 1];
check($warning['visible'] === false, 'A successful response without disabled codes clears warning');
check($n->ReadAttributeString('DisabledServicesNotifiedSignature') === '', 'Recovery clears notification state');

$n->WriteAttributeString('RawData', json_encode($envelope, JSON_THROW_ON_ERROR));
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 2, 'A new incident after recovery sends a new notification');

$n->properties['VIN'] = 'TMBJC7NY0N0000002';
check(invoke($n, 'disabledVehicleServiceWarningCaption') === '', 'The previous vehicle warning is not shown for a new VIN');
$n->properties['VIN'] = $envelope['vehicle']['vin'];

$n->properties['NotifyKeyExpiry'] = false;
invoke($n, 'updateDisabledVehicleServices', $healthy);
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 2, 'Disabled notifications setting prevents sending without hiding warning');
$n->properties['NotifyKeyExpiry'] = true;
$n->properties['NotificationInstanceID'] = 0;
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 2, 'Missing notification target prevents sending');
$n->properties['NotificationInstanceID'] = 900;
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 3, 'Adding a valid target enables info notifications');

invoke($n, 'updateDisabledVehicleServices', $healthy);
$GLOBALS['serviceNotificationFail'] = true;
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 4, 'Failed notification is attempted');
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 4, 'Failed notification is throttled for 24 hours');

$translations = json_decode((string) file_get_contents(__DIR__ . '/../MySkoda/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
$notice = $translations['Skoda has disabled these vehicle services: %s. The affected information is not being updated; existing values may be outdated. A possible reason is an expired Car Connect remote access service.'] ?? '';
check(str_contains($notice, 'nicht aktualisiert'), 'German warning states values are not updated');
check(str_contains($notice, 'möglicher Grund'), 'German warning does not claim proven subscription expiry');

echo 'Skoda disabled services checks passed (' . $GLOBALS['checks'] . " total checks).\n";
