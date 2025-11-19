# Changelog

All notable changes to `laravel-resource-permissions` will be documented in this file.

## [0.3.0] - 2025-11-19

### Breaking Changes
- Renamed `HasAssignedUsers` trait to `HasAssignedModels`.
- Removed deprecated methods in `HasAssignedUsers` trait: `getAssignedUsers`, `hasUserAssigned`, `hasAllUsersAssigned`, `hasAnyUserAssigned`.
- Removed deprecated methods in `ModelHasResourceAndPermission` model: `user`, `forUserAndResource`, `isUserAssignedToResource`, `getUsersForResource`.
- Removed backward compatibility class aliases.

### Added
- Added generic model methods to `HasAssignedUsers` trait.

## [0.2.1] - 2025-11-13

### Changed
- Marked deprecated methods for removal in 0.3.0.

### Documentation
- Updated documentation for polymorphic model support.

## [0.2.0] - 2025-11-12

### Added
- Refactored `user_id` to polymorphic `model_id`/`model_type` to support any model (not just User).
- Added `HasAssignedUsers` trait and user filtering methods.
- Added static helper methods for simplified permission checks.
- Renamed model to `ModelHasResourceAndPermission`.
- Changed default table name to `model_has_resource_and_permissions`.

## [0.1.0] - 2025-11-12

### Added
- Initial package implementation.
