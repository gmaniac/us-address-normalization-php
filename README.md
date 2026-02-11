# US Address Normalization

A PHP library to normalize and hash US addresses without requiring an external service. This is the PHP implementation of [us-address-normalization](https://github.com/gmaniac/us-normalize-address) (TypeScript).

Based on the Perl module [Geo::StreetAddress::US](https://metacpan.org/pod/Geo::StreetAddress::US) originally written by Schuyler D. Erle.

## Purpose

The main purpose of this package is as the first layer of address normalization and standardization. Recommended use is to pre-parse/normalize an address and compare to an existing cache/record set using the hash functions.

## Limitations

This is a basic normalizer. It realistically only handles US-based addresses, and should not be considered dependable for strict address-to-address comparison. **This normalizer does not verify the validity of the address!** If you are dependent on accurate addresses, you should use a 3rd party service to verify addresses.

## Installation

```bash
composer require gmaniac/us-address-normalization
```

## Usage

### Normalizing

```php
<?php
use UsAddressNormalization\Normalizer;

$normalizer = new Normalizer();
$address = $normalizer->parse('204 southeast Smith Street Harrisburg, or 97446');

$address->getAddressComponents();
/* Returns:
[
    "number" => "204",
    "street" => "Smith",
    "street_type" => "St",
    "unit" => "",
    "unit_prefix" => "",
    "suffix" => "",
    "prefix" => "SE",
    "city" => "Harrisburg",
    "state" => "OR",
    "postal_code" => "97446",
    "postal_code_ext" => null,
    ...
] */

$address->toString();
// "204 SE Smith St, Harrisburg, OR 97446"
```

### Comparing

```php
<?php
use UsAddressNormalization\Normalizer;

$normalizer = new Normalizer();

$address1 = $normalizer->parse('204 southeast Smith Street Harrisburg, or 97446');
$address2 = $normalizer->parse('204 SE Smith St. Harrisburg, Oregon 97446');
$address3 = $normalizer->parse('207 SE Smith St. Harrisburg, Oregon 97446');

$address1->is($address2); // true
$address2->is($address3); // false
$address1->isSameStreet($address3); // true

// Compare hashes directly
$address1->getFullHash() === $address2->getFullHash(); // true
```

### Hashing

Three hash methods are available for different comparison needs:

```php
<?php
use UsAddressNormalization\Normalizer;

$normalizer = new Normalizer();
$address = $normalizer->parse('1234 Main St NE, Minneapolis, MN 55401');

// Hash excluding ZIP code (recommended for long-term matching since ZIPs can change)
$address->getHash();

// Full hash including ZIP code
$address->getFullHash();

// Street-only hash (for matching addresses on the same street)
$address->getStreetHash();
```

For consistent hashing when starting with pre-validated address components:

```php
<?php
use UsAddressNormalization\SimpleAddress;

$address = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');
$address->getHash();     // hash minus zip
$address->getFullHash(); // hash including zip

// Static factory methods:
SimpleAddress::hashFromParts('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');
SimpleAddress::fullHashFromParts('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');
```

### Formatting

```php
<?php
use UsAddressNormalization\Normalizer;

$normalizer = new Normalizer();
$address = $normalizer->parse('204 southeast Smith Street Harrisburg, or 97446');

$address->toString();
// or
(string) $address;
// "204 SE Smith St, Harrisburg, OR 97446"

$address->toArray();
// ['204 SE Smith St', 'Harrisburg, OR 97446']
```

### Parsing from Components

```php
<?php
use UsAddressNormalization\Normalizer;

$normalizer = new Normalizer();
$address = $normalizer->parseFromComponents(
    '1234 Main St SE',
    null,           // address line 2
    'Minneapolis',
    'MN',
    '55401'
);
```

### Configuration Options

```php
<?php
use UsAddressNormalization\Normalizer;

// Strict mode (default: true)
// When disabled, unparseable addresses return SimpleAddress instead of false
$normalizer = new Normalizer(['strict_mode' => false]);

// Custom lookup tables
$normalizer = new Normalizer();
$normalizer->setDirectionalLookup(['north' => 'N', 'south' => 'S', ...]);
$normalizer->setStateCodesLookup(['california' => 'CA', ...]);
$normalizer->setStreetTypesLookup(['street' => 'st', 'avenue' => 'ave', ...]);
```

## Running Tests

```bash
composer install
composer test
```

## Related Projects

- [us-address-normalization](https://github.com/gmaniac/us-normalize-address) - TypeScript implementation

## Attribution

This project builds upon work from:
- [zerodahero/address-normalization](https://github.com/zerodahero/address-normalization)
- [khartnett/address-normalization](https://github.com/khartnett/address-normalization)
- [Geo::StreetAddress::US](https://metacpan.org/pod/Geo::StreetAddress::US) (Perl)

## License

MIT
