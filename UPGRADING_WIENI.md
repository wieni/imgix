# Upgrade Guide

This document describes breaking changes and how to upgrade. For a
complete list of changes including minor and patch releases, please
refer to the [`CHANGELOG`](CHANGELOG.md).

If you're upgrading from the [official version](https://www.drupal.org/project/imgix) over at Drupal.org, you should 
check out [`UPGRADING.md`](UPGRADING.md).

## 9.1.0
The _S3 bucket has prefix_ option is deprecated. The new _Path prefix_ option should be used instead. The difference is 
that when the first option has a _truthy_ value, the first part of the path is removed. With the new option, you have to
define the string prefix that should be removed. This allows you to strip multiple parts of the path if needed.

## 9.0.0
### Settings form
The settings form at `/admin/config/media/imgix` is removed. You can use the toolkit settings form at
`/admin/config/media/image-toolkit` instead.

### Presets
Presets functionality is removed in favour of core image styles. All existing presets are automatically converted to 
image styles. You just have to add labels to the newly created image styles, since existing presets don't have labels.
The presets form is replaced by the image style interface at `/admin/config/media/image-styles`.

### Field type, widget & formatter
The `imgix` field type is removed. Use the `image` field type from the `image` core module instead. An example update 
hook that can be used to migrate your fields can be found 
[here](https://github.com/wieni/wmmedia/blob/feature/v2/remove-imgix-dependency/wmmedia.install#L164).

The `imgix` field widget and field formatter are also removed, since they only supported `imgix` fields. Use the _Image_
widget and formatter from the `image` core module instead.

### `imgix_image` theme
The `imgix_image` theme hook has been removed. Use `image_style` from the `image` core module instead.

```php
$elements['image'] = [
    '#theme' => 'image_style',
    '#style_name' => 'thumbnail',
    '#uri' => $file->getFileUri(),
];
```

### `administer imgix` permission
The `administer imgix` permission is removed. The `administer image styles` permission from the image core module should
be granted instead. This change is done automatically.

### Twig extension
The Twig extension is deprecated. Also, the fallback placeholder image the extension would return is removed.

### `imgix.settings` config
The `mapping_url` option has been removed since it wasn't used. This change is done automatically.

### `ImgixManager` service
- `ImgixManagerInterface::getImgixUrl` is deprecated without replacement.
- `ImgixManagerInterface::getImgixUrlByPreset` is deprecated. `ImageStyleInterface::buildUrl` should be used instead
- `ImgixManagerInterface::getPresets` is removed without replacement.
- `ImgixManagerInterface::getMappingTypes` is moved to `ImgixToolkitInterface`
- `ImgixManager::SOURCE_S3`, `ImgixManager::SOURCE_FOLDER` & `ImgixManager::SOURCE_PROXY` are moved to `ImgixToolkitInterface`
- `ImgixManagerInterface::SUPPORTED_EXTENSIONS` is removed. `ImgixToolkit::getSupportedExtensions` should be used instead.

### _Imgix Browser_ submodule
The _Imgix Browser_ submodule is removed. If you're still using the entity browser and/or field widget, you should copy
the submodule into your project.
