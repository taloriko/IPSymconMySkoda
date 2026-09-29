<?php

declare(strict_types=1);

// Isolated host double: no Symcon installation, credentials or network required.
if (class_exists('IPSModuleStrict', false)) {
    throw new RuntimeException('Run this test with standalone PHP, not inside Symcon.');
}

$root = dirname(__DIR__);
foreach (['BOOLEAN' => 0, 'INTEGER' => 1, 'FLOAT' => 2, 'STRING' => 3] as $name => $value) {
    define('VARIABLETYPE_' . $name, $value);
}
define('KL_WARNING', 1);
define('KL_ERROR', 2);

foreach (glob($root . '/MySkoda/src/*.php') as $file) {
    preg_match_all('/\bVARIABLE_(?:PRESENTATION|TEMPLATE)_[A-Z_]+\b/', file_get_contents($file), $matches);
    foreach ($matches[0] as $constant) {
        if (!defined($constant)) {
            define($constant, $constant);
        }
    }
}

$GLOBALS['objects'] = [];
$GLOBALS['nextId'] = 1000;
$GLOBALS['metadataWrites'] = 0;
$GLOBALS['checks'] = 0;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$GLOBALS['checks'];
}

function IPS_GetObjectIDByIdent(string $ident, int $parent): int|false
{
    foreach ($GLOBALS['objects'] as $id => $object) {
        if ($object['ParentID'] === $parent && $object['ObjectIdent'] === $ident) {
            return $id;
        }
    }
    return false;
}

function IPS_VariableExists(int $id): bool
{
    return ($GLOBALS['objects'][$id]['ObjectType'] ?? -1) === 2;
}

function IPS_GetVariable(int $id): array
{
    return $GLOBALS['objects'][$id];
}

function IPS_GetObject(int $id): array
{
    return $GLOBALS['objects'][$id];
}

function IPS_GetMedia(int $id): array
{
    return $GLOBALS['objects'][$id];
}

function IPS_SetIcon(int $id, string $icon): void
{
    ++$GLOBALS['metadataWrites'];
    $GLOBALS['objects'][$id]['ObjectIcon'] = $icon;
}

function IPS_SetName(int $id, string $name): void
{
    ++$GLOBALS['metadataWrites'];
    $GLOBALS['objects'][$id]['ObjectName'] = $name;
}

function IPS_SetPosition(int $id, int $position): void
{
    ++$GLOBALS['metadataWrites'];
    $GLOBALS['objects'][$id]['ObjectPosition'] = $position;
}

function IPS_DeleteVariable(int $id): void
{
    throw new RuntimeException('Variable deletion is forbidden in this test.');
}

function GetValue(int $id): mixed
{
    return $GLOBALS['objects'][$id]['Value'];
}

class MySkodaTestHost
{
    public int $InstanceID = 10;
    public array $properties = [];
    public array $attributes = [];
    public array $logs = [];
    public int $registrations = 0;
    public int $status = 0;

    public function Create(): void {}
    public function ApplyChanges(): void {}
    public function RegisterPropertyString(string $key, string $value): void { $this->properties[$key] ??= $value; }
    public function RegisterPropertyInteger(string $key, int $value): void { $this->properties[$key] ??= $value; }
    public function RegisterPropertyBoolean(string $key, bool $value): void { $this->properties[$key] ??= $value; }
    public function RegisterAttributeString(string $key, string $value): void { $this->attributes[$key] ??= $value; }
    public function RegisterAttributeInteger(string $key, int $value): void { $this->attributes[$key] ??= $value; }
    public function RegisterAttributeBoolean(string $key, bool $value): void { $this->attributes[$key] ??= $value; }
    public function ReadPropertyString(string $key): string { return $this->properties[$key]; }
    public function ReadPropertyInteger(string $key): int { return $this->properties[$key]; }
    public function ReadPropertyBoolean(string $key): bool { return $this->properties[$key]; }
    public function ReadAttributeString(string $key): string { return $this->attributes[$key]; }
    public function ReadAttributeInteger(string $key): int { return $this->attributes[$key]; }
    public function ReadAttributeBoolean(string $key): bool { return $this->attributes[$key]; }
    public function WriteAttributeString(string $key, string $value): void { $this->attributes[$key] = $value; }
    public function WriteAttributeInteger(string $key, int $value): void { $this->attributes[$key] = $value; }
    public function WriteAttributeBoolean(string $key, bool $value): void { $this->attributes[$key] = $value; }
    public function RegisterTimer(string $ident, int $interval, string $script): void {}
    public function SetTimerInterval(string $ident, int $interval): void {}
    public function SetVisualizationType(int $type): void {}
    public function SetSummary(string $summary): void {}
    public function SetStatus(int $status): void { $this->status = $status; }
    public function UpdateFormField(string $name, string $field, mixed $value): void {}
    public function Translate(string $text): string { return $text; }
    public function SendDebug(string $label, mixed $data, int $format): void {}
    public function LogMessage(string $message, int $severity): void { $this->logs[] = [$message, $severity]; }
    public function GetIDForIdent(string $ident): int|false { return IPS_GetObjectIDByIdent($ident, $this->InstanceID); }
    public function GetValue(string $ident): mixed { return GetValue($this->GetIDForIdent($ident)); }

