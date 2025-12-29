# Imager X Power Pack for Craft CMS

Unlocks a stash of hidden weapons in your quest to optimize images, while simultaneously
saving time and cognitive load. It's a good deal.

## Requirements

This plugin requires Imager X 4.5+/5.0+ and Craft CMS 4.0+/5.0+.

## Installation

To install the plugin, follow these instructions:

1. Install with composer via `composer require spacecatninja/imager-x-power-pack` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings → Plugins, or from the command line via `./craft plugin/install imager-x-power-pack`.

---

## Usage

The Power Pack comes with a couple of convenient template functions that will help you craft
optimal responsive images. It won't be a good fit for _all_ use-cases, and is _slightly opinionated_, but
aims to turbo charge _most_ of your needs.

The main functionality is wrapped in the `pppicture` template function, for generating complex `<picture>` markup fast and easy, 
and `ppimg` that generates a single `<img>` tag. Both assume that you're familiar with, and leverage, `srcset` and `sizes`
(which you should). They also lean into Imager's named transforms and quick transform syntax, to provide markup that
is compact and easy to maintain.

The Power Pack comes with a number of [config settings](#configuring) that can be used to customize the output. For instance
will alternative text automatically be pulled from the native `alt` field in Craft, but you can configure it to use a different field
if you want. The `loading` strategy will be set to `lazy` and `decoding` to `auto`, but this can also be changed, either globally in the
config, or directly in your templates on a case to case basis. By default SVGs and animated GIFs will not be transformed, and used as-is
in a seemless manner, but this can also be reconfigured if you actually want to let Imager handle them.

There is also support for the _awesome_ [lazySizes](https://afarkas.github.io/lazysizes) library, which makes it even easier
and faster to deliver optimized images. Just enable the `lazysizes` config settings, optionally `autoloadLazysizes` if you don't
already include the lazySizes JS bundle in your code, and you're done.

For more details, read on.

## Template functions

### pppicture($sources[, $params=[], $config=[]])

Ouputs a full `<picture>` tag with all the bells and whistles.

The `sources` parameter is an array of arrays, where each source have the following parameters:

```
[image, transform, mediaQuery, format]
```

`image` and `transform` can be anything that you'd normally pass into `craft.imagerx.transformImage()`. The last two parameters
relate to the source tag of the picture element, and will add a `media` and/or a `type` attribute to it. An example or five speaks
a thousand words, so let's have a look.

Let's start easy, we have an art directed image where we want to use the named transform `mySmallTransforms` up to 750px browser width,
and then `myLargeTransforms` above that. For the smaller sizes, we want to use the asset `imageMobile`, and `image` for bigger sizes:

```
{# Template code #}
{{ pppicture(
    [
        [image, 'myLargeTransforms', 750],
        [imageMobile, 'mySmallTransforms']
    ]
) }}

{# Resulting markup #}
<picture>
    <source srcset="..." 
        sizes="100vw"
        width="1200" height="675" 
        media="(min-width: 768px)"> 
    <img src="..." srcset="..." 
        sizes="100vw" 
        width="800" height="800" 
        alt="The quick brown fox jumps" 
        loading="lazy" decoding="auto" 
        style="object-position: 50% 50%;">
</picture>
```

A lot of attributes going on (I've removed the actual transforms from `src` and `srcset` to make things more readable), 
but the gist of it is; We got a `<picture>` element with one `<source>` that has a media query which will trigger for browser sizes
from 768px and above, and an `<img>` element with the fallback sources, and the necessary attributes.

Let's build on this and add additional support for browsers that support WebP.

```
{# Template code #}
{{ pppicture(
    [
        [image, 'myLargeWebpTransforms', 750, 'webp'],
        [image, 'myLargeTransforms', 750],
        [imageMobile, 'mySmallWebpTransforms', 'webp'],
        [imageMobile, 'mySmallTransforms']
    ]
) }}

{# Resulting markup #}
<picture>
    <source srcset="..." width="1200" height="675" media="(min-width: 768px)" type="image/webp" sizes="100vw">
    <source srcset="..." width="1200" height="675" media="(min-width: 768px)" sizes="100vw">
    <source srcset="..." width="800" height="800" type="image/webp" sizes="100vw">
    <img src="..." srcset="..." width="800" height="800" alt="The quick brown fox jumps" sizes="100vw" loading="lazy" decoding="auto" style="object-position: 50% 50%;">
</picture>
```

As you can see, additional sources have now been added that will kick in if the browser supports WebP.

_The parameter format does not control the file format, it only sets the source’s attribute (`type="image/webp"`)._

Notice that the syntax for sources is pretty loose, if you don't need a media query as the third parameter, you can just go ahead 
and add format if you need that.

When using media queries based on width, it's recommended to just use an integer for the minimum width as shown above. If you do,
power pack will be able to sort the sources so that the order is always correct (remember, the browser will pick the
_first_ `<source>` that it matches with, not the _most specific_ of the sources). 

But, you can also provide a full media query if you want. All of these examples are valid:

```
[image, 'myTransforms', 750]
[image, 'myTransforms', '(min-width: 750px)'] // same as the above
[image, 'myTransforms', '(max-width: 749px)'] // using max-width
[image, 'myTransforms', '(min-aspect-ratio: 16/9)'] // using aspect ratio maybe
[image, 'myTransforms', '(min-width: 1200px) and (orientation: landscape)'] // getting specific
[image, 'myTransforms', 'landscape'] // very nifty shortcut!

[image, 'myTransforms', 750, 'avif'] // and for format, you can do this
[image, 'myTransforms', 'avif'] // or this
```

In addition to the `sources` parameter, `pppicture` takes two additional parameters; `params`, which are 
additional parameters, and `config`, which override the default configuration. The following example
shows all the available parameters in effect, and an example of overriding a couple of config settings.

```
{{ pppicture(
    [
        [image, [1000, 2000, 16/9], 768],
        [imageMobile, [500, 1000, 1]
    ],
    {
        sizes: '(min-width: 768px) 33vw, 100vw',
        class: 'absolute full',
        loading: 'eager',
        decoding: 'async',
        alt: 'This is a custom alternative text!',
        defaults: { effects: { sharpen: true } },
        imagerOverrides: {
            transformer: 'craft'
        }
    }, 
    {
        lazysizes: true,
        placeholder: 'blurhash'
    }) }}
```

Most notably there is `sizes`, which you will have to supply in order for the browser to know what the intended size
of the image should be. It defaults to `100vw` by default, which is fine for full width images, but make sure you
customize this for other things. _Unless_ you use (the awesome) lazySizes, in which case it will be set to `auto`, and you'll 
never have to think about it again. 

The `class` parameter is for adding classes to the `<img>` tag, both for the normal one inside the `<picture>`, but 
also the fallback image inside the `<noscript>` tag if you're using lazySizes. If you want to add classes, or other 
attributes to the picture tag itself, use the [native `attr` filter](https://craftcms.com/docs/4.x/dev/filters.html#attr) 
on the output directly.

All parameters that end up as attributes in the markup, are passed through the same filters as when using the 
native `attr` filter. This means that you can pass strings to `class` and `filter`, but you can also use an 
array of classes (ie `class: ['absolute', 'full', shouldHaveSpecialClass ? 'special-class']`), or an object notation for 
styles (ie `style: { background: 'red' }`).

Also notice that this example uses the new quick syntax in Imager, which provides a very compact way to generate a
full source set, if all you need is a range of sizes. 

Let's conclude with a very simple example that outputs a responsive image with range of transforms from 1000px to 
2000px, in 16/9 format, with optional support for WebP and Avif.   

```
{{ pppicture(
    [
        [image, [1000, 2000, 16/9, 'avif'], 'avif'],
        [image, [1000, 2000, 16/9, 'webp'], 'webp'],
        [image, [1000, 2000, 16/9]]
    ]
) }}
```


### ppimg($image, $transform[, $params=[], $config=[]])

Outputs a single `<img>` tag. It is almost identical to `pppicture`, but takes `image` and `transform` parameters,
instead of a `sources` array. 

A simple example that creates a single `<img>` tag, with attributes based on the configuration:

```
{# Template code #}
{{ ppimg(image, [1000, 2000, 16/9]) }}

{# Resulting markup #}
<img src="..." srcset="..." width="1000" height="563" alt="" sizes="100vw" loading="lazy" decoding="auto" style="object-position: 50% 50%;">
```

`ppimg` takes the same parameters as `pppicture`:

```
{{ 
    ppimg(image, 'myNamedTransform', {
        sizes: '(min-width: 1024px) calc(100vw-80px), calc(100vw-40px)'
        class: 'absolute full',
        loading: 'eager'
    }, {
        lazysizes: true,
        placeholder: 'blurhash'
    }) 
}}
```

### ppplaceholder($image[, $output='attr', $type='dominantColor', $config=[]])

Outputs a placeholder, either as a full style attribute (when `output` is set to `attr`), or as a css style only (when 
`output` is set to `style`). By default, a dominant color background is created, but this can be changed
to any of the valid values for placeholder, `dominantColor`, `blurup` or `blurhash`.

```
<div class="relative w-full h-0 pb-[56.25%]" {{ ppplaceholder(image, 'attr', 'blurup') }}>
    {{ 
        ppimg(image, [1000, 2000, 16/9], { class: 'absolute full' })
    }}
</div>
```

_This is an alternative to the automatic placeholder functionality, which adds the placeholder to the `img` tag, and it doesn't
make sense to use both at the same time._

_Please note that using this method will result in a transformed image being created using the native `craft` transformer._

This, by the way, is a function that benefits from named parameters:

```
{{ ppplaceholder(image, type='blurup')
```

### pptransform($image, $transforms[, $defaults=null, $config=null])

Just a wrapper around `craft.imagerx.transformImage`, because that's alot of typing. It also respects the `transformSvgs` and
`transformAnimatedGifs` config settings, if these are set to `false`, the asset will be returned untransformed.

```
{% set transforms = pptransform(image, [1000, 2000]) %}
{{ transforms|srcset }}
```


## Configuring

You can configure the adapter by creating a file in your config folder called
`imager-x-power-pack.php`, and override settings as needed.

### altTextHandle [string]
_Default: `'alt'`_  
The name of the Asset field handle to be used for alternative text on the image tag.
Defaults to the built in `alt` field, but can be changed to a custom field.

### placeholder [string]
_Default: `''`_  
Possible values: `'', 'dominantColor', 'blurup', 'blurhash'`   
When enabled a css placeholder will be added to the image tag, and will be displayed until the
image is loaded.

_Please note that using using the placeholder functionality will result in a transformed image being created using
the native `craft` transformer._

### placeholderSize [int]
_Default: `16`_    
When using the `blurup` or `blurhash` style placeholders, this is the base size (width) of the small image
that is generated and used as the blurup. A higher value will create a more detailed placeholder,
but will increase the size of the base64 encoded image and your document size.

### blurupTransformParams [array]
_Default: `['effects' => ['blur' => true]]`_  
Extra parameters that are passed to Imager when transforming the image that is used as a blurup. A standard blur is added
by default to improve the visual quality.

### loading [string]
_Default: `'lazy'`_     
Sets the [loading strategy](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#loading) for the image tag.
You'd usually want this set to `'lazy'`, but for images that are candidates to be your[Largest Contentful Paint (LCP)](https://web.dev/articles/lcp)
element, you'd want to use `'eager'`.

### decoding [string]
_Default: `'auto'`_     
Sets the [decoding hint](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#decoding) for the image tag.

### objectPosition [bool]
_Default: `true`_   
When enabled (default) an `object-position` CSS style with the focal point from Craft, will automatically be added to the
image tag. This will ensure that the focal point of the image is taken into consideration when the image is used to cover
a wrapper with a different aspect ratio. The styles for the image and wrapper is up to you to add.

### defaultTransformParams [array]
_Default: `[]`_     
Default transforms that are merged into all transform.

_If you use auto generation, make sure to include these defaults explicitely in your named transforms. Failing to do so will
render auto generation useless._

### transformSvgs [bool]
_Default: `false`_     
When disabled (default), SVGs will not be transformed by Imager, and merely output as-is when passed to `pppicture` or `ppimg`. 
Attributes like `width` and `height` will be added based on the source file.

_This could lead to unexpected results depending on your use-case, so consider whether or not this works for you, or if
you need to handle this outside of the power pack._

### transformAnimatedGifs [bool]
_Default: `false`_     
When disabled (default), animated GIFs will not be transformed by Imager, and merely output as-is when passed to `pppicture` or `ppimg`. 
Attributes like `width` and `height` will be added based on the source file.

_This could lead to unexpected results depending on your use-case, so consider whether or not this works for you, or if
you need to handle this outside of the power pack._

### lazysizes [bool]
_Default: `false`_     
When enabled the markup generated will be customized to fit with the (awesome) [lazySizes](https://github.com/aFarkas/lazysizes)
library. `data-sizes` will be set to `auto`, the source sets will be put into `data-srcset` attributes, and a `<noscript>` tag
with a fallback image will be automatically created.

### lazysizesClass [string]

_Default: `'lazyload'`_     
The name of the class that lazySizes is configured to use.

### autoloadLazysizes [bool]

_Default: `false`_     
When enabled the lazySizes bundle specified in `lazysizesURL` will automatically be loaded when needed.

### lazysizesURL [string]

_Default: `'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js'`_     
The URL to the lazySizes bundle that will be loaded if `autoloadLazysizes` is set to `true`. 

(We recommend including lazySizes in your own JS bundles though, that way you are in a bit more control) 


---

Price, license and support
---
The plugin is released under the MIT license. It requires Imager X, which is a commercial plugin [available in the Craft plugin store](https://plugins.craftcms.com/imager-x). 
