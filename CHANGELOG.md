# Changelog

All notable changes to this project will be documented in this file.

## [1.0.2] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.0.1] - 2026-03-16

### Changed
- Standardize composer.json: add homepage, scripts
- Add Development section to README

## [1.0.0] - 2026-03-13

### Added

- `EnvValidator::required()` for defining required environment variables
- `EnvValidator::schema()` for defining variables with type rules
- `PendingValidation` fluent builder with `optional()`, `defaults()`, and `type()` methods
- Type validation for `string`, `int`, `float`, `bool`, `url`, `email`, and `json`
- `validate()` returns a `ValidationResult` with `passed`, `missing`, `invalid`, and `warnings`
- `validateOrFail()` throws `EnvValidationException` on failure
- Full test suite
