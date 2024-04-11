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
    public static function parseDefaultParams(Asset|string $image, array $params, Settings $settings): array
    {
        $defaults = $params['defaults'] ?? [];
        
        if (!empty($settings->defaultTransformParams)) {
            $defaults = array_merge($settings->defaultTransformParams, $defaults);
        }

        $params['defaults'] = $defaults;
        
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
            $params['class'] = isset($params['class']) ? $params['class'].' '.$settings->lazysizesClass : $settings->lazysizesClass;
        }

        if ($settings->objectPosition && $image instanceof Asset) {
            $objectPositionStyles = 'object-position: '.$image->getFocalPoint(true).'; ';
            $params['style'] = isset($params['style']) ? $objectPositionStyles.$params['style'] : $objectPositionStyles;
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

    public static function hasMediaQueryStringValue(array $sources): bool
    {
        foreach ($sources as $source) {
            if (is_string($source[2])) {
                return true;
            }
        }

        return false;
    }

    public static function getPlaceholderStyles(TransformedImageInterface $image, Settings $settings): string
    {
        $placeHolderWidth = $settings->placeholderSize;
        $placeHolderHeight = round($placeHolderWidth * ($image->height / $image->width));

        if ($settings->placeholder === 'dominantColor') {
            $color = ImagerX::getInstance()->color->getDominantColor($image);

            return 'background-color: '.$color.';';
        }

        if ($settings->placeholder === 'blurup') {
            try {
                $bgImage = ImagerX::getInstance()->imagerx->transformImage($image, ['width' => $placeHolderWidth, 'height' => $placeHolderHeight], [], ['transformer' => 'craft']);
                $dataUri = $bgImage->getDataUri();
            } catch (\Throwable $throwable) {
                \Craft::error('An error occurred when trying to create placeholder in Imager X Power Pack: '.$throwable->getMessage(), __METHOD__);
                $dataUri = null;
            }

            if (!empty($dataUri)) {
                return 'background: url('.$dataUri.') center center / cover;';
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
                return 'background: url('.$dataUri.') center center / cover;';
            }
        }

        return '';
    }

}
