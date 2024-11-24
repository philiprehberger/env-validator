# Changelog

All notable changes to `env-validator` will be documented in this file.

## [Unreleased]

## [1.2.0] - 2026-03-31

### Added
- Dependency rules: `requiredIf()`, `requiredUnless()`, and `dependsOn()` for conditional variable requirements
- .env file parsing via `EnvValidator::fromFile()` for validating env files without loading them
- Environment profiles via `EnvValidator::profile()` and `validateProfile()` for named validation schemas

## [1.1.1] - 2026-03-31

### Changed
- Standardize README to 3-badge format with emoji Support section
- Update CI checkout action to v5 for Node.js 24 compatibility
- Add GitHub issue templates, dependabot config, and PR template

## [1.1.0] - 2026-03-22

### Added
- `ip`, `ipv4`, `ipv6` validation types
- `custom()` method for user-defined validation rules with callable validators
- `enum()` method for validating environment values against backed enum cases

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
