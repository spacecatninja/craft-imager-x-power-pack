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
use craft\helpers\Html;
use spacecatninja\imagerx\exceptions\ImagerException;
use spacecatninja\imagerx\ImagerX;
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

    /**
     * @param array      $sources
     * @param array|null $params
     * @param array|null $configOverrides
     * @param bool       $imgOnly
     *
     * @return \Twig\Markup
     * @throws \spacecatninja\imagerx\exceptions\ImagerException
     */
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
        $fallbackImage = null;

        foreach ($sources as [$image, $transform, $mediaQuery, $format]) {
            $isLast = !(count($elements) < (count($sources) - 1));
            $elementType = $isLast ? 'img' : 'source';

            try {
                $transforms = ImagerX::getInstance()->imager->transformImage($image, $transform, $defaults, $imagerOverrides);
            } catch (ImagerException $e) {
                \Craft::error('An error occured when trying to transform image in Imager X Power Pack: '.$e->getMessage(), __METHOD__);

                if ($imagerConfig->suppressExceptions) {
                    return new Markup('', 'utf-8');
                }

                throw $e;
            }

            $firstImage = $transforms[0] ?? null;

            if ($isLast) {
                $fallbackImage = $firstImage;
            }

            if ($settings->lazysizes) {
                $attrs = [
                    'src' => $elementType === 'img' ? ImagerX::getInstance()->placeholder->placeholder(['width' => $firstImage?->width, 'height' => $firstImage?->height]) : null,
                    'srcset' => ImagerX::getInstance()->placeholder->placeholder(['width' => $firstImage?->width, 'height' => $firstImage?->height]),
                    'data-sizes' => 'auto',
                    'data-srcset' => ImagerX::getInstance()->imager->srcset($transforms),
                    'data-aspectratio' => $firstImage ? $firstImage->width / $firstImage->height : null,
                ];
            } else {
                $attrs = [
                    'src' => $elementType === 'img' ? $firstImage?->url : null,
                    'srcset' => ImagerX::getInstance()->imager->srcset($transforms),
                    'sizes' => $sizes,
                ];
            }

            if (!isset($params['width'], $params['height'])) {
                $attrs += [
                    'width' => $firstImage?->width,
                    'height' => $firstImage?->height,
                ];
            }

            if ($elementType === 'img') {
                if (!empty($params)) {
                    $attrs += $params;
                }

                if ($settings->placeholder !== '') {
                    $styles = PowerPackHelpers::getPlaceholderStyles($firstImage, $settings);

                    if ($styles !== '') {
                        if (isset($attrs['style'])) {
                            $attrs['style'] .= ' '.$styles;
                        } else {
                            $attrs['style'] = $styles;
                        }
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

            $element = Html::tag($elementType, PHP_EOL, $attrs);

            $elements[] = $element;
        }

        if (!$imgOnly) {
            $markup = Html::tag('picture', implode('', $elements));
        } else {
            $markup = implode('', $elements);
        }

        // If using lazysizes, let's output a rudimentary noscript alternative
        if ($settings->lazysizes && $fallbackImage) {
            if (isset($params['class'])) {
                $params['class'] = trim(str_replace($settings->lazysizesClass, '', $params['class']));
                
                if ($params['class'] === '') {
                    $params['class'] = null;
                }
            }
            
            $noscriptImg = Html::tag('img', PHP_EOL, [
                    'src' => $fallbackImage->url,
                    'width' => $fallbackImage->width,
                    'height' => $fallbackImage->height,
                ] + $params);

            $markup .= Html::tag('noscript', $noscriptImg);
        }

        $this->maybeLoadLazysizes($settings);

        // Return resulting markup
        return new Markup($markup, 'utf-8');
    }


    public function createImg(Asset|string $image, array|string $transform, array $params = [], array $configOverrides = []): Markup
    {
        return $this->createPicture([$image, $transform], $params, $configOverrides, true);
    }

    private function maybeLoadLazysizes(Settings $settings): void
    {
        if ($settings->lazysizes && $settings->autoloadLazysizes) {
            \Craft::$app->view->registerJsFile($settings->lazysizesURL, [
                'async' => ''
            ]);
        }
    }
}
