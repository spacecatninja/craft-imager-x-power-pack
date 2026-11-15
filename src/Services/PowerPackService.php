<?php
/**
 * Imager X Power Pack
 *
 * @link      https://www.spacecat.ninja/
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace SpaceCatNinja\ImagerXPowerPack\Services;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Svg;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;
use SpaceCatNinja\ImagerX\Adapters\ImagerAdapterInterface;
use SpaceCatNinja\ImagerX\Data\TransformedImageInterface;
use SpaceCatNinja\ImagerX\Exceptions\ImagerException;
use SpaceCatNinja\ImagerX\Services\ImagerService;
use SpaceCatNinja\ImagerX\Services\PlaceholderService;
use SpaceCatNinja\ImagerXPowerPack\Settings;
use SpaceCatNinja\ImagerXPowerPack\Support\PowerPackHelpers;
use Twig\Markup;

/**
 * Power Pack Service
 *
 * @author    SpaceCatNinja
 * @since     1.0.0
 */
#[Singleton]
class PowerPackService
{
    public function __construct(
        private readonly ImagerService $imager,
        private readonly PlaceholderService $placeholders,
    ) {
    }

    public function createPicture(array $sources, ?array $params = null, ?array $configOverrides = null, bool $imgOnly = false): Markup
    {
        $settings = $this->getSettings($configOverrides);

        if (!is_array($sources[0])) {
            $sources = [$sources];
        }

        $imagerConfig = ImagerService::getConfig();

        $params = PowerPackHelpers::parseDefaultParams($sources[0][0] ?? null, $params, $settings);

        $sizes = $params['sizes'];
        $defaults = $params['defaults'];
        $imagerOverrides = $params['imagerOverrides'];

        unset($params['defaults'], $params['sizes'], $params['imagerOverrides']);

        $sources = PowerPackHelpers::parseSources($sources);
        
        if (!$settings->transformSvgs || !$settings->transformAnimatedGifs) {
            $sources = PowerPackHelpers::reduceSources($sources, $settings);
        }
        
        $elements = [];
        $fallbackSrcUrl = null;

        foreach ($sources as [$image, $transform, $mediaQuery, $format]) {
            if ($image === null) {
                continue;
            }
            
            $isLast = !(count($elements) < (count($sources) - 1));
            $elementType = $isLast ? 'img' : 'source';
            $canHavePlaceholder = true;

            if ((PowerPackHelpers::isSvg($image) && !$settings->transformSvgs) || (PowerPackHelpers::isAnimatedGif($image) && !$settings->transformAnimatedGifs)) {
                $defaultImage = $image;
                $canHavePlaceholder = false;
                
                $srcImageUrl = '';
                $defaultImageWidth = null;
                $defaultImageHeight = null;
                
                if ($image instanceof Asset) {
                    $defaultImageWidth = $image->getWidth();
                    $defaultImageHeight = $image->getHeight();
                    $srcImageUrl = $image->getUrl();
                } else if (is_string($image) and str_starts_with($image, 'http://') or str_starts_with($image, 'https://') or str_starts_with($image, '//') or str_starts_with($image, 'data:image/')) {
                    $srcImageUrl = $image;
                } else {
                    // Image is a string, we need to assume that this can be used as an URL, and is relative to @webroot
                    
                    if (str_starts_with($image, '@webroot')) {
                        $srcImageUrl = '/'.ltrim(ltrim($image, '@webroot'), '/');
                        $imagePath = Env::parse($image);
                    } else {
                        $srcImageUrl = '/'.ltrim($image, '/');
                        $imagePath = Env::parse('@webroot'.$srcImageUrl);
                    }
                    
                    if (PowerPackHelpers::isSvg($image)) {
                        $svg = new Svg();
                        $svg->loadImage($imagePath);
                        $defaultImageWidth = $svg->getWidth();
                        $defaultImageHeight = $svg->getHeight();
                    } else {
                        [$defaultImageWidth, $defaultImageHeight] = getimagesize($imagePath);
                    }
                }
                
                if (($srcImageUrl !== '' && $defaultImageWidth !== null) || $settings->lazysizes) {
                    $srcset = $srcImageUrl.' '.($defaultImageWidth ?? 1).'w';
                } else {
                    $srcset = '';
                }
                
            } else {
                try {
                    $transforms = $this->imager->transformImage($image, $transform, $defaults, $imagerOverrides);
                } catch (ImagerException $e) {
                    Log::error('An error occurred when trying to transform image in Imager X Power Pack: '.$e->getMessage());

                    if ($imagerConfig->suppressExceptions) {
                        return new Markup('', 'utf-8');
                    }

                    throw $e;
                }

                // With suppressExceptions on, Imager X logs the error and returns null instead of throwing
                if (empty($transforms)) {
                    return new Markup('', 'utf-8');
                }

                if (!is_array($transforms)) {
                    $transforms = [$transforms];
                }
                
                $defaultImage = $transforms[0] ?? null;
                $defaultImageWidth = $defaultImage?->width;
                $defaultImageHeight = $defaultImage?->height;
                $srcset = $this->imager->srcset($transforms);
                $srcImageUrl = $defaultImage?->url;
            }
            
            if ($defaultImageWidth === 0) {
                $defaultImageWidth = null;
            } 
            
            if ($defaultImageHeight === 0) {
                $defaultImageHeight = null;
            } 
            
            if ($isLast) {
                $fallbackSrcUrl = $srcImageUrl;
                $fallbackImageWidth = $defaultImageWidth;
                $fallbackImageHeight = $defaultImageHeight;
            }
            
            if ($settings->lazysizes) {
                $attrs = [
                    'src' => $elementType === 'img' ? $this->placeholders->placeholder(['width' => $defaultImageWidth ?? 1, 'height' => $defaultImageHeight ?? 1]) : null,
                    'srcset' => $this->placeholders->placeholder(['width' => $defaultImageWidth ?? 1, 'height' => $defaultImageHeight ?? 1]),
                    'data-sizes' => 'auto',
                    'data-srcset' => $srcset,
                    'data-aspectratio' => $defaultImageWidth && $defaultImageHeight ? $defaultImageWidth / $defaultImageHeight : null,
                ];
            } else {
                $attrs = [
                    'src' => $elementType === 'img' ? $srcImageUrl : null,
                    'srcset' => !empty($srcset) ? $srcset : null,
                    'sizes' => !empty($srcset) ? $sizes : null,
                ];
            }

            if (!isset($params['width'], $params['height'])) {
                $attrs += [
                    'width' => $defaultImageWidth,
                    'height' => $defaultImageHeight,
                ];
            }

            if ($elementType === 'img') {
                if (!empty($params)) {
                    $attrs += $params;
                }

                // The placeholder needs the image's dimensions. Transformers can't always tell them, for instance
                // Imgix for external images when getExternalImageDimensions is off, and the image is shown without one.
                if ($canHavePlaceholder && $settings->placeholder !== '' && $defaultImageWidth && $defaultImageHeight) {
                    $transform = $this->imager->transformImage($image, ['width' => 200, 'ratio' => $defaultImageWidth/$defaultImageHeight ], [], ['transformer' => 'craft']);
                    $styles = PowerPackHelpers::getPlaceholderStyles($transform, $settings);
                    
                    if (!empty($styles)) {
                        $attrs['style'] = [...$styles, ...$attrs['style']];
                    }
                }
            }

            if ($elementType === 'source') {
                if (!empty($mediaQuery)) {
                    $attrs['media'] = is_int($mediaQuery) ? '(min-width: '.$mediaQuery.'px)' : $mediaQuery;
                }

                if ($format !== null) {
                    $attrs['type'] = 'image/'.$format;
                }
            }

            $element = Html::tag($elementType, PHP_EOL);
            $element = Html::modifyTagAttributes($element, $attrs);

            $elements[] = $element;
        }

        if (!$imgOnly) {
            $markup = Html::tag('picture', implode('', $elements));
        } else {
            $markup = implode('', $elements);
        }

        // If using lazysizes, let's output a rudimentary noscript alternative
        if ($settings->lazysizes && $fallbackSrcUrl) {
            if (isset($params['class'])) {
                $index = array_search($settings->lazysizesClass, $params['class'], true);
                if ($index !== false) {
                    unset($params['class'][$index]);
                }
            }
            
            $noscriptImg = Html::tag('img', PHP_EOL, [
                    'src' => $fallbackSrcUrl,
                    'width' => $fallbackImageWidth,
                    'height' => $fallbackImageHeight,
                ] + $params);

            $markup .= Html::tag('noscript', $noscriptImg);
        }

        $this->maybeLoadLazysizes($settings);

        // Return resulting markup
        return new Markup($markup, 'utf-8');
    }
    
