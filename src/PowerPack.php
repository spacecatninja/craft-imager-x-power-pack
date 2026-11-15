<?php
/**
 * Imager X Power Pack
 *
 * @link      https://www.spacecat.ninja/
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace SpaceCatNinja\ImagerXPowerPack;

use craft\base\Plugin;

use SpaceCatNinja\ImagerXPowerPack\Services\PowerPackService;
use SpaceCatNinja\ImagerXPowerPack\Twig\PowerPackTwigExtension;

/**
 * @author    SpaceCatNinja
 * @since     1.0.0
 *
 * @property  PowerPackService $power
 *
 */
class PowerPack extends Plugin
{
    public function init(): void
    {
        parent::init();

        // Register services
        $this->setComponents([
            'power' => PowerPackService::class,
        ]);

        \Craft::$app->view->registerTwigExtension(new PowerPackTwigExtension());
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Settings
    {
        return new Settings();
    }
}