    public function SetValue(string $ident, mixed $value): void
    {
        $id = $this->GetIDForIdent($ident);
        check($id !== false && IPS_VariableExists($id), 'Value target must be a variable: ' . $ident);
        $valid = match ($GLOBALS['objects'][$id]['VariableType']) {
            0 => is_bool($value),
            1 => is_int($value),
            2 => is_float($value),
            3 => is_string($value)
        };
        check($valid, 'Unexpected value type: ' . $ident);
        $GLOBALS['objects'][$id]['Value'] = $value;
    }

    public function MaintainAction(string $ident, bool $enabled): void
    {
        $id = $this->GetIDForIdent($ident);
        if ($id !== false) {
            $GLOBALS['objects'][$id]['Action'] = $enabled;
        }
    }

    private function register(string $ident, string $name, array $presentation, int $position, int $type): void
    {
        check($this->GetIDForIdent($ident) === false, 'Existing variable re-registered: ' . $ident);
        ++$this->registrations;
        $GLOBALS['objects'][$GLOBALS['nextId']++] = [
            'ParentID' => $this->InstanceID,
            'ObjectIdent' => $ident,
            'ObjectType' => 2,
            'ObjectName' => $name,
            'ObjectPosition' => $position,
            'ObjectIcon' => '',
            'VariableType' => $type,
            'Presentation' => $presentation,
            'Value' => match ($type) {
                0 => false,
                1 => 0,
                2 => 0.0,
                3 => ''
            }
        ];
    }

    public function RegisterVariableBoolean(string $ident, string $name, array $presentation, int $position): void
    {
        $this->register($ident, $name, $presentation, $position, 0);
    }

    public function RegisterVariableInteger(string $ident, string $name, array $presentation, int $position): void
    {
        $this->register($ident, $name, $presentation, $position, 1);
    }

    public function RegisterVariableFloat(string $ident, string $name, array $presentation, int $position): void
    {
        $this->register($ident, $name, $presentation, $position, 2);
    }

    public function RegisterVariableString(string $ident, string $name, array $presentation, int $position): void
    {
        $this->register($ident, $name, $presentation, $position, 3);
    }
}

class_alias(MySkodaTestHost::class, 'IPSModuleStrict');
require $root . '/MySkoda/module.php';

function invoke(MySkoda $module, string $method, mixed ...$args): mixed
{
    return (new ReflectionMethod(MySkoda::class, $method))->invoke($module, ...$args);
}

// Base lifecycle: without vehicle data, only module-owned status variables exist.
$m = new MySkoda();
$m->Create();
$m->ApplyChanges();

check($m->status === 201, 'An empty configuration must be reported as unconfigured.');
check($m->registrations === 2, 'Without API data only ApiKeyWarning and LastUpdate are created.');
check($m->GetIDForIdent('ApiKeyWarning') !== false, 'ApiKeyWarning exists.');
check($m->GetIDForIdent('LastUpdate') !== false, 'LastUpdate exists.');
check($m->GetIDForIdent('StateOfCharge') === false, 'EV variables must not be created before the API returns them.');
check(json_decode($m->GetConfigurationForm(), true) !== null, 'Configuration form must remain valid JSON.');

// Optional diagnostic/VIN groups are still explicitly user controlled.
$m->properties['ShowDetails'] = true;
$m->properties['CreateVINVariables'] = true;
$m->properties['VIN'] = 'TMBJC7NY0N0000001'; // Synthetic syntax-valid fixture, no real vehicle.
$m->ApplyChanges();

check($m->registrations === 23, 'Expected base, diagnostic and local VIN variables only.');
check(count($GLOBALS['objects']) === 23, 'No duplicate identifiers during repeated setup.');

