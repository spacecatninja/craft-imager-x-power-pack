<?php
/**
 * Imager X Power Pack
 *
 * @link      https://www.spacecat.ninja/
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace SpaceCatNinja\ImagerXPowerPack;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Support\Facades\Twig;
use SpaceCatNinja\ImagerXPowerPack\Twig\PowerPackTwigExtension;

/**
 * @author    SpaceCatNinja
 * @since     1.0.0
 */
class PowerPack extends Plugin
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(Settings::class, fn() => $this->getSettings());
    }

    public function boot(): void
    {
        Twig::registerExtension(new PowerPackTwigExtension());
    }

    protected static function createSettings(): ?PluginSettings
    {
        return new Settings();
    }
}
