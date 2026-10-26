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
    check(count(preg_split('//u', $title, -1, PREG_SPLIT_NO_EMPTY)) <= 32, 'VISU title must fit 32 characters');
    check(count(preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY)) <= 256, 'VISU body must fit 256 characters');
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
$n->properties['NotifyDisabledVehicleServices'] = true;
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

// Separate opt-in does not affect the on-screen warning and must not latch.
$n->WriteAttributeBoolean('DisabledServicesNotificationSent', false);
$n->WriteAttributeInteger('DisabledServicesNotificationLastAttempt', 0);
$n->properties['NotifyDisabledVehicleServices'] = false;
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 0, 'Disabled service option suppresses notification');
check(!$n->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'Disabled option does not latch notification');
check($n->ReadAttributeString('DisabledServicesPendingRecovery') !== '', 'Outage remains tracked while disabled');
$n->properties['NotifyDisabledVehicleServices'] = true;

invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 1, 'One info notification is sent for disabled services');
$first = $GLOBALS['serviceNotificationCalls'][0];
check($first['instanceId'] === 900 && $first['type'] === 'Info', 'Existing visualization receives an Info notification');
check(str_contains($first['text'], 'not updated'), 'Notification describes missing updates');
check(str_contains($first['text'], 'Car Connect'), 'Notification mentions only possible service expiry');
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 1, 'Polling does not repeat the same notification');

$reverse = $envelope;
$reverse['errors'] = array_reverse($errors);
invoke($n, 'updateDisabledVehicleServices', $reverse);
check(count($GLOBALS['serviceNotificationCalls']) === 1, 'Error order cannot retrigger notification');

$healthy = $envelope;
$healthy['errors'] = [];
$healthy['vehicle']['status'] = ['overall' => ['locked' => 'YES']];
$healthy['vehicle']['odometer'] = ['mileageInKm' => 15000];
$healthy['vehicle']['parkingPosition'] = ['state' => 'PARKED'];
$n->WriteAttributeString('RawData', json_encode($healthy, JSON_THROW_ON_ERROR));
invoke($n, 'updateDisabledVehicleServices', $healthy);
check(!$n->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'Recovery resets sent flag');
check($n->ReadAttributeString('DisabledServicesPendingRecovery') === '', 'Recovery clears pending services');

$n->WriteAttributeString('RawData', json_encode($envelope, JSON_THROW_ON_ERROR));
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 2, 'A second incident notifies again');
check($n->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'Successful send stores sent flag');

$n->WriteAttributeBoolean('DisabledServicesNotificationSent', false);
$n->WriteAttributeInteger('DisabledServicesNotificationLastAttempt', 0);
$n->WriteAttributeString('DisabledServicesPendingRecovery', '');
$n->WriteAttributeString('DisabledServicesNotifiedSignature', implode('|', array_column($errors, 'type')));
$n->WriteAttributeInteger('DisabledServicesLastAttemptAt', time());
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 3, 'Legacy notification attempt attributes cannot suppress initial alert');

$missingErrors = $envelope;
$missingErrors['errors'] = [];
$n->WriteAttributeString('RawData', json_encode($missingErrors, JSON_THROW_ON_ERROR));
invoke($n, 'updateDisabledVehicleServices', $missingErrors);
check($n->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'Missing codes without restored fields retain sent flag');
check(invoke($n, 'disabledVehicleServiceWarningCaption') !== '', 'Warning survives missing errors if data are still absent');

$n->WriteAttributeString('RawData', json_encode($healthy, JSON_THROW_ON_ERROR));
invoke($n, 'updateDisabledVehicleServices', $healthy);
check(!$n->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'Complete recovery rearms alert');

$GLOBALS['serviceNotificationFail'] = true;
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(!$n->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'Failed send must not set flag');
check(count($GLOBALS['serviceNotificationCalls']) === 4, 'Failed send is attempted once');
invoke($n, 'updateDisabledVehicleServices', $envelope);
check(count($GLOBALS['serviceNotificationCalls']) === 4, 'Failed send is throttled');
$n->WriteAttributeInteger('DisabledServicesNotificationLastAttempt', 0);
$GLOBALS['serviceNotificationFail'] = false;
invoke($n, 'updateDisabledVehicleServices', $envelope);
check($n->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'Successful retry sets flag');

// Upgrade with a preexisting outage and a stale legacy sent flag:
$previousCalls = count($GLOBALS['serviceNotificationCalls']);
$GLOBALS['serviceNotificationFail'] = false;
$upgrade = new MySkoda();
$upgrade->InstanceID = 199;
$upgrade->Create();
$upgrade->properties['VIN'] = 'TMBJC7NY0N0000001';
$upgrade->properties['APIToken'] = 'test-token-only';
$upgrade->properties['NotificationInstanceID'] = 900;
$upgrade->properties['NotifyDisabledVehicleServices'] = true;
$upgrade->WriteAttributeString('RawData', json_encode($envelope, JSON_THROW_ON_ERROR));
$upgrade->WriteAttributeString('ConfigFingerprint', hash('sha256', $upgrade->properties['VIN'] . '|' . $upgrade->properties['APIToken']));
$upgrade->WriteAttributeBoolean('DisabledServicesNotificationSent', true);
$upgrade->WriteAttributeInteger('DisabledServicesNotificationLastAttempt', time());
$upgrade->ApplyChanges();
check($upgrade->ReadAttributeInteger('DisabledServicesAlertSchema') === 1, 'Upgrade performs schema migration');
check(count($GLOBALS['serviceNotificationCalls']) === $previousCalls + 1, 'Preexisting outage is notified once after upgrade');
check($upgrade->ReadAttributeBoolean('DisabledServicesNotificationSent'), 'New successful notification is latched');
check(str_contains($upgrade->ReadAttributeString('DisabledServicesAlertStatus'), 'accepted'), 'The automatic delivery decision is visible');
$upgrade->ApplyChanges();
check(count($GLOBALS['serviceNotificationCalls']) === $previousCalls + 1, 'Second ApplyChanges must not notify again');
$upForm = json_decode($upgrade->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$notificationPanel = array_values(array_filter($upForm['elements'], static fn (array $e): bool => ($e['caption'] ?? '') === 'Notifications'))[0];
$feedback = array_values(array_filter($notificationPanel['items'], static fn (array $e): bool => ($e['name'] ?? '') === 'DisabledServicesAlertFeedback'))[0];
check(str_contains($feedback['caption'], 'already sent'), 'Configuration shows previously sent notification status');

$translations = json_decode((string) file_get_contents(__DIR__ . '/../MySkoda/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
$notice = $translations['Skoda has disabled these vehicle services: %s. The affected information is not being updated; existing values may be outdated. A possible reason is an expired Car Connect remote access service.'] ?? '';
check(str_contains($notice, 'nicht aktualisiert'), 'German warning states values are not updated');
check(str_contains($notice, 'möglicher Grund'), 'German warning does not claim proven subscription expiry');

echo 'Skoda disabled services checks passed (' . $GLOBALS['checks'] . " total checks).\n";