// User-owned metadata must survive repeated ApplyChanges.
foreach ($GLOBALS['objects'] as &$object) {
    if ($object['ParentID'] !== $m->InstanceID) {
        continue;
    }
    $object['ObjectName'] = 'User ' . $object['ObjectIdent'];
    $object['ObjectPosition'] += 5000;
    $object['ObjectIcon'] = '';
    $object['Presentation'] = ['custom' => true];
}
unset($object);

$before = array_filter(
    $GLOBALS['objects'],
    static fn (array $object): bool => $object['ParentID'] === $m->InstanceID
);
$writes = $GLOBALS['metadataWrites'];
$m->ApplyChanges();
$m->ApplyChanges();

check($m->registrations === 23, 'Repeated ApplyChanges must not register existing variables.');
check($GLOBALS['metadataWrites'] === $writes, 'Existing metadata must not be written.');
foreach ($before as $id => $old) {
    foreach (['ObjectName', 'ObjectPosition', 'ObjectIcon', 'Presentation'] as $key) {
        check($GLOBALS['objects'][$id][$key] === $old[$key], 'Metadata changed: ' . $old['ObjectIdent'] . '/' . $key);
    }
}

// Creation options never delete variables that already exist.
$m->properties['ShowDetails'] = false;
$m->properties['CreateVINVariables'] = false;
$m->properties['EnableRemote'] = true;
$m->ApplyChanges();
check(count(array_filter($GLOBALS['objects'], static fn (array $object): bool => $object['ParentID'] === $m->InstanceID)) === 23, 'Disabling creation options must not delete objects.');

// EV fixture: every delivered known path creates its variable, and operations enable only matching actions.
$vehicle = [
    'vin' => $m->properties['VIN'],
    'airConditioning' => [
        'state' => 'OFF',
        'targetTemperature' => ['value' => 20.0, 'unit' => 'CELSIUS']
    ],
    'charging' => [
        'settings' => [
            'targetStateOfChargeInPercent' => 80,
            'preferredChargeMode' => 'MANUAL',
            'availableChargeModes' => ['MANUAL']
        ],
        'status' => [
            'state' => 'CHARGING',
            'chargePowerInKw' => 4.2,
            'battery' => [
                'stateOfChargeInPercent' => 71,
                'remainingCruisingRangeInMeters' => 321000
            ]
        ]
    ],
    'status' => [
        'overall' => [
            'doorsLocked' => 'YES',
            'locked' => 'NO',
            'reliableLockStatus' => 'UNKNOWN',
            'doors' => 'CLOSED',
            'windows' => 'OPEN'
        ]
    ],
    'odometer' => ['mileageInKm' => 12345],
    'operations' => [
        ['name' => 'startCharging'],
        ['name' => 'stopCharging'],
        ['name' => 'setChargingLimit'],
        ['name' => 'setChargeMode'],
        ['name' => 'startAirConditioning'],
        ['name' => 'stopAirConditioning']
    ]
];

invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);

foreach ([
    'VIN', 'ClimateState', 'Climate', 'TargetTemperature', 'TargetTemperatureUnit',
    'TargetSOC', 'ChargeMode', 'APIAvailableChargeModes', 'ChargingState', 'Charging',
    'ChargePower', 'StateOfCharge', 'Range', 'DoorsLocked', 'Locked',
    'ReliableLockStatus', 'DoorsOpen', 'WindowsOpen', 'Mileage', 'APIRemoteOperations'
] as $ident) {
    check($m->GetIDForIdent($ident) !== false, 'Delivered EV field must create variable: ' . $ident);
}

check($m->GetValue('DoorsLocked') === 'YES' && $m->GetValue('Locked') === 'NO', 'Lock fields remain independent strings.');
check($m->GetValue('WindowsOpen') === 'OPEN' && $m->GetValue('ReliableLockStatus') === 'UNKNOWN', 'Status strings are preserved.');
check($m->GetValue('ChargePower') === 4200.0 && $m->GetValue('Range') === 321, 'Power/range conversions.');
check($m->GetValue('Charging') === true, 'Charging boolean follows confirmed active charging state.');
check($m->GetValue('TargetTemperature') === 20.0, 'API target temperature is stored.');
check($GLOBALS['objects'][$m->GetIDForIdent('Charging')]['Action'] === true, 'Charging action follows reported operations.');
check($GLOBALS['objects'][$m->GetIDForIdent('TargetSOC')]['Action'] === true, 'Charging limit action follows reported operations.');
check($GLOBALS['objects'][$m->GetIDForIdent('Climate')]['Action'] === true, 'Climate action follows reported operations.');
check($GLOBALS['objects'][$m->GetIDForIdent('TargetTemperature')]['Action'] === true, 'Temperature action requires climate support.');

