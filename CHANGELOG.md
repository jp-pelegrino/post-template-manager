# Changelog

All notable changes to this project will be documented in this file.

## [1.0.3-beta](https://github.com/jp-pelegrino/post-template-manager/compare/v1.0.2-beta...v1.0.3-beta) (2025-07-24)


### Bug Fixes

* resolving issues with REST API ([c72c8e9](https://github.com/jp-pelegrino/post-template-manager/commit/c72c8e992dee41ba7e9047edbe44de3e48006439))


### Miscellaneous Chores

* **release:** 1.0.3-beta - config and workflow fixes ([bf50010](https://github.com/jp-pelegrino/post-template-manager/commit/bf50010805a52013a11619610e99669c0e0960f1))
* **release:** 1.0.3.1-beta - config and workflow fixes ([b41f9a6](https://github.com/jp-pelegrino/post-template-manager/commit/b41f9a6643cb6d2b699eb662c40c418f8ab0452d))

## [1.0.3-beta] - 2025-07-25
### Changed
- Manual config and changelog edits for improved Release Please compatibility and workflow reliability.

## [1.0.2-beta](https://github.com/jp-pelegrino/post-template-manager/compare/v1.0.1-beta...v1.0.2-beta) (2025-07-24)


### Bug Fixes

* issues with the code ([20d544d](https://github.com/jp-pelegrino/post-template-manager/commit/20d544d860a3453140bcd3cf9b58a3c24220701b))
* issues with the code actions ([86c9fdf](https://github.com/jp-pelegrino/post-template-manager/commit/86c9fdf5d0c14481246eb6700625dc3228c22e3d))
## [1.0.2-beta] - 2025-07-25
### Fixed
- Added valid .release-please-config.json for Release Please compatibility.
- Removed unsupported exists() function from workflow.
### Changed
- Improved version automation and workflow reliability for plugin releases.

## [1.0.1-beta] - 2025-07-24
### Fixed
- REST API permission error when loading template content from the sidebar.
- Improved plugin ZIP packaging for direct WordPress installation.

### Changed
- Script enqueue version now automatically syncs with plugin header version for full release automation.
- GitHub Actions workflow fixed to properly upload CHANGELOG.md without using unsupported exists() function.

### Added
- Automated GitHub Actions workflow for packaging and releasing the plugin.
- Automatic changelog and release notes generation via release-please.

## [1.0.0-beta] - 2025-07-22
### Added
- Initial release of Post Template Manager.
- Custom post type: "Post Template".
- Gutenberg editor sidebar integration for template selection.
- REST API endpoint for fetching template content.
- Support for featured images in templates.
