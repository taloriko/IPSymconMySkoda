<?php

declare(strict_types=1);

/**
 * Symcon >= 8.x presentation templates used by MySkoda.
 *
 * These are native presentation templates, not Legacy variable profiles.
 */
trait MySkodaPresentationTrait
{
    private function booleanYesNoPresentation(bool $goodValue): array
    {
        $green = 0x22C55E;
        $orange = 0xF59E0B;
        $name = $goodValue ? 'MySkoda.Status.GoodTrue' : 'MySkoda.Status.GoodFalse';
        $values = $this->booleanValueTemplateValues(
            $goodValue ? $orange : $green,
            $goodValue ? $green : $orange
        );
        $template = $this->ensurePresentationTemplate($name, VARIABLE_PRESENTATION_VALUE_PRESENTATION, $values);

        if ($template !== '') {
            return [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'TEMPLATE' => $template
            ];
        }

        return array_merge(
            ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION],
            $values
        );
    }

    private function booleanActivePresentation(): array
    {
        $green = 0x22C55E;

        // With an action, Charging/Climate are real controls and therefore use
        // the native Switch presentation. Without an action they fall back to a
        // passive Value Presentation, because Switch also requires an action.
        if ($this->ReadPropertyBoolean('EnableRemote')) {
            $values = [
                'USE_ICON_FALSE' => false,
                'ICON_TRUE' => '',
                'ICON_FALSE' => '',
                'GLOW_COLOR' => $green,
                'GLOW_INTENSITY' => 35,
                'USAGE_TYPE' => 2
            ];
            $template = $this->ensurePresentationTemplate('MySkoda.Control.Switch', VARIABLE_PRESENTATION_SWITCH, $values);
            if ($template !== '') {
                return [
                    'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
                    'TEMPLATE' => $template
                ];
            }
            return array_merge(['PRESENTATION' => VARIABLE_PRESENTATION_SWITCH], $values);
        }

        $values = $this->booleanValueTemplateValues($green, $green);
        $template = $this->ensurePresentationTemplate('MySkoda.Status.Normal', VARIABLE_PRESENTATION_VALUE_PRESENTATION, $values);
        if ($template !== '') {
            return [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'TEMPLATE' => $template
            ];
        }
        return array_merge(['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION], $values);
    }

    private function booleanValueTemplateValues(int $falseColor, int $trueColor): array
    {
        return [
            'ICON' => '',
            'COLOR' => -1,
            'OPTIONS' => json_encode([
                [
                    'Value' => false,
                    'Caption' => $this->Translate('No'),
                    'IconActive' => false,
                    'IconValue' => '',
                    'ColorActive' => true,
                    'ColorValue' => $falseColor
                ],
                [
                    'Value' => true,
                    'Caption' => $this->Translate('Yes'),
                    'IconActive' => false,
                    'IconValue' => '',
                    'ColorActive' => true,
                    'ColorValue' => $trueColor
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function ensurePresentationTemplate(string $name, string $presentation, array $values): string
    {
        if (!function_exists('IPS_GetTemplateListByPresentation')
            || !function_exists('IPS_GetTemplate')
            || !function_exists('IPS_CreateTemplate')
            || !function_exists('IPS_SetTemplateName')
            || !function_exists('IPS_SetTemplateValues')) {
            return '';
        }

        foreach (IPS_GetTemplateListByPresentation($presentation) as $templateId) {
            $template = IPS_GetTemplate($templateId);
            $displayName = (string) ($template['DisplayName'] ?? '');
            if ($displayName !== $name) {
                continue;
            }
            IPS_SetTemplateValues($templateId, $values);
            return $templateId;
        }

        $templateId = IPS_CreateTemplate($presentation);
        IPS_SetTemplateName($templateId, $name);
        IPS_SetTemplateValues($templateId, $values);
        return $templateId;
    }
}
