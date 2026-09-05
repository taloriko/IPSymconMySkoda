<?php

declare(strict_types=1);

/**
 * MySkoda 1.9 runtime/form additions.
 */
trait MySkodaCoreV19Trait
{
    public function RequestAction(string $Ident, mixed $Value): void
    {
        // HTML-SDK lifecycle refresh. This only re-sends the cached vehicle
        // state to the visualization and does not consume a MySkoda API call.
        if ($Ident === 'VisualizationRefresh') {
            $this->RefreshVisuals();
            return;
        }

        $this->requestActionCoreV18($Ident, $Value);
    }

    public function GetConfigurationForm(): string
    {
        $raw = $this->getConfigurationFormCoreV18();
        $form = json_decode($raw, true);
        if (!is_array($form)) {
            return $raw;
        }

        $connectionCaption = $this->connectionFeedbackCaption();
        $notificationCaption = $this->notificationTargetFeedbackCaption();
        $validVisualizationModules = $this->getVisualizationModuleIds();
        $titleSupported = function_exists('IPS_SetHiddenTitle');
        $titleCaption = $titleSupported
            ? $this->Translate('The Symcon tile title can be hidden while the instance name remains unchanged in the object tree.')
            : $this->Translate('Hiding the Symcon tile title requires Symcon 9.1 or newer. The instance name itself is never changed.');

        if (isset($form['elements']) && is_array($form['elements'])) {
            $this->prepareConfigurationElements(
                $form['elements'],
                $connectionCaption,
                $notificationCaption,
                $validVisualizationModules,
                $titleSupported,
                $titleCaption
            );
        }

        return json_encode($form, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function prepareConfigurationElements(
        array &$elements,
        string $connectionCaption,
        string $notificationCaption,
        array $validVisualizationModules,
        bool $titleSupported,
        string $titleCaption
    ): void {
        foreach ($elements as &$element) {
            if (!is_array($element)) {
                continue;
            }

            $name = (string) ($element['name'] ?? '');
            if ($name === 'ConnectionFeedback') {
                $element['caption'] = $connectionCaption;
            } elseif ($name === 'NotificationInstanceID') {
                $element['validModules'] = $validVisualizationModules;
            } elseif ($name === 'NotificationTargetFeedback') {
                $element['caption'] = $notificationCaption;
            } elseif ($name === 'HideVisualizationTitle') {
                $element['enabled'] = $titleSupported;
            } elseif ($name === 'TileTitleSupportFeedback') {
                $element['caption'] = $titleCaption;
            }

            if (isset($element['items']) && is_array($element['items'])) {
                $this->prepareConfigurationElements(
                    $element['items'],
                    $connectionCaption,
                    $notificationCaption,
                    $validVisualizationModules,
                    $titleSupported,
                    $titleCaption
                );
            }
        }
        unset($element);
    }
}