// A known path that was not delivered must not exist yet.
check($m->GetIDForIdent('PlugConnectionState') === false, 'Missing plug connection state must not be pre-created.');
check($m->GetIDForIdent('PlugLockState') === false, 'Missing plug lock state must not be pre-created.');

// New fields in a later response are created immediately.
$vehicle['charging']['status']['plugConnectionState'] = 'DISCONNECTED';
$vehicle['charging']['status']['plugLockState'] = 'UNLOCKED';
invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);
check($m->GetValue('PlugConnectionState') === 'DISCONNECTED', 'Later plug connection field is created and stored.');
check($m->GetValue('PlugLockState') === 'UNLOCKED', 'Later plug lock field is created and stored.');

// Missing fields in a later partial response retain their last value.
unset($vehicle['charging']['status']['state'], $vehicle['charging']['status']['plugConnectionState'], $vehicle['charging']['status']['plugLockState']);
invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);
check($m->GetValue('Charging') === true, 'Missing charging state must not overwrite the last confirmed value.');
check($m->GetValue('ChargingState') === 'CHARGING', 'Missing charging state must not overwrite the last raw state.');
check($m->GetValue('PlugConnectionState') === 'DISCONNECTED', 'Missing later plug value retains its last value.');
check($m->GetValue('PlugLockState') === 'UNLOCKED', 'Missing later plug lock retains its last value.');

// Unknown fields are created generically with a stable API_* ident and native JSON scalar type.
$vehicle['diagnostics'] = [
    'oilTemperatureInC' => 92.5,
    'serviceDue' => false,
    'counter' => 0,
    'emptyText' => '',
    'notAvailable' => null
];
$beforeUnknown = $m->registrations;
invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);
check($m->GetIDForIdent('API_Diagnostics_OilTemperatureInC') !== false, 'Unknown float field receives a generic variable.');
check($m->GetIDForIdent('API_Diagnostics_ServiceDue') !== false, 'Unknown boolean field receives a generic variable.');
check($m->GetIDForIdent('API_Diagnostics_Counter') !== false, 'Unknown zero value is still valid data.');
check($m->GetIDForIdent('API_Diagnostics_EmptyText') === false, 'Empty strings are treated as not populated.');
check($m->GetIDForIdent('API_Diagnostics_NotAvailable') === false, 'Null values are treated as not populated.');
check($m->GetValue('API_Diagnostics_OilTemperatureInC') === 92.5, 'Unknown float keeps its native value.');
check($m->GetValue('API_Diagnostics_ServiceDue') === false, 'Unknown false keeps its native value.');
check($m->GetValue('API_Diagnostics_Counter') === 0, 'Unknown zero keeps its native value.');
$afterUnknown = $m->registrations;
invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);
check($m->registrations === $afterUnknown, 'Repeated unknown fields must reuse their stable identifiers.');
check($afterUnknown > $beforeUnknown, 'Unknown fields must add variables once.');

// Dynamic data updates values but never repairs user metadata.
$mileageId = $m->GetIDForIdent('Mileage');
$GLOBALS['objects'][$mileageId]['ObjectName'] = 'Mein Kilometerstand';
$GLOBALS['objects'][$mileageId]['ObjectPosition'] = 7777;
$GLOBALS['objects'][$mileageId]['Presentation'] = ['custom' => 'mileage'];
$savedMileage = $GLOBALS['objects'][$mileageId];
$vehicle['odometer']['mileageInKm'] = 12346;
invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);
check($m->GetValue('Mileage') === 12346, 'Delivered values continue to update.');
foreach (['ObjectName', 'ObjectPosition', 'Presentation'] as $key) {
    check($GLOBALS['objects'][$mileageId][$key] === $savedMileage[$key], 'Dynamic update changed user metadata: ' . $key);
}

