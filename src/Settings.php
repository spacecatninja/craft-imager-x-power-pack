<?php
/**
 * Imager X Power Pack
 *
 * @link      https://www.spacecat.ninja/
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace SpaceCatNinja\ImagerXPowerPack;

use CraftCms\Cms\Plugin\PluginSettings;

class Settings extends PluginSettings
{
    public array $defaultTransformParams = [];
    
    public string $altTextHandle = 'alt';
    
    public string $placeholder = ''; // '', 'dominantColor', 'blurup', 'blurhash'
    public int $placeholderSize = 16;
    public array $blurupTransformParams = ['effects' => ['blur' => true]];
    
    public string $loading = 'lazy';
    public string $decoding = 'auto';
    public bool $objectPosition = true;
    
    public bool $transformSvgs = false;
    public bool $transformAnimatedGifs = false;
    
    public bool $lazysizes = false;
    public string $lazysizesClass = 'lazyload';
    public bool $autoloadLazysizes = false;
    public string $lazysizesURL = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
}
