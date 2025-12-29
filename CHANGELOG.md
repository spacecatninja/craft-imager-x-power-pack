# Imager X Power Pack Changelog

## 1.1.0 - 2025-12-29

### Fixed
- Fixed an issue where an error would be thrown for external SVGs, and reduced redundant sources if transformSvgs is false and the source image is the same for all sources (fixes #7).

## 1.0.5 - 2024-09-24

### Changed
- Changed behaviour of `ppplaceholder` to allow `null` values for `image` to be more in line with how Imager works.

## 1.0.4 - 2024-08-09

### Changed
- Changed behaviour of `ppimg` to allow `null` values for `image` to be more in line with how Imager works.

### Fixed
- Fixed an issue that would occur if width and/or height of an image/transform could be deducted (for instance for `imgix` transformer with external urls and `getExternalImageDimensions` set to false).

## 1.0.3 - 2024-05-14

### Fixed
- Fixed incorrect naming of styles.

## 1.0.2 - 2024-05-13

### Added
- Added support for `blurupTransformParams` config setting, it defaults to a blur effect.

## 1.0.1 - 2024-05-13

### Added
- Added support for attribute handling equal to the attr filter.

### Changed
- Changed handling of placeholder, the default `craft` transformer is always used for the base image (fixes #1).

## 1.0.0 - 2024-05-03

### Added
- Initial public release
