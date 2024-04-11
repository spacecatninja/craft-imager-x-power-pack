<?php
/**
 * Imager X Power Pack
 *
 * @link      https://www.spacecat.ninja/
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace spacecatninja\imagerxpowerpack;

use craft\base\Plugin;

use spacecatninja\imagerxpowerpack\models\Settings;
use spacecatninja\imagerxpowerpack\services\PowerPackService;
use spacecatninja\imagerxpowerpack\twigextensions\PowerPackTwigExtension;

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
