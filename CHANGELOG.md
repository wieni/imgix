# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [9.1.7] - 2021-07-30
### Removed
- Remove the `s3_has_prefix` option. Since we're now using `file_create_url`, the bucket is automatically stripped from
  the path.

## [9.1.6] - 2021-07-30
### Fixed
- Automatically activate the image toolkit

## [9.1.5] - 2021-07-29
### Fixed
- Only override image style entity class if toolkit is enabled

## [9.1.4] - 2021-07-28
### Fixed
- Migrate field widgets & formatters from imgix to image

## [9.1.3] - 2021-07-28
### Fixed
- Migrate fields from imgix to image field types

## [9.1.2] - 2021-07-27
### Fixed
- Fix the image module dependency not being installed

## [9.1.1] - 2021-07-26
### Fixed
- Check existence of plugin definitions before overriding
- Fix config schema

## [9.1.0] - 2021-07-24
Check [`UPGRADING.md`](UPGRADING.md) for instructions.

### Added
- Add the _Path prefix_ option
- Add support for Google Cloud Storage sources

### Changed
- Deprecated the _S3 bucket has prefix_ option. Use the new _Path prefix_ option instead.

## [9.0.0] - 2021-07-07
Check [`UPGRADING.md`](UPGRADING.md) for instructions.

### Added
- Add `ImgixImageStyleFactoryInterface` to help with transitioning to image styles

### Removed
- Remove Imgix presets in favour of core image styles
- Remove field type, widget & formatter
- Remove `imgix_image` theme hook
- Remove the `administer imgix` permission
- Remove the `mapping_url` setting
- Remove `ImgixManagerInterface::getPresets`
- Remove `ImgixManagerInterface::getMappingTypes`. Use `ImgixToolkitInterface::getMappingTypes` instead.
- Remove `ImgixManager::SOURCE_S3`, `ImgixManager::SOURCE_FOLDER` & `ImgixManager::SOURCE_PROXY`. Use
  `ImgixToolkitInterface` constants instead.
- Remove `ImgixManagerInterface::SUPPORTED_EXTENSIONS`. Use `ImgixToolkit::getSupportedExtensions` instead.
- Remove _Imgix Browser_ submodule

## [8.6.1] - 2021-01-28
### Changed
- Fix possible TypeError

## [8.6.0] - 2020-12-08
### Changed
- Update field widget to function/look more like the default image widget

### Removed
- Remove unused template

## [8.5.2] - 2020-11-30
### Fixed
- Fix auto upload not working in Imgix widget

## [8.5.1] - 2020-07-23
### Changed
- Update drupal/core version requirement

## [8.5.0] - 2020-02-24
### Changed
- Move supported file extensions to constant
- Add file & imgix dependencies to the imgix_browser module
- Add file dependency to the imgix module
- Change PHP version requirement to 7.1
- Update module description
- Update README
- Code cleanup

## [8.4.0] - 2020-02-24
### Added
- Add coding standard fixers
- Add license
- Add changelog
- Add issue & pull request templates
- Add mp4 & webm to supported file extensions

### Changed
- Update .gitignore
- Fix code style
- Normalize composer.json
- Add argument & return type hints

### Fixed
- Add back required core key

## [8.3.2] - 2019-11-22
### Changed
- Increase drupal/core version constraint to support version 9

### Removed
- Remove dependency on system module

## [8.3.1] - 2019-11-13
### Added
- Add jfif file extension

## [8.3.0] - 2019-10-16
### Added
- Add .gitignore
- Add php & drupal/core composer dependencies

### Changed
- Remove deprecated code usages

## [8.2.8] - 2019-02-15
### Added
- Add svg to allowed file formats

## [8.2.7] - 2018-08-09
### Fixed
- Fix notice 'Undefined index: max_resolution'

## [8.2.6] - 2018-01-17
### Added
- Add S3 bucket support

## [8.2.5] - 2017-10-18
### Fixed
- Quote strings that start with '@' in yaml files

## [8.2.4] - 2017-07-03
### Fixed
- Fix config overrides not coming through

## [8.2.3] - 2017-06-01
### Added
- Add a preset chooser to the form display widget
- Add `getImgixUrlByPreset` method to `ImgixManagerInterface`

## [8.2.2] - 2017-05-24
### Changed
- Fix code style

## [8.2.1] - 2017-05-23
### Added
- Add system module dependency

### Changed
- Fix code style

### Fixed
- Fix issue where preview is shown twice

## [8.1.7] - 2017-05-16
### Fixed
- Fix undefined index 'external_cdn'

## [8.1.6] - 2017-05-16
### Added
- Add the option to use a CDN in the generated urls

## [8.1.5] - 2017-05-03
### Added
- Add preview to the imgix widget

### Changed
- Change the title and description of the description form field

## [8.1.4] - 2017-03-27
### Added
- Add the `imgix_browser` submodule, providing a form and entity browser
  widget

### Changed
- Change the module package from 'Services' to 'Media'

## [8.1.3] - 2017-01-28
### Changed
- Make the Twig function return a placeholder if no image is provided

## [8.1.2] - 2017-01-10
### Added
- Add convenient getters for the file, caption and title
- Add Twig filter

### Fixed
- Fix implementation of the Twig function

## [8.1.1] - 2017-01-09
Initial Drupal 8 release

## [7.x-1.3] - 2016-08-22
### Fixed
- Fix warnings

## [7.x-1.2] - 2016-08-18
### Changed
- Replace current domain with given Mapping URL
- Reorder fields in the settings page
- Rename some variables

## [7.x-1.1] - 2016-07-23
### Added
- Add uninstall hook to delete all imgix variables

## [7.x-1.0] - 2016-07-23
Initial Drupal 7 release