// Local target temperature selection remains until an active climate state is returned.
$m->RequestAction('TargetTemperature', 23.0);
check($m->GetValue('TargetTemperature') === 23.0, 'Local target temperature can be selected while climate is off.');
$vehicle['airConditioning']['targetTemperature']['value'] = 20.0;
invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);
check($m->GetValue('TargetTemperature') === 23.0, 'Local temperature survives polling while climate is off.');
$vehicle['airConditioning']['state'] = 'ON';
invoke($m, 'syncDynamicVehicleVariables', $vehicle, ['vehicle' => $vehicle]);
check($m->GetValue('TargetTemperature') === 20.0, 'Active climate restores API authority immediately.');
check(!$m->ReadAttributeBoolean('TargetTemperatureOverride'), 'External climate start clears the local override.');

// Command helper regression checks remain intact.
$m->SetValue('TargetSOC', 80);
$ok = invoke($m, 'executeOptimisticCommand', 'TargetSOC', 90, 'Charging limit', function () use ($m): bool {
    check($m->GetValue('TargetSOC') === 90, 'Optimistic value during request.');
    return true;
});
check($ok && $m->GetValue('TargetSOC') === 90, 'Accepted command keeps desired value.');

$ok = invoke($m, 'executeOptimisticCommand', 'TargetSOC', 100, 'Charging limit', function () use ($m): bool {
    $m->WriteAttributeString('LastError', 'fixture rejection');
    return false;
});
check(!$ok && $m->GetValue('TargetSOC') === 90, 'Rejected command rolls back.');

$ops = [[
    'operationId' => 'setChargingLimit',
    'method' => 'PUT',
    'path' => '/api/v1/vehicles/{vin}/charging/limit',
    'requestSchema' => ['properties' => ['targetStateOfChargeInPercent' => ['type' => 'integer']]]
]];
$m->WriteAttributeString('OpenApiOperations', json_encode($ops, JSON_THROW_ON_ERROR));
$m->WriteAttributeInteger('OpenApiUpdatedAt', time());
check(invoke($m, 'refreshOpenApi', false) === $ops, 'OpenAPI cache is still usable for commands.');
$operation = invoke($m, 'findOperation', $ops, 'limit');
check(invoke($m, 'buildScalarPayload', $operation, 'limit', 80) === ['targetStateOfChargeInPercent' => 80], 'Charging payload construction.');

// Existing wrong-type/non-variable objects are never deleted or rewritten.
$locked = $m->GetIDForIdent('Locked');
$savedLocked = $GLOBALS['objects'][$locked];
$definition = invoke($m, 'variable', 'Locked', 'Replacement', VARIABLETYPE_BOOLEAN, 1, [], true);
$logCount = count($m->logs);
invoke($m, 'registerVariableOnce', $definition);
check($GLOBALS['objects'][$locked] === $savedLocked, 'Wrong-type existing variables must not be deleted or modified.');
check(count($m->logs) === $logCount + 1, 'A type collision must be reported.');

$GLOBALS['objects'][$locked]['ObjectType'] = 0;
$savedLocked = $GLOBALS['objects'][$locked];
$logCount = count($m->logs);
invoke($m, 'registerVariableOnce', $definition);
check($GLOBALS['objects'][$locked] === $savedLocked, 'Non-variable ident collision must not be modified.');
check(count($m->logs) === $logCount + 1, 'An object collision must be reported.');
$GLOBALS['objects'][$locked]['ObjectType'] = 2;

// Raw response and working data stay separate.
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
$m->WriteAttributeString('LastVehicleResponseRaw', "{\n  \"detail\": \"fixture error\"\n}\n");
$workingData = $m->ReadAttributeString('RawData');
check($m->GetLastVehicleResponseRaw() === "{\n  \"detail\": \"fixture error\"\n}\n", 'Raw response must remain byte-exact.');
check($m->ReadAttributeString('RawData') === $workingData, 'Reading an error response must not replace valid cached data.');

// Combustion fixture based on a Superb response: no EV variables are created.
$c = new MySkoda();
$c->InstanceID = 20;
$c->Create();
$c->ApplyChanges();
check($c->registrations === 2, 'Fresh combustion instance starts without vehicle-specific variables.');

