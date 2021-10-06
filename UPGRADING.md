# Upgrade Guide

This document describes breaking changes and how to upgrade. For a
complete list of changes including minor and patch releases, please
refer to the [`CHANGELOG`](CHANGELOG.md).

If you're upgrading from the [Wieni fork](https://github.com/wieni/imgix) over at Github, you should check out [`UPGRADING_WIENI.md`](UPGRADING_WIENI.md).

## 9.0.0
### Image toolkit
Imgix is now an image toolkit. The new image toolkit is enabled automatically after updating the module.

Image styles should continue to work, additionally you now have some new image operations to work with:
- _Apply Imgix parameter_: apply raw Imgix parameters as documented in the 
  [Imgix documentation](https://docs.imgix.com/apis/rendering).
- _Change the quality_: controls the output quality of lossy file formats 

### `administer imgix` permission
The `administer imgix` permission is removed. The `administer image styles` permission from the image core module should
be granted instead. This change is done automatically.

### `ImgixStyles` service and `imgix_get_url` function
The `ImgixStyles` service and `imgix_get_url` function are removed. Since this is code isn't specific to the Imgix toolkit. You can use the
`image_utilities` module for a more generic replacement to this service. 

### Settings form
The settings form at `/admin/config/media/imgix/settings` is removed. You can use the toolkit settings form at 
`/admin/config/media/image-toolkit` instead.
