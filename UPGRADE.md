# Upgrading to Imager X Power Pack 2 (Craft 6)

This guide lists what changed in version 2 of Imager X Power Pack, and what you need to update in your project and custom code. It applies when upgrading from version 1 (Craft 4 or 5).

Templates that use `pppicture()`, `ppimg()` and `ppplaceholder()` aren't affected, and `pptransform()` only changed for SVGs and animated GIFs that aren't transformed (see below). Projects need to move and convert the config file. The class changes only affect custom code that uses the plugin's classes directly.

## Requirements

- Craft CMS 6.0.0 or later
- Imager X 7.0.0-beta.2 or later, Lite or Pro edition
- PHP 8.5 or later
- Imager X Power Pack 1.1.4 or later installed before you upgrade. Craft won't update from an older version.
- The plugin doesn't need the Yii 2 adapter (`craftcms/yii2-adapter`).

## Upgrade steps

1. Update the plugin to the latest 1.x version on Craft 5, at least 1.1.4.
2. Upgrade your project to Craft 6, and Imager X to version 7, following their upgrade guides.
3. Update the plugin: `composer require spacecatninja/imager-x-power-pack:^2.0.0`.
4. Move your config file to `config/craft/imager-x-power-pack.php`, and convert it (see below).
5. Review the breaking changes below, and update your code.

## Breaking changes

### The config file

Craft 6 reads plugin config files from `config/craft/`, as plain Laravel config. Move `config/imager-x-power-pack.php` to `config/craft/imager-x-power-pack.php`, and return a flat array instead of the multi-environment `'*'` format. The settings themselves are unchanged.

Before:

```php
return [
    '*' => [
        'lazysizes' => true,
        'placeholder' => 'dominantColor',
    ],
    'dev' => [
        'placeholder' => '',
    ],
];
```

After:

```php
return [
    'lazysizes' => true,
    'placeholder' => env('APP_ENV') === 'dev' ? '' : 'dominantColor',
];
```

Config files are loaded before Laravel knows the environment, so use `env()` for per-environment values, not `app()->environment()`.

### pptransform() with SVGs and animated GIFs

When `transformSvgs` or `transformAnimatedGifs` is off, `pptransform()` returns SVGs and animated GIFs untransformed. It now returns them the same way Imager X returns transformed images: in an array for quick syntax, a list of transforms, or a named transform with a list of transforms, and on its own for a single transform. Version 1 returned an array whenever the transforms were an array, also for a single transform like `{ width: 300 }`, and the image on its own for a named transform, also one with a list of transforms. A named transform threw an error when the image was a path.

Before:

```twig
{% set logo = pptransform(svgAsset, { width: 300 }) %}
<img src="{{ logo[0].url }}">
```

After:

```twig
{% set logo = pptransform(svgAsset, { width: 300 }) %}
<img src="{{ logo.url }}">
```

### Namespace and classes

The plugin moved from `spacecatninja\imagerxpowerpack` to the `SpaceCatNinja\ImagerXPowerPack` namespace, with Laravel-style folders.

| Before | After |
| --- | --- |
| `spacecatninja\imagerxpowerpack\PowerPack` | `SpaceCatNinja\ImagerXPowerPack\PowerPack` |
| `spacecatninja\imagerxpowerpack\models\Settings` | `SpaceCatNinja\ImagerXPowerPack\Settings` |
| `spacecatninja\imagerxpowerpack\services\PowerPackService` | `SpaceCatNinja\ImagerXPowerPack\Services\PowerPackService` |
| `spacecatninja\imagerxpowerpack\helpers\PowerPackHelpers` | `SpaceCatNinja\ImagerXPowerPack\Support\PowerPackHelpers` |
| `spacecatninja\imagerxpowerpack\twigextensions\PowerPackTwigExtension` | `SpaceCatNinja\ImagerXPowerPack\Twig\PowerPackTwigExtension` |

`PowerPackService` no longer extends `craft\base\Component`, and the settings class extends Craft 6's `CraftCms\Cms\Plugin\PluginSettings`. The classes use Imager X 7's classes, like `SpaceCatNinja\ImagerX\Data\TransformedImageInterface` and `SpaceCatNinja\ImagerX\Exceptions\ImagerException`.

### The service

The `power` service component on the plugin was removed. `PowerPackService` is a container singleton, so inject it, or resolve it with `app()`. Its methods are unchanged.

Before:

```php
use spacecatninja\imagerxpowerpack\PowerPack;

$markup = PowerPack::getInstance()->power->createImg($asset, [400, 1200]);
```

After:

```php
use SpaceCatNinja\ImagerXPowerPack\Services\PowerPackService;

$markup = app(PowerPackService::class)->createImg($asset, [400, 1200]);
```

Read the plugin settings with `app(SpaceCatNinja\ImagerXPowerPack\Settings::class)` instead of `PowerPack::getInstance()->getSettings()`.
