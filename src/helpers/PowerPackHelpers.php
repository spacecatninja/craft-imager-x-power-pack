<?php
/**
 * Imager X plugin for Craft CMS
 *
 * Ninja powered image transforms.
 *
 * @link      https://www.spacecat.ninja
 * @copyright Copyright (c) 2024 André Elvan
 */

namespace spacecatninja\imagerxpowerpack\helpers;

use craft\base\Element;
use craft\elements\Asset;
use spacecatninja\imagerx\ImagerX;
use spacecatninja\imagerx\models\TransformedImageInterface;
use spacecatninja\imagerxpowerpack\models\Settings;
use yii\helpers\Html;

/**
 * Class PowerPackHelpers
 *
 * @author    SPACECATNINJA
 * @since     1.0.0
 */
class PowerPackHelpers
{
    public static function parseDefaultParams(Asset|string|null $image, array $params, Settings $settings): array
    {
        $defaults = $params['defaults'] ?? [];
        
        if (!empty($settings->defaultTransformParams)) {
            $defaults = array_merge($settings->defaultTransformParams, $defaults);
        }

        $params['defaults'] = $defaults;

        if (!isset($params['class'])) {
            $params['class'] = [];
        } elseif (!is_array($params['class'])) {
            $params['class'] = [$params['class']];
        }
        
        if (!isset($params['style'])) {
            $params['style'] = [];
        } elseif (is_string($params['style'])) {
            $params['style'] = Html::cssStyleToArray($params['style']);
        }
        
        if (!isset($params['sizes'])) {
            $params['sizes'] = '100vw';
        }
        
        if (!isset($params['imagerOverrides'])) {
            $params['imagerOverrides'] = [];
        }

        if (!isset($params['loading'])) {
            $params['loading'] = $settings->loading;
        }

        if (!isset($params['decoding'])) {
            $params['decoding'] = $settings->decoding;
        }

        if (!isset($params['alt']) && $image instanceof Asset) {
            $params['alt'] = $image[$settings->altTextHandle] ?? '';
        }

        if ($settings->lazysizes) {
            $params['class'][] = $settings->lazysizesClass;
        }

        if ($settings->objectPosition && $image instanceof Asset) {
            $params['style']['object-position'] = $image->getFocalPoint(true);
        }
        
        return $params;
    }

    public static function parseSources(array $sources): array
    {
        $r = [];

        foreach ($sources as $source) {
            $s = [$source[0] ?? null, $source[1] ?? null, $source[2] ?? 0, $source[3] ?? null];

            if (is_string($s[2]) && $s[3] === null && in_array($s[2], ['jpeg', 'jpg', 'gif', 'webp', 'avif', 'heic'])) {
                $s[3] = $s[2];
                $s[2] = null;
            }

            if ($s[3] === 'jpg') {
                $s[3] = 'jpeg';
            }

            if (in_array($s[2], ['landscape', 'portrait'])) {
                $s[2] = "(orientation: $s[2])";
            }

            if (is_int($s[2])) {
                $s[2] = "(min-width: $s[2]px)";
            }

            $r[] = $s;
        }

        if (self::hasMediaQueryStringValue($r)) {
            return $r;
        }

        usort($r, static fn($a, $b) => $a[2] < $b[2]);
        
        return $r;
    }

    public static function reduceSources(array $sources, Settings $settings): array
    {
        if (count($sources) <= 1) {
            return $sources;
        }
        
        $image = $sources[0][0];
        
        // Strip redundant sources if transformSvgs is false and all sources are the same
        if (!$settings->transformSvgs && self::isSvg($image)) {
            $allSame = true;
            
            foreach ($sources as $source) {
                if (!self::isSvg($source[0])) {
                    $allSame = false;
                    break;
                }
            }
            
            if ($allSame) {
                $sources = [array_pop($sources)];
            }
            return $sources;
        }
        
        // Strip redundant sources if transformAnimatedGifs is false and all sources are the same
        if (!$settings->transformAnimatedGifs && self::isAnimatedGif($image)) {
            $allSame = true;
            
            foreach ($sources as $source) {
                if (!self::isAnimatedGif($source[0])) {
                    $allSame = false;
                    break;
                }
            }
            
            if ($allSame) {
                $sources = [array_pop($sources)];
            }
            return $sources;
        }
        
        return $sources;
    }

    public static function hasMediaQueryStringValue(array $sources): bool
    {
        foreach ($sources as $source) {
            if (is_string($source[2])) {
                return true;
            }
        }

        return false;
    }

    public static function getPlaceholderStyles(TransformedImageInterface $image, Settings $settings): array
    {
        $placeHolderWidth = $settings->placeholderSize;
        $placeHolderHeight = round($placeHolderWidth * ($image->height / $image->width));

        if ($settings->placeholder === 'dominantColor') {
            $color = ImagerX::getInstance()->color->getDominantColor($image);

            return ['background-color' => $color];
        }

        if ($settings->placeholder === 'blurup') {
            try {
                $bgImage = ImagerX::getInstance()->imagerx->transformImage($image, ['width' => $placeHolderWidth, 'height' => $placeHolderHeight], $settings->blurupTransformParams, ['transformer' => 'craft']);
                $dataUri = $bgImage->getDataUri();
            } catch (\Throwable $throwable) {
                \Craft::error('An error occurred when trying to create placeholder in Imager X Power Pack: '.$throwable->getMessage(), __METHOD__);
                $dataUri = null;
            }

            if (!empty($dataUri)) {
                return ['background' => 'url('.$dataUri.') center center / cover'];
            }
        }

        if ($settings->placeholder === 'blurhash') {
            try {
                if (method_exists($image, 'getBlurhash')) {
                    $blurhash = $image->getBlurhash();
                    $dataUri = !empty($blurhash) ? ImagerX::getInstance()->placeholder->placeholder(['type' => 'blurhash', 'width' => $placeHolderWidth, 'height' => $placeHolderHeight, 'hash' => $blurhash]) : null;
                }
            } catch (\Throwable $throwable) {
                \Craft::error('An error occurred when trying to create placeholder in Imager X Power Pack: '.$throwable->getMessage(), __METHOD__);
                $dataUri = null;
            }

            if (!empty($dataUri)) {
                return ['background' => 'url('.$dataUri.') center center / cover'];
            }
        }

        return [];
    }
    
    public static function isSvg(Asset|string $image): bool
    {
        if ($image instanceof Asset) {
            return $image->extension === 'svg';
        }
        
        return pathinfo($image, PATHINFO_EXTENSION) === 'svg';
    }

    public static function isAnimatedGif(Asset|string $image): bool
    {
        if ($image instanceof Asset) {
            $extension = $image->extension;
        } else {
            $extension = pathinfo($image, PATHINFO_EXTENSION);
        }
        
        return $extension === 'gif' && ImagerX::getInstance()->imagerx->isAnimated($image);
    }
}
