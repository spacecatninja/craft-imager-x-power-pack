### Imager X Power Pack for Craft CMS

Unlocks a stash of hidden weapons in your quest to optimize images, while simultaniously saving time and cognitive load. It's a good deal.    

## Requirements

This plugin requires Imager X 4.5+/5.0+ and Craft CMS 4.0+/5.0+.

## Installation

To install the plugin, follow these instructions:

1. Install with composer via `composer require spacecatninja/imager-x-power-pack` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings → Plugins, or from the command line via `./craft install/plugin imager-x-power-pack`.

---

## Usage

(to be continued)


## Template functions

### ppimg($image, $transform[, $params=[], $configOverrides=[]])
Outputs a single `<img>` tag.

(to be continued, examples for now)

```
{{ ppimg(image, [1000, 2000, 16/9]) }}
```

```
{{ 
    ppimg(image, 'myNamedTransform', {
        class: 'absolute full',
        loading: 'eager'
    }, {
        lazysizes: true,
        placeholder: 'blurhash'
    }) 
}}
```


### pppicture($sources[, $params=[], $configOverrides=[]])
Ouputs a full `<picture>` tag with all the bells and whistles.

(to be continued, examples for now)

```
{{ pictureMarkup = pppicture(
    [
        [image, 'myLargeTransforms', 768],
        [imageMobile, 'mySmallTransforms']
    ]
) }}
```

```
{{ pppicture(
    [
        [image, 'heroLandscape', 'landscape'],
        [image, 'heroPortrait']
    ],
    {
        class: 'absolute full'
    }
) }}
```

```
{{ pppicture(
    [
        [image, [1000, 2000, 16/9, 'webp'], 1440, 'webp'],
        [image, [1000, 1000, 16/9], 1440],
        [image, [1000, 2000, 4/3, 'webp'], 'webp'],
        [image, [2000, 2000, 4/3, 'jpg']],
    ],
    {
        class: 'absolute full',
    }
) }}
```

```
{{ pppicture(
    [
        [image, [1000, 2000, 16/9], 768],
        [imageMobile, [500, 1000, 3/4]]
    ],
    {
        defaults: { effects: { grayscale: true, sharpen: true } },
        class: 'absolute full',
        loading: 'eager',
        decoding: 'async',
        alt: 'Custom alt text'
    }
)|attr({ class: 'lorem ipsum' }) }}
```

## Configuring

You can configure the adapter by creating a file in your config folder called
`imager-x-power-pack.php`, and override settings as needed.

### altTextHandle [string]
Default: `'alt'`  
The name of the Asset field handle to be used for alternative text on the image tag. 
Defaults to the built in `alt` field, but can be changed to a custom field.

### placeholder [string]
Default: `''`  
Possible values: `'', 'dominantColor', 'blurup', 'blurhash'`   
When enabled a css placeholder will be added to the image tag, and will be displayed until the
image is loaded.

### placeholderSize [int]
Default: `16`  
When using the `blurup` style placeholder, this is the base size (width) of the small image
that is generated and used as the blurup. A higher value will create a more detailed placeholder,
but will increase the size of the base64 encoded image and your document size.

### loading [string]
Default: `'lazy'`  
Sets the [loading strategy](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#loading) for the image tag.
You'd usually want this set to `'lazy'`, but for images that are candidates to be your[Largest Contentful Paint (LCP)](https://web.dev/articles/lcp)
element, you'd want to use `'eager'`.

### decoding [string]
Default: `'auto'`  
Sets the [decoding hint](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#decoding) for the image tag.

### objectPosition [bool]
Default: `true`  
When enabled (default) an `object-position` CSS style with the focal point from Craft, will automatically be added to the 
image tag. This will ensure that the focal point of the image is taken into consideration when the image is used to cover 
a wrapper with a different aspect ratio. The styles for the image and wrapper is up to you to add.

### defaultTransformParams [array]
Default: `[]`  
Default transforms that are merged into all transform.

_If you use auto generation, make sure to include these defaults explicitely in your named transforms. Failing to do so will 
render auto generation useless._

### lazysizes [bool]
Default: `false`  
When enabled the markup generated will be customized to fit with the (awesome) [lazysizes](https://github.com/aFarkas/lazysizes)
library.

### lazysizesClass [string]
Default: `'lazyload'`  
The name of the class that lazysizes is configured to use.

### autoloadLazysizes [bool]
Default: `false`  
When enabled the lazysizes bundle specified in `lazysizesURL` will automatically be loaded when needed. 

### lazysizesURL [string]
Default: `'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js'`  
The URL to the lazysizes bundle that will be loaded if `autoloadLazysizes` is set to `true`.


---

Price, license and support
---
The plugin is released under the MIT license. It requires Imager X, which is a commercial plugin [available in the Craft plugin store](https://plugins.craftcms.com/imager-x). 
