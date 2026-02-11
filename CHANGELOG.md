# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-11

### Added

- Initial release as `gmaniac/us-address-normalization`
- PHP implementation matching [us-address-normalization](https://github.com/gmaniac/us-normalize-address) TypeScript library
- `Normalizer` class for parsing address strings
- `Address` class with component getters and hash methods
- `SimpleAddress` class for hashing pre-validated addresses
- Three hash methods: `getHash()`, `getFullHash()`, `getStreetHash()`
- Address comparison via `is()` and `isSameStreet()` methods
- Configurable lookup tables for directionals, states, and street types
- Strict mode toggle for handling unparseable addresses
- Two-pass parsing for comma-less multi-word cities
- Comprehensive test suite with 98%+ line coverage

### Fixed

- Multi-word cities (e.g., "New York", "Salt Lake City") no longer incorrectly parsed as unit numbers
- Dotted directional abbreviations (e.g., "S.W.", "N.E.") now correctly normalize to "SW", "NE"
- Comma-less addresses with multi-word cities now parse correctly

### Attribution

Based on work from:
- [zerodahero/address-normalization](https://github.com/zerodahero/address-normalization)
- [khartnett/address-normalization](https://github.com/khartnett/address-normalization)
- [Geo::StreetAddress::US](https://metacpan.org/pod/Geo::StreetAddress::US) (Perl)
