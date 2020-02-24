imgix
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/imgix/v/stable)](https://packagist.org/packages/wieni/imgix)
[![Total Downloads](https://poser.pugx.org/wieni/imgix/downloads)](https://packagist.org/packages/wieni/imgix)
[![License](https://poser.pugx.org/wieni/imgix/license)](https://packagist.org/packages/wieni/imgix)

> Render Drupal 8 images through Imgix, a real-time image processing service and CDN

## Why?
- **Build Imgix urls** for any file entity using a set of parameters or a configured preset
- **Custom field type**, widget & formatter with optional title & description fields

## Installation

This package requires PHP 7.1 and Drupal 8.7 or higher. It can be
installed using Composer:

```bash
 composer require wieni/imgix
```

## How does it work?

### Configuring the module
Users with the `administer imgix` permission are allowed to configure the module
 by navigating to `/admin/config/media/imgix`. The same permission is needed
 to manage presets, predefined sets of Imgix parameters that can be used to 
 render images in a consistent manner. These presets can be managed at 
 `/admin/config/media/imgix/presets`.

### Rendering images
There are several ways to render images through Imgix using this module:

#### Field formatter
When using the `imgix` field type, you can use the provided field formatter
 to render images on your page using a certain preset.
 
#### Twig function
When in context of a Twig template, you can use the `imgix` function or
 filter by passing a preset along the file entity. 
 
#### Service
In any other context, you can use the [`ImgixManagerInterface::getImgixUrl`](src/ImgixManagerInterface.php) or
[`ImgixManagerInterface::getImgixUrlByPreset`](src/ImgixManagerInterface.php) functions to build Imgix urls.

### `imgix_browser` submodule
The `imgix_browser` submodule provides a widget to be used in combination 
with the [Entity Browser](https://www.drupal.org/project/entity_browser) module.

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.

## License
Distributed under the MIT License. See the [LICENSE](LICENSE) file
for more information.
