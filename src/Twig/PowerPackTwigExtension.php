<?php
/**
 * Imager X plugin for Craft CMS
 *
 * Ninja powered image transforms.
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace SpaceCatNinja\ImagerXPowerPack\Twig;

use CraftCms\Cms\Asset\Elements\Asset;
use SpaceCatNinja\ImagerX\Adapters\ImagerAdapterInterface;
use SpaceCatNinja\ImagerX\Data\TransformedImageInterface;
use SpaceCatNinja\ImagerXPowerPack\Services\PowerPackService;
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
            new TwigFunction('ppplaceholder', [$this, 'ppplaceholder'], ['is_safe' => ['html']]),
            new TwigFunction('pptransform', [$this, 'pptransform']),
        ];
    }

    public function pppicture(array $sources, array $params = [], array $config = []): Markup
    {
        return app(PowerPackService::class)->createPicture($sources, $params, $config);
    }
    
    public function ppimg(Asset|string|null $image, array|string $transform, array $params = [], array $config = []): Markup
    {
        return app(PowerPackService::class)->createImg($image, $transform, $params, $config);
    }
    
    public function ppplaceholder(Asset|string|null $image, string $output='attr', string $type='dominantColor', ?array $config = null): string
    {
        return app(PowerPackService::class)->createPlaceholder($image, $output, $type, $config);
    }
    
    public function pptransform(Asset|ImagerAdapterInterface|string|null $image, array|string $transforms, ?array $defaults = null, ?array $config = null): array|TransformedImageInterface|Asset|string|null
    {
        return app(PowerPackService::class)->transform($image, $transforms, $defaults, $config);
    }
}
