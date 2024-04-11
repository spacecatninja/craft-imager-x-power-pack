<?php
/**
 * Imager X plugin for Craft CMS
 *
 * Ninja powered image transforms.
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace spacecatninja\imagerxpowerpack\twigextensions;

use craft\elements\Asset;
use spacecatninja\imagerxpowerpack\PowerPack;
use Twig\Extension\AbstractExtension;

use Twig\Markup;
use Twig\TwigFunction;

/**
 * @author    SpaceCatNinja
 * @since     1.0.0
 */
class PowerPackTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'Imager X Power Pack';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pppicture', [$this, 'pppicture'], ['is_safe' => ['html']]),
            new TwigFunction('ppimg', [$this, 'ppimg'], ['is_safe' => ['html']]),
        ];
    }

    public function pppicture(array $sources, array $params = [], array $configOverrides = []): Markup
    {
        return PowerPack::getInstance()->power->createPicture($sources, $params, $configOverrides);
    }
    
    public function ppimg(Asset|string $image, array|string $transform, array $params = [], array $configOverrides = []): Markup
    {
        return PowerPack::getInstance()->power->createImg($image, $transform, $params, $configOverrides);
    }
}