    public function createImg(Asset|ImagerAdapterInterface|string|null $image, array|string $transform, array $params = [], array $configOverrides = []): Markup
    {
        return $this->createPicture([$image, $transform], $params, $configOverrides, true);
    }
    
    public function createPlaceholder(Asset|string|null $image, string $output='attr', string $type='dominantColor', ?array $configOverrides = null): string
    {
        if (empty($image)) {
            return '';
        }
        
        $settings = $this->getSettings($configOverrides);

        if (empty($type)) {
            return '';
        }
        
        $settings->placeholder = $type;
        
        $transform = $this->imager->transformImage($image, ['width' => 200 ], [], ['transformer' => 'craft']);
        $styles = PowerPackHelpers::getPlaceholderStyles($transform, $settings);

        if (empty($styles)) {
            return '';
        }
        
        $parsedStyles = Html::cssStyleFromArray($styles);
        
        return $output==='attr' ? 'style="'.$parsedStyles.'"' : $parsedStyles;
    }
    
    public function transform(Asset|ImagerAdapterInterface|string|null $image, array|string $transforms, ?array $defaults = null, ?array $config = null): array|TransformedImageInterface|Asset|string|null
    {
        $settings = app(Settings::class);

        if ((PowerPackHelpers::isSvg($image) && !$settings->transformSvgs) || (PowerPackHelpers::isAnimatedGif($image) && !$settings->transformAnimatedGifs)) {
            // Returned the way Imager X would return the transformed images, as a list or a single image
            return PowerPackHelpers::isTransformList($transforms) ? [$image] : $image;
        }
        
        return $this->imager->transformImage($image, $transforms, $defaults, $config);
    }
    

    /**
     * --- Private methods ------------------------------------------------------------------------------------------------------------------
     */
    
    /**
     * Returns a copy of the plugin settings with the config overrides applied, so the overrides don't change the
     * settings that other calls use.
     */
    private function getSettings(?array $configOverrides): Settings
    {
        $settings = clone app(Settings::class);

        if (!empty($configOverrides)) {
            Settings::configure($settings, $configOverrides);
        }

        return $settings;
    }

    private function maybeLoadLazysizes(Settings $settings): void
    {
        if ($settings->lazysizes && $settings->autoloadLazysizes) {
            HtmlStack::jsFile($settings->lazysizesURL, [
                'async' => true,
            ]);
        }
    }
}
