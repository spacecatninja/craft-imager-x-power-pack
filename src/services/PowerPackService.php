<?php
/**
 * Imager X Power Pack
 *
 * @link      https://www.spacecat.ninja/
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace spacecatninja\imagerxpowerpack\services;

use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\App;
use craft\helpers\Html;
use craft\image\Svg;
use spacecatninja\imagerx\adapters\ImagerAdapterInterface;
use spacecatninja\imagerx\exceptions\ImagerException;
use spacecatninja\imagerx\ImagerX;
use spacecatninja\imagerx\models\TransformedImageInterface;
use spacecatninja\imagerx\services\ImagerService;
use spacecatninja\imagerxpowerpack\helpers\PowerPackHelpers;
use spacecatninja\imagerxpowerpack\PowerPack;
use spacecatninja\imagerxpowerpack\models\Settings;
use Twig\Markup;


/**
 * Power Pack Service
 *
 * @author    SpaceCatNinja
 * @since     1.0.0
 */
class PowerPackService extends Component
{
    
    public function createPicture(array $sources, ?array $params = null, ?array $configOverrides = null, bool $imgOnly = false): Markup
    {
        /* @var Settings $settings */
        $settings = clone PowerPack::getInstance()?->getSettings(); // cloning to avoid overriding cached values when applying overrides

        if ($configOverrides !== null) {
            $settings->setAttributes($configOverrides, false);
        }

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
                
                if ($image instanceof Asset) {
                    $defaultImageWidth = $image->width;
                    $defaultImageHeight = $image->height;
                    $srcset = $image->url.' '.($defaultImageWidth ?? 1).'w';
                    $srcImageUrl = $image->url;
                } else {
                    // Image is a string, we need to assume that this can be used as an URL, and is relative to @webroot
                    
                    if (str_starts_with($image, '@webroot')) {
                        $srcImageUrl = '/'.ltrim(ltrim($image, '@webroot'), '/');
                        $imagePath = App::parseEnv($image);
                    } else {
                        $srcImageUrl = '/'.ltrim($image, '/');
                        $imagePath = App::parseEnv('@webroot'.$srcImageUrl);
                    }
                    
                    if (PowerPackHelpers::isSvg($image)) {
                        $svg = new Svg();
                        $svg->loadImage($imagePath);
                        $defaultImageWidth = $svg->getWidth();
                        $defaultImageHeight = $svg->getHeight();
                    } else {
                        [$defaultImageWidth, $defaultImageHeight] = getimagesize($imagePath);
                    }
                    
                    $defaultImageWidth = $defaultImageWidth;
                    $defaultImageHeight = $defaultImageHeight;
                    
                    $srcset = $srcImageUrl.' '.$defaultImageWidth.'w';
                }
                
            } else {
                try {
                    $transforms = ImagerX::getInstance()->imager->transformImage($image, $transform, $defaults, $imagerOverrides);
                } catch (ImagerException $e) {
                    \Craft::error('An error occured when trying to transform image in Imager X Power Pack: '.$e->getMessage(), __METHOD__);

                    if ($imagerConfig->suppressExceptions) {
                        return new Markup('', 'utf-8');
                    }

                    throw $e;
                }
                
                $defaultImage = $transforms[0] ?? null;
                $defaultImageWidth = $defaultImage?->width;
                $defaultImageHeight = $defaultImage?->height;
                $srcset = ImagerX::getInstance()->imager->srcset($transforms);
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
                    'src' => $elementType === 'img' ? ImagerX::getInstance()->placeholder->placeholder(['width' => $defaultImageWidth ?? 1, 'height' => $defaultImageHeight ?? 1]) : null,
                    'srcset' => ImagerX::getInstance()->placeholder->placeholder(['width' => $defaultImageWidth ?? 1, 'height' => $defaultImageHeight ?? 1]),
                    'data-sizes' => 'auto',
                    'data-srcset' => $srcset,
                    'data-aspectratio' => $defaultImageWidth && $defaultImageHeight ? $defaultImageWidth / $defaultImageHeight : null,
                ];
            } else {
                $attrs = [
                    'src' => $elementType === 'img' ? $srcImageUrl : null,
                    'srcset' => $srcset,
                    'sizes' => $sizes,
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

                if ($canHavePlaceholder && $settings->placeholder !== '') {
                    $transform = ImagerX::getInstance()->imager->transformImage($image, ['width' => 200, 'ratio' => $defaultImageWidth/$defaultImageHeight ], [], ['transformer' => 'craft']);
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
    
    public function createImg(Asset|string|null $image, array|string $transform, array $params = [], array $configOverrides = []): Markup
    {
        return $this->createPicture([$image, $transform], $params, $configOverrides, true);
    }
    
    public function createPlaceholder(Asset|string|null $image, string $output='attr', string $type='dominantColor', ?array $configOverrides = null): string
    {
        if (empty($image)) {
            return '';
        }
        
        /* @var Settings $settings */
        $settings = clone PowerPack::getInstance()?->getSettings(); // cloning to avoid overriding cached values when applying overrides
        
        if ($configOverrides !== null) {
            $settings->setAttributes($configOverrides, false);
        }

        if (empty($type)) {
            return '';
        }
        
        $settings->placeholder = $type;
        
        $transform = ImagerX::getInstance()->imager->transformImage($image, ['width' => 200 ], [], ['transformer' => 'craft']);
        $styles = PowerPackHelpers::getPlaceholderStyles($transform, $settings);

        if (empty($styles)) {
            return '';
        }
        
        $parsedStyles = Html::cssStyleFromArray($styles);
        
        return $output==='attr' ? 'style="'.$parsedStyles.'"' : $parsedStyles;
    }
    
    public function transform(Asset|ImagerAdapterInterface|string|null $image, array|string $transforms, array $defaults = null, array $config = null): array|TransformedImageInterface|Asset|null
    {
        /* @var Settings $settings */
        $settings = clone PowerPack::getInstance()?->getSettings();
        
        if ((PowerPackHelpers::isSvg($image) && !$settings->transformSvgs) || (PowerPackHelpers::isAnimatedGif($image) && !$settings->transformAnimatedGifs)) {
            return is_array($transforms) ? [$image] : $image;
        }
        
        return ImagerX::getInstance()->imager->transformImage($image, $transforms, $defaults, $config);
    }
    

    /**
     * --- Private methods ------------------------------------------------------------------------------------------------------------------
     */
    
    private function maybeLoadLazysizes(Settings $settings): void
    {
        if ($settings->lazysizes && $settings->autoloadLazysizes) {
            \Craft::$app->view->registerJsFile($settings->lazysizesURL, [
                'async' => ''
            ]);
        }
    }
}
