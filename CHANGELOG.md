# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

[Unreleased]: https://github.com/tommyknocker/chain/compare/v1.2.0...HEAD

## [1.2.0] - 2025-01-27

### Added
- **Enhanced Conditional Methods**: Added `whenAll()`, `whenAny()`, and `whenNone()` for complex condition checking
- **Timeout Protection**: Added `timeout()` method for protecting against slow operations
- **Configuration System**: Added `ChainConfig` class with predefined configurations (default, performance, development)
- **Extension System**: Added `ChainExtensionInterface` for monitoring and logging chain operations
- **Method Caching**: Implemented method existence caching for improved performance
- **Specific Exception Types**: Added dedicated exception classes:
  - `ChainException` - Base exception class
  - `ChainMethodNotFoundException` - When method doesn't exist
  - `ChainInvalidOperationException` - For invalid operations
  - `ChainTimeoutException` - For timeout errors
- **Enhanced Error Handling**: Improved `rescue()`, `catch()`, and `retry()` methods with better type safety
- **Comprehensive Examples**: Added `advanced-features.php` demonstrating all new functionality
- **Professional Development Tools**: Added comprehensive scripts for development workflow:
  - `composer quality` - Run all quality checks
  - `composer test:coverage` - Test coverage reports
  - `composer test:ci` - CI-ready test output
  - `composer examples` - Test all examples
  - `composer release` - Automated release management

### Changed
- **Code Organization**: Extracted functionality into traits for better Single Responsibility Principle compliance:
  - `ErrorHandlingTrait` - Error handling methods
  - `DebuggingTrait` - Debugging utilities
  - `ConditionalTrait` - Enhanced conditional methods
- **Type Safety**: Enhanced PHPDoc comments and type hints throughout the codebase
- **Code Style**: Applied PSR-12 standards with PHP CS Fixer
- **Static Analysis**: Added PHPStan with level 8 analysis
- **Documentation**: Updated README.md with comprehensive examples and new features

### Fixed
- **Russian Comments**: Replaced Russian comments with English in fixture files
- **Test Coverage**: Added comprehensive test suite covering all new functionality
- **Method Resolution**: Fixed namespace resolution issues in test files

### Security
- **Input Validation**: Enhanced input validation in conditional methods
- **Exception Handling**: Improved exception handling with specific exception types

## [1.1.0] - 2024-12-15

### Added
- **Functional Pipelines**: Added `pipe()` method for functional programming patterns
- **Container Integration**: Added PSR-11 container support with `change()` method
- **Branching Support**: Added `clone()` method for immutable chain branching
- **Debugging Utilities**: Added `dump()` and `dd()` methods for debugging
- **Error Resilience**: Added `rescue()`, `catch()`, and `retry()` methods
- **Collection Iteration**: Added `each()` method for iterating over collections

### Changed
- **API Consistency**: Improved method chaining consistency across all methods
- **Documentation**: Enhanced examples and documentation

## [1.0.0] - 2024-11-01

### Added
- **Core Functionality**: Initial release with basic method chaining
- **Conditional Execution**: Added `when()` and `unless()` methods
- **Value Extraction**: Added `get()` and `value()` methods
- **Side Effects**: Added `tap()` method for side effects
- **Object Transformation**: Added `map()` method for object transformation
- **Basic Examples**: Initial set of examples demonstrating core functionality

[1.2.0]: https://github.com/tommyknocker/chain/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/tommyknocker/chain/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/tommyknocker/chain/tree/v1.0.0
