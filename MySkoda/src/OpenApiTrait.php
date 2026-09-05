<?php

declare(strict_types=1);

trait MySkodaOpenApiTrait
{
    private function refreshOpenApi(bool $force): array
    {
        $cached = $this->ReadAttributeString('OpenApiOperations');
        $updated = $this->ReadAttributeInteger('OpenApiUpdatedAt');
        if (!$force && $cached !== '' && (time() - $updated) < 86400) {
            $ops = json_decode($cached, true);
            return is_array($ops) ? $ops : [];
        }

        $response = $this->request('GET', self::OPENAPI_URL, null, false);
        if (!$response['ok'] || !is_array($response['json'])) {
            $ops = json_decode($cached, true);
            return is_array($ops) ? $ops : [];
        }

        $ops = $this->extractOpenApiOperations($response['json']);
        $this->WriteAttributeString('OpenApiOperations', json_encode($ops, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->WriteAttributeInteger('OpenApiUpdatedAt', time());
        return $ops;
    }

    private function extractOpenApiOperations(array $spec): array
    {
        $operations = [];
        foreach (($spec['paths'] ?? []) as $path => $pathItem) {
            if (!is_array($pathItem)) {
                continue;
            }
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (!isset($pathItem[$method]) || !is_array($pathItem[$method])) {
                    continue;
                }
                $op = $pathItem[$method];
                $schema = null;
                foreach (($op['requestBody']['content'] ?? []) as $content) {
                    if (is_array($content) && isset($content['schema']) && is_array($content['schema'])) {
                        $schema = $this->resolveSchema($spec, $content['schema']);
                        break;
                    }
                }
                $operations[] = [
                    'operationId' => (string) ($op['operationId'] ?? ''),
                    'summary' => (string) ($op['summary'] ?? ''),
                    'method' => strtoupper($method),
                    'path' => (string) $path,
                    'requestSchema' => $schema
                ];
            }
        }
        return $operations;
    }

    private function resolveSchema(array $spec, array $schema, int $depth = 0): array
    {
        if ($depth > 12) {
            return $schema;
        }
        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $resolved = $this->resolveJsonPointer($spec, $schema['$ref']);
            if (is_array($resolved)) {
                return $this->resolveSchema($spec, $resolved, $depth + 1);
            }
        }
        if (isset($schema['allOf']) && is_array($schema['allOf'])) {
            $merged = [];
            foreach ($schema['allOf'] as $part) {
                if (is_array($part)) {
                    $merged = array_replace_recursive($merged, $this->resolveSchema($spec, $part, $depth + 1));
                }
            }
            $schema = array_replace_recursive($merged, $schema);
            unset($schema['allOf']);
        }
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                if (is_array($property)) {
                    $schema['properties'][$key] = $this->resolveSchema($spec, $property, $depth + 1);
                }
            }
        }
        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->resolveSchema($spec, $schema['items'], $depth + 1);
        }
        return $schema;
    }

    private function resolveJsonPointer(array $document, string $pointer): mixed
    {
        if (!str_starts_with($pointer, '#/')) {
            return null;
        }
        $current = $document;
        foreach (explode('/', substr($pointer, 2)) as $part) {
            $part = str_replace(['~1', '~0'], ['/', '~'], $part);
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }
        return $current;
    }

    private function findOperation(array $operations, string $purpose): ?array
    {
        $ids = [
            'limit' => ['setChargingLimit', 'setChargeLimit', 'updateChargingLimit', 'setTargetStateOfCharge', 'setTargetStateOfChargeInPercent'],
            'mode' => ['changeChargeMode', 'setChargeMode', 'changeChargingMode', 'setChargingMode'],
            'profile' => ['updateChargingProfile', 'setChargingProfile', 'updateChargeProfile']
        ];
        foreach ($ids[$purpose] ?? [] as $wanted) {
            foreach ($operations as $operation) {
                if (strcasecmp((string) ($operation['operationId'] ?? ''), $wanted) === 0) {
                    return $operation;
                }
            }
        }

        $best = null;
        $scoreBest = 0;
        foreach ($operations as $operation) {
            if (($operation['method'] ?? '') === 'GET') {
                continue;
            }
            $hay = strtolower(($operation['operationId'] ?? '') . ' ' . ($operation['summary'] ?? '') . ' ' . ($operation['path'] ?? ''));
            $score = 0;
            if (str_contains($hay, 'charg')) {
                $score += 2;
            }
            if ($purpose === 'limit') {
                $score += str_contains($hay, 'limit') ? 6 : 0;
                $score += (str_contains($hay, 'target') && str_contains($hay, 'state')) ? 4 : 0;
                $score += str_contains($hay, 'soc') ? 3 : 0;
            } elseif ($purpose === 'mode') {
                $score += str_contains($hay, 'mode') ? 7 : 0;
            } elseif ($purpose === 'profile') {
                $score += str_contains($hay, 'profile') ? 7 : 0;
                $score += (str_contains($hay, 'update') || str_contains($hay, 'set')) ? 2 : 0;
            }
            if ($score > $scoreBest) {
                $scoreBest = $score;
                $best = $operation;
            }
        }
        return $scoreBest >= 6 ? $best : null;
    }

    private function buildScalarPayload(array $operation, string $purpose, int|string $value): array
    {
        $schema = is_array($operation['requestSchema'] ?? null) ? $operation['requestSchema'] : [];
        if ($purpose === 'limit') {
            $path = $this->findSchemaPropertyPath($schema, ['targetStateOfChargeInPercent', 'targetSoc', 'chargingLimit', 'limit'], ['target']);
            return $this->setNestedValue($path ?? ['targetStateOfChargeInPercent'], (int) $value);
        }
        $path = $this->findSchemaPropertyPath($schema, ['preferredChargeMode', 'chargeMode', 'chargingMode', 'mode'], ['mode']);
        return $this->setNestedValue($path ?? ['preferredChargeMode'], (string) $value);
    }

    private function findSchemaPropertyPath(array $schema, array $exactNames, array $containsWords, array $prefix = []): ?array
    {
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return null;
        }
        foreach ($properties as $name => $property) {
            foreach ($exactNames as $exact) {
                if (strcasecmp((string) $name, (string) $exact) === 0 && !(is_array($property) && isset($property['properties']))) {
                    return array_merge($prefix, [(string) $name]);
                }
            }
        }
        foreach ($properties as $name => $property) {
            if (is_array($property) && isset($property['properties'])) {
                $found = $this->findSchemaPropertyPath($property, $exactNames, $containsWords, array_merge($prefix, [(string) $name]));
                if ($found !== null) {
                    return $found;
                }
            }
        }
        foreach ($properties as $name => $property) {
            if (is_array($property) && isset($property['properties'])) {
                continue;
            }
            $low = strtolower((string) $name);
            $matches = true;
            foreach ($containsWords as $word) {
                if (!str_contains($low, strtolower((string) $word))) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return array_merge($prefix, [(string) $name]);
            }
        }
        return null;
    }

    private function setNestedValue(array $path, int|string $value): array
    {
        $result = [];
        $ref =& $result;
        $last = array_pop($path);
        foreach ($path as $part) {
            $ref[$part] = [];
            $ref =& $ref[$part];
        }
        $ref[$last] = $value;
        return $result;
    }

    private function replacePathParameters(string $path, ?int $profileId = null): string
    {
        $path = str_replace('{vin}', rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN')))), $path);
        if (preg_match_all('/\{([^}]+)\}/', $path, $matches)) {
            foreach ($matches[1] as $parameter) {
                $low = strtolower((string) $parameter);
                if ($profileId !== null && ($low === 'id' || str_contains($low, 'profile'))) {
                    $path = str_replace('{' . $parameter . '}', rawurlencode((string) $profileId), $path);
                }
            }
        }
        return $path;
    }

    private function buildProfilePayload(array $operation, array $profile): array
    {
        $schema = is_array($operation['requestSchema'] ?? null) ? $operation['requestSchema'] : [];
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return $profile;
        }
        foreach (['profile', 'chargingProfile'] as $wrapper) {
            if (isset($properties[$wrapper]) && is_array($properties[$wrapper])) {
                return [$wrapper => $this->filterPayloadToSchema($profile, $properties[$wrapper])];
            }
        }
        $filtered = $this->filterPayloadToSchema($profile, $schema);
        return $filtered !== [] ? $filtered : $profile;
    }

    private function filterPayloadToSchema(array $payload, array $schema): array
    {
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return $payload;
        }
        $result = [];
        foreach ($properties as $key => $propertySchema) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if (is_array($value) && is_array($propertySchema)) {
                if (array_is_list($value) && isset($propertySchema['items']) && is_array($propertySchema['items'])) {
                    $result[$key] = array_map(fn ($item) => is_array($item) ? $this->filterPayloadToSchema($item, $propertySchema['items']) : $item, $value);
                } else {
                    $result[$key] = $this->filterPayloadToSchema($value, $propertySchema);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

}
