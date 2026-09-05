<?php

declare(strict_types=1);

require_once __DIR__ . '/src/CoreTrait.php';
require_once __DIR__ . '/src/VariablesTrait.php';
require_once __DIR__ . '/src/PresentationTrait.php';
require_once __DIR__ . '/src/ApiTrait.php';
require_once __DIR__ . '/src/OpenApiTrait.php';
require_once __DIR__ . '/src/VisualizationTrait.php';
require_once __DIR__ . '/src/VisualizationV15Trait.php';
require_once __DIR__ . '/src/NotificationTrait.php';
require_once __DIR__ . '/src/HelpersTrait.php';

final class MySkoda extends IPSModuleStrict
{
    use MySkodaCoreTrait;
    use MySkodaVariablesTrait, MySkodaPresentationTrait {
        MySkodaPresentationTrait::booleanYesNoPresentation insteadof MySkodaVariablesTrait;
        MySkodaPresentationTrait::booleanActivePresentation insteadof MySkodaVariablesTrait;
    }
    use MySkodaApiTrait;
    use MySkodaOpenApiTrait;
    use MySkodaVisualizationTrait, MySkodaVisualizationV15Trait {
        MySkodaVisualizationV15Trait::buildVehicleTileHtml insteadof MySkodaVisualizationTrait;
        MySkodaVisualizationV15Trait::buildVehicleSvg insteadof MySkodaVisualizationTrait;
        MySkodaVisualizationV15Trait::extractRemainingChargeMinutes insteadof MySkodaVisualizationTrait;
    }
    use MySkodaNotificationTrait;
    use MySkodaHelpersTrait;

    private const API_ROOT = 'https://public.api.connect.skoda-auto.cz';
    private const OPENAPI_URL = self::API_ROOT . '/v3/api-docs';
    private const USER_AGENT = 'IP-Symcon-MySkoda/1.5';
    private const QUOTA_RESERVE = 2;
}