$combustion = [
    'auxiliaryHeating' => [
        'state' => 'OFF',
        'carCapturedTimestamp' => '2026-09-29T03:40:47Z',
        'durationInSeconds' => 2400
    ],
    'fuelStatus' => [
        'carCapturedTimestamp' => '2026-09-29T06:24:44Z',
        'carType' => 'GASOLINE',
        'primaryEngineRange' => [
            'currentFuelLevelInPercent' => 64,
            'currentSoCInPercent' => 64,
            'engineType' => 'GASOLINE',
            'remainingRangeInKm' => 430
        ],
        'totalRangeInKm' => 430
    ],
    'licensePlate' => 'TEST-XX 1',
    'name' => 'Superb',
    'odometer' => [
        'mileageInKm' => 37953,
        'carCapturedTimestamp' => '2026-09-29T06:24:44Z'
    ],
    'operations' => [
        ['name' => 'startAuxiliaryHeating'],
        ['name' => 'stopAuxiliaryHeating']
    ],
    'parkingPosition' => [
        'state' => 'PARKED',
        'formattedAddress' => 'Test address',
        'gpsCoordinates' => [
            'latitude' => 48.123456,
            'longitude' => 9.123456
        ]
    ],
    'renderUrl' => 'https://iprenders.blob.core.windows.net/test/fixture.png',
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
        ],
        'carCapturedTimestamp' => '2026-09-29T06:24:44Z'
    ],
    'vin' => 'TMBABCD12E1234567'
];

invoke($c, 'syncDynamicVehicleVariables', $combustion, ['vehicle' => $combustion]);

check($c->GetIDForIdent('Charging') === false, 'Combustion vehicle must not receive a charging action variable.');
check($c->GetIDForIdent('StateOfCharge') === false, 'Combustion vehicle must not receive EV battery state of charge.');
check($c->GetIDForIdent('TargetSOC') === false, 'Combustion vehicle must not receive a charging limit.');
check($c->GetValue('APICarType') === 'GASOLINE', 'Combustion type is stored.');
check($c->GetValue('APIPrimaryEngineType') === 'GASOLINE', 'Primary engine type is stored.');
check($c->GetValue('FuelLevel') === 64, 'Fuel level receives the known presentation variable.');
check($c->GetValue('PrimaryEngineRange') === 430, 'Primary engine range is stored in km.');
check($c->GetValue('TotalRange') === 430, 'Total range is stored in km.');
check($c->GetValue('AuxiliaryHeatingDuration') === 2400, 'Auxiliary heating duration is stored in seconds.');
check($c->GetValue('APIAuxiliaryHeatingState') === 'OFF', 'Auxiliary heating state is stored.');
check($c->GetValue('Mileage') === 37953, 'Combustion odometer is stored.');
check($c->GetValue('API_FuelStatus_PrimaryEngineRange_CurrentSoCInPercent') === 64, 'Unknown combustion field is retained generically.');
check($c->GetValue('API_AuxiliaryHeating_CarCapturedTimestamp') === '2026-09-29T03:40:47Z', 'Unknown timestamp is retained as delivered.');
check(str_contains($c->GetValue('APIRemoteOperations'), 'startAuxiliaryHeating'), 'Reported remote operations are stored.');
check(in_array('startAuxiliaryHeating', json_decode($c->ReadAttributeString('AvailableRemoteOperations'), true), true), 'Operation cache follows the response.');

// A short later response must not erase the previously known vehicle state.
$combustionCount = $c->registrations;
invoke($c, 'syncDynamicVehicleVariables', ['name' => 'Superb', 'odometer' => ['mileageInKm' => 37954]], ['vehicle' => ['name' => 'Superb', 'odometer' => ['mileageInKm' => 37954]]]);
check($c->GetValue('FuelLevel') === 64, 'Missing fuel data keeps its last value.');
check($c->GetValue('TotalRange') === 430, 'Missing total range keeps its last value.');
check($c->GetValue('Mileage') === 37954, 'Fields that are present continue to update.');
check($c->registrations === $combustionCount, 'A partial response does not create duplicates.');

// A brand-new future field is added exactly once without a code update.
$extended = $combustion;
$extended['serviceData'] = ['oilTemperatureInC' => 92.5];
invoke($c, 'syncDynamicVehicleVariables', $extended, ['vehicle' => $extended]);
check($c->GetValue('API_ServiceData_OilTemperatureInC') === 92.5, 'A future API field is exposed automatically.');
$extendedCount = $c->registrations;
invoke($c, 'syncDynamicVehicleVariables', $extended, ['vehicle' => $extended]);
check($c->registrations === $extendedCount, 'A future API field keeps the same identifier on later updates.');

$paths = json_decode($c->ReadAttributeString('DynamicVariablePaths'), true);
check(($paths['API_ServiceData_OilTemperatureInC'] ?? '') === 'serviceData.oilTemperatureInC', 'Generic ident/path mapping is persisted.');

echo 'Runtime invariants passed (' . $GLOBALS['checks'] . " checks).\n";
