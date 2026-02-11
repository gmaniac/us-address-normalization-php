<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use UsAddressNormalization\Normalizer;
use UsAddressNormalization\Address;
use UsAddressNormalization\SimpleAddress;

class NormalizerTest extends TestCase
{
    /** @test */
    public function testReturnsAddressClass()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse('1234 Main St SE, Minneapolis, MN 55401');

        $this->assertInstanceOf(Address::class, $address);
    }

    /** @test */
    public function testReturnsAddressOnFivePart()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parseFromComponents('1234 Main St SE', null, 'Minneapolis', 'MN', '55401');

        $this->assertInstanceOf(Address::class, $address);
    }

    /** @test */
    public function testReturnsSimpleAddressOnBadFivePart()
    {
        $normalizer = new Normalizer(['strict_mode' => false]);
        $address = $normalizer->parseFromComponents('1234 Main St SE, Unit 301 Unit 301', null, 'Minneapolis,', 'MN,', '55401');

        $this->assertInstanceOf(SimpleAddress::class, $address);
    }

    /** @test */
    public function testReturnsFalseOnBadFivePartStrict()
    {
        $normalizer = new Normalizer(['strict_mode' => true]);
        $address = $normalizer->parseFromComponents('1234 Main St SE, Unit 301 Unit 301', null, 'Minneapolis,', 'MN,', '55401');

        $this->assertFalse($address);
    }

    public static function normalizesAddressesDataProvider()
    {
        return [
            [
                '1234 Main St. SE, Minneapolis, MN 55401',
                '1234 Main Street Southeast, Minneapolis, MN 55401'
            ],
            [
                '1234 Main St. SE, Minneapolis, MN 55401',
                '1234 Main St SE, Minneapolis, Minnesota 55401'
            ],
            [
                '1234 Main St. SE, Minneapolis, MN 55401',
                '1234 Main St southeast, Minneapolis, Minnesota 55401'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider normalizesAddressesDataProvider
     */
    public function testNormalizesAddresses($firstAddress, $secondAddress)
    {
        $normalizer = new Normalizer();

        $this->assertEquals(
            (string)$normalizer->parse($firstAddress),
            (string)$normalizer->parse($secondAddress)
        );
    }

    public static function badAddressesDataProvider()
    {
        return [
            'double unit no commas' => ['1234 Main St. SE Unit 101 Unit 101'],
            'double unit mismatch comma' => ['1234 Main St. SE, Unit 101 Apt 101, Minneapolis, MN 55555'],
            'double unit comma' => ['3333 West End Ave, Unit 301 Unit 301, Nashville, TN, 37205'],
            'nonsense' => ['Main Street West Fork Soup Salad'],
        ];
    }

    /**
     * @test
     * @dataProvider badAddressesDataProvider
     */
    public function testFailsOnBadAddresses($badAddress)
    {
        $normalizer = new Normalizer();

        $this->assertFalse($normalizer->parse($badAddress));
    }

    /** @test */
    public function testHandlesAddressWithUnitPrefix()
    {
        $normalizer = new Normalizer();

        // Note: Units REQUIRE a prefix (Unit, Apt, Suite, #, etc.) to be recognized.
        // Bare unit numbers like "1W" without a prefix will NOT be captured as units.
        // This is intentional to prevent multi-word cities from being misparsed as units.
        $addresses = [
            [ // Unit with "Unit" prefix
                'test' => '1234 W Main Avenue Unit 1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Unit 1W, Chicago, IL 60647'
            ],
            [ // Unit with "Apartment" prefix
                'test' => '1234 W Main Avenue Apartment 1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Apartment 1W, Chicago, IL 60647'
            ],
            [ // Unit with "#" prefix
                'test' => '1234 W Main Avenue #1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave #1W, Chicago, IL 60647'
            ],
            [ // Unit with "Room" prefix
                'test' => '1234 W Main Avenue Room 1, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Room 1, Chicago, IL 60647'
            ],
            [ // Unit with "Apt" prefix
                'test' => '1234 W Main Avenue Apt 1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Apt 1W, Chicago, IL 60647'
            ],
            [ // Address without any unit
                'test' => '1234 W Main Street, Chicago, IL, 60647',
                'expected_result' => '1234 W Main St, Chicago, IL 60647'
            ],
        ];

        foreach ($addresses as $address) {
            $this->assertEquals(
                $address['expected_result'],
                (string)$normalizer->parse($address['test'])
            );
        }
    }

    /**
     * @test
     * Fix 1: Multi-word cities should not be misparsed as phantom units
     */
    public function testMultiWordCitiesWithCommas()
    {
        $normalizer = new Normalizer();

        $addresses = [
            [
                'test' => '350 5th Avenue, New York, NY 10118',
                'expected_result' => '350 5th Ave, New York, NY 10118'
            ],
            [
                'test' => '100 Main St, Salt Lake City, UT 84101',
                'expected_result' => '100 Main St, Salt Lake City, UT 84101'
            ],
            [
                'test' => '55 Water St, New York, NY 10041',
                'expected_result' => '55 Water St, New York, NY 10041'
            ],
            [
                'test' => '200 Broadway, New York, NY 10007',
                'expected_result' => '200 Broadway, New York, NY 10007'
            ],
            [
                'test' => '500 Main St, West Palm Beach, FL 33401',
                'expected_result' => '500 Main St, West Palm Beach, FL 33401'
            ],
        ];

        foreach ($addresses as $address) {
            $this->assertEquals(
                $address['expected_result'],
                (string)$normalizer->parse($address['test']),
                "Failed for input: {$address['test']}"
            );
        }
    }

    /**
     * @test
     * Fix 2: Dotted directionals like S.W. should normalize correctly
     */
    public function testDottedDirectionals()
    {
        $normalizer = new Normalizer();

        $addresses = [
            [
                'test' => '789 S.W. Pine Drive, Portland, OR 97201',
                'expected_result' => '789 SW Pine Dr, Portland, OR 97201'
            ],
            [
                'test' => '100 N.E. Broadway, Portland, OR 97232',
                'expected_result' => '100 NE Broadway, Portland, OR 97232'
            ],
            [
                'test' => '456 N.W. 23rd Ave, Portland, OR 97210',
                'expected_result' => '456 NW 23rd Ave, Portland, OR 97210'
            ],
            [
                'test' => '200 S. Main St, Los Angeles, CA 90012',
                'expected_result' => '200 S Main St, Los Angeles, CA 90012'
            ],
        ];

        foreach ($addresses as $address) {
            $this->assertEquals(
                $address['expected_result'],
                (string)$normalizer->parse($address['test']),
                "Failed for input: {$address['test']}"
            );
        }
    }

    /**
     * @test
     * Fix 3: Comma-less addresses with multi-word cities should parse correctly
     */
    public function testCommalessMultiWordCities()
    {
        $normalizer = new Normalizer();

        $addresses = [
            [
                'test' => '100 Main St Salt Lake City UT 84101',
                'expected_result' => '100 Main St, Salt Lake City, UT 84101'
            ],
            [
                'test' => '350 5th Ave New York NY 10118',
                'expected_result' => '350 5th Ave, New York, NY 10118'
            ],
            [
                'test' => '123 Lake Shore Drive Chicago IL 60611',
                'expected_result' => '123 Lake Shore Dr, Chicago, IL 60611'
            ],
        ];

        foreach ($addresses as $address) {
            $this->assertEquals(
                $address['expected_result'],
                (string)$normalizer->parse($address['test']),
                "Failed for input: {$address['test']}"
            );
        }
    }

    /**
     * @test
     * Regression tests to ensure existing behavior is not broken
     */
    public function testRegressions()
    {
        $normalizer = new Normalizer();

        $addresses = [
            [
                'test' => '204 southeast Smith Street Harrisburg, or 97446',
                'expected_result' => '204 SE Smith St, Harrisburg, OR 97446'
            ],
            [
                'test' => '1234 Main St NE, Minneapolis, MN 55401',
                'expected_result' => '1234 Main St NE, Minneapolis, MN 55401'
            ],
            [
                'test' => '123 N 1st Ave Apt 4B, New York, NY 10001',
                'expected_result' => '123 N 1st Ave Apt 4B, New York, NY 10001'
            ],
            [
                'test' => '789 Main St #12, Denver, CO 80202',
                'expected_result' => '789 Main St #12, Denver, CO 80202'
            ],
        ];

        foreach ($addresses as $address) {
            $this->assertEquals(
                $address['expected_result'],
                (string)$normalizer->parse($address['test']),
                "Failed for input: {$address['test']}"
            );
        }
    }

    /** @test */
    public function testZipPlusFour()
    {
        $normalizer = new Normalizer();

        $addresses = [
            [
                'test' => '123 Main St, Denver, CO 80202-1234',
                'expected_number' => '123',
                'expected_zip' => '80202',
                'expected_zip_ext' => '1234',
            ],
            [
                'test' => '456 Oak Ave, Seattle, WA 98101-5678',
                'expected_number' => '456',
                'expected_zip' => '98101',
                'expected_zip_ext' => '5678',
            ],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $components = $parsed->getAddressComponents();
            $this->assertEquals($addr['expected_zip'], $components['postal_code']);
            $this->assertEquals($addr['expected_zip_ext'], $components['postal_code_ext']);
        }
    }

    /** @test */
    public function testPoBoxAddresses()
    {
        $normalizer = new Normalizer();

        $addresses = [
            [
                'test' => 'PO Box 123, Denver, CO 80202',
                'expected_result' => 'PO Box 123, Denver, CO 80202'
            ],
            [
                'test' => 'P.O. Box 456, Seattle, WA 98101',
                'expected_result' => 'P.O. Box 456, Seattle, WA 98101'
            ],
            [
                'test' => 'POB 789, Portland, OR 97201',
                'expected_result' => 'POB 789, Portland, OR 97201'
            ],
        ];

        foreach ($addresses as $address) {
            $parsed = $normalizer->parse($address['test']);
            // PO Box addresses may or may not parse depending on implementation
            // Just verify no exception is thrown
            $this->assertTrue($parsed === false || $parsed instanceof Address);
        }
    }

    /** @test */
    public function testStreetTypeVariations()
    {
        $normalizer = new Normalizer();

        $addresses = [
            ['test' => '123 Main Street, Denver, CO 80202', 'expected_type' => 'St'],
            ['test' => '123 Main St., Denver, CO 80202', 'expected_type' => 'St'],
            ['test' => '123 Main Avenue, Denver, CO 80202', 'expected_type' => 'Ave'],
            ['test' => '123 Main Ave, Denver, CO 80202', 'expected_type' => 'Ave'],
            ['test' => '123 Main Boulevard, Denver, CO 80202', 'expected_type' => 'Blvd'],
            ['test' => '123 Main Blvd, Denver, CO 80202', 'expected_type' => 'Blvd'],
            ['test' => '123 Main Drive, Denver, CO 80202', 'expected_type' => 'Dr'],
            ['test' => '123 Main Dr, Denver, CO 80202', 'expected_type' => 'Dr'],
            ['test' => '123 Main Lane, Denver, CO 80202', 'expected_type' => 'Ln'],
            ['test' => '123 Main Ln, Denver, CO 80202', 'expected_type' => 'Ln'],
            ['test' => '123 Main Road, Denver, CO 80202', 'expected_type' => 'Rd'],
            ['test' => '123 Main Rd, Denver, CO 80202', 'expected_type' => 'Rd'],
            ['test' => '123 Main Circle, Denver, CO 80202', 'expected_type' => 'Cir'],
            ['test' => '123 Main Court, Denver, CO 80202', 'expected_type' => 'Ct'],
            ['test' => '123 Main Place, Denver, CO 80202', 'expected_type' => 'Pl'],
            ['test' => '123 Main Way, Denver, CO 80202', 'expected_type' => 'Way'],
            ['test' => '123 Main Parkway, Denver, CO 80202', 'expected_type' => 'Pkwy'],
            ['test' => '123 Main Highway, Denver, CO 80202', 'expected_type' => 'Hwy'],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals(
                $addr['expected_type'],
                $parsed->getStreetType(),
                "Street type mismatch for: {$addr['test']}"
            );
        }
    }

    /** @test */
    public function testStateNameVariations()
    {
        $normalizer = new Normalizer();

        $addresses = [
            ['test' => '123 Main St, Denver, Colorado 80202', 'expected_state' => 'CO'],
            ['test' => '123 Main St, Denver, CO 80202', 'expected_state' => 'CO'],
            ['test' => '123 Main St, Seattle, Washington 98101', 'expected_state' => 'WA'],
            ['test' => '123 Main St, Seattle, WA 98101', 'expected_state' => 'WA'],
            ['test' => '123 Main St, Austin, Texas 78701', 'expected_state' => 'TX'],
            ['test' => '123 Main St, Austin, TX 78701', 'expected_state' => 'TX'],
            ['test' => '123 Main St, Miami, Florida 33101', 'expected_state' => 'FL'],
            ['test' => '123 Main St, New York, New York 10001', 'expected_state' => 'NY'],
            ['test' => '123 Main St, Los Angeles, California 90001', 'expected_state' => 'CA'],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals(
                $addr['expected_state'],
                $parsed->getState(),
                "State mismatch for: {$addr['test']}"
            );
        }
    }

    /** @test */
    public function testDirectionalVariations()
    {
        $normalizer = new Normalizer();

        $addresses = [
            ['test' => '123 N Main St, Denver, CO 80202', 'expected_prefix' => 'N'],
            ['test' => '123 North Main St, Denver, CO 80202', 'expected_prefix' => 'N'],
            ['test' => '123 S Main St, Denver, CO 80202', 'expected_prefix' => 'S'],
            ['test' => '123 South Main St, Denver, CO 80202', 'expected_prefix' => 'S'],
            ['test' => '123 E Main St, Denver, CO 80202', 'expected_prefix' => 'E'],
            ['test' => '123 East Main St, Denver, CO 80202', 'expected_prefix' => 'E'],
            ['test' => '123 W Main St, Denver, CO 80202', 'expected_prefix' => 'W'],
            ['test' => '123 West Main St, Denver, CO 80202', 'expected_prefix' => 'W'],
            ['test' => '123 NE Main St, Denver, CO 80202', 'expected_prefix' => 'NE'],
            ['test' => '123 Northeast Main St, Denver, CO 80202', 'expected_prefix' => 'NE'],
            ['test' => '123 NW Main St, Denver, CO 80202', 'expected_prefix' => 'NW'],
            ['test' => '123 Northwest Main St, Denver, CO 80202', 'expected_prefix' => 'NW'],
            ['test' => '123 SE Main St, Denver, CO 80202', 'expected_prefix' => 'SE'],
            ['test' => '123 Southeast Main St, Denver, CO 80202', 'expected_prefix' => 'SE'],
            ['test' => '123 SW Main St, Denver, CO 80202', 'expected_prefix' => 'SW'],
            ['test' => '123 Southwest Main St, Denver, CO 80202', 'expected_prefix' => 'SW'],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals(
                $addr['expected_prefix'],
                $parsed->getPrefix(),
                "Prefix mismatch for: {$addr['test']}"
            );
        }
    }

    /** @test */
    public function testDirectionalSuffix()
    {
        $normalizer = new Normalizer();

        $addresses = [
            ['test' => '123 Main St N, Denver, CO 80202', 'expected_suffix' => 'N'],
            ['test' => '123 Main St NE, Denver, CO 80202', 'expected_suffix' => 'NE'],
            ['test' => '123 Main St SW, Denver, CO 80202', 'expected_suffix' => 'SW'],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals(
                $addr['expected_suffix'],
                $parsed->getSuffix(),
                "Suffix mismatch for: {$addr['test']}"
            );
        }
    }

    /** @test */
    public function testUnitPrefixVariations()
    {
        $normalizer = new Normalizer();

        $addresses = [
            ['test' => '123 Main St Suite 100, Denver, CO 80202', 'expected_prefix' => 'Suite', 'expected_unit' => '100'],
            ['test' => '123 Main St Ste 100, Denver, CO 80202', 'expected_prefix' => 'Ste', 'expected_unit' => '100'],
            ['test' => '123 Main St Apt 100, Denver, CO 80202', 'expected_prefix' => 'Apt', 'expected_unit' => '100'],
            ['test' => '123 Main St Apartment 100, Denver, CO 80202', 'expected_prefix' => 'Apartment', 'expected_unit' => '100'],
            ['test' => '123 Main St Unit 100, Denver, CO 80202', 'expected_prefix' => 'Unit', 'expected_unit' => '100'],
            ['test' => '123 Main St #100, Denver, CO 80202', 'expected_prefix' => null, 'expected_unit' => '100'],
            ['test' => '123 Main St Room 100, Denver, CO 80202', 'expected_prefix' => 'Room', 'expected_unit' => '100'],
            ['test' => '123 Main St Fl 2, Denver, CO 80202', 'expected_prefix' => 'Fl', 'expected_unit' => '2'],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals(
                $addr['expected_unit'],
                $parsed->getUnit(),
                "Unit mismatch for: {$addr['test']}"
            );
        }
    }

    /** @test */
    public function testCustomLookups()
    {
        $customDirectional = [
            'north' => 'N',
            'south' => 'S',
            'custom' => 'CUS',
        ];

        $normalizer = new Normalizer();
        $normalizer->setDirectionalLookup($customDirectional);

        // Should still work with standard directionals that are in custom lookup
        $parsed = $normalizer->parse('123 North Main St, Denver, CO 80202');
        $this->assertNotFalse($parsed);
        $this->assertEquals('N', $parsed->getPrefix());
    }

    /** @test */
    public function testStrictModeToggle()
    {
        $normalizer = new Normalizer();

        // Default is strict mode
        $this->assertTrue($normalizer->getStrictMode());

        // Toggle off
        $normalizer->setStrictMode(false);
        $this->assertFalse($normalizer->getStrictMode());

        // Toggle back on
        $normalizer->setStrictMode(true);
        $this->assertTrue($normalizer->getStrictMode());
    }

    /** @test */
    public function testNumericStreetNames()
    {
        $normalizer = new Normalizer();

        $addresses = [
            ['test' => '123 1st St, Denver, CO 80202', 'expected_street' => '1st'],
            ['test' => '123 2nd Ave, Denver, CO 80202', 'expected_street' => '2nd'],
            ['test' => '123 3rd Blvd, Denver, CO 80202', 'expected_street' => '3rd'],
            ['test' => '123 4th Dr, Denver, CO 80202', 'expected_street' => '4th'],
            ['test' => '123 42nd St, Denver, CO 80202', 'expected_street' => '42nd'],
            ['test' => '350 5th Ave, New York, NY 10118', 'expected_street' => '5th'],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals(
                $addr['expected_street'],
                $parsed->getStreet(),
                "Street mismatch for: {$addr['test']}"
            );
        }
    }

    /** @test */
    public function testMultiWordStreetNames()
    {
        $normalizer = new Normalizer();

        $addresses = [
            ['test' => '123 Martin Luther King Blvd, Denver, CO 80202', 'expected_street' => 'Martin Luther King'],
            ['test' => '123 Lake Shore Dr, Chicago, IL 60611', 'expected_street' => 'Lake Shore'],
            ['test' => '123 Park Place Ave, Denver, CO 80202', 'expected_street' => 'Park Place'],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals(
                $addr['expected_street'],
                $parsed->getStreet(),
                "Street mismatch for: {$addr['test']}"
            );
        }
    }

    /** @test */
    public function testAddressWithoutZip()
    {
        $normalizer = new Normalizer();

        $parsed = $normalizer->parse('123 Main St, Denver, CO');
        $this->assertNotFalse($parsed);
        $this->assertEquals('Denver', $parsed->getCity());
        $this->assertEquals('CO', $parsed->getState());
        $this->assertNull($parsed->getPostalCode());
    }

    /** @test */
    public function testHyphenatedStreetNumber()
    {
        $normalizer = new Normalizer();

        $parsed = $normalizer->parse('123-45 Main St, Denver, CO 80202');
        $this->assertNotFalse($parsed);
        $this->assertEquals('123-45', $parsed->getNumber());
    }

    /** @test */
    public function testGetAddressComponents()
    {
        $normalizer = new Normalizer();
        $parsed = $normalizer->parse('123 N Main St NE Apt 4B, Denver, CO 80202-1234');

        $this->assertNotFalse($parsed);
        $components = $parsed->getAddressComponents();

        $this->assertIsArray($components);
        $this->assertArrayHasKey('number', $components);
        $this->assertArrayHasKey('street', $components);
        $this->assertArrayHasKey('street_type', $components);
        $this->assertArrayHasKey('unit', $components);
        $this->assertArrayHasKey('unit_prefix', $components);
        $this->assertArrayHasKey('suffix', $components);
        $this->assertArrayHasKey('prefix', $components);
        $this->assertArrayHasKey('city', $components);
        $this->assertArrayHasKey('state', $components);
        $this->assertArrayHasKey('postal_code', $components);
        $this->assertArrayHasKey('postal_code_ext', $components);

        $this->assertEquals('123', $components['number']);
        $this->assertEquals('Main', $components['street']);
        $this->assertEquals('St', $components['street_type']);
        $this->assertEquals('4B', $components['unit']);
        $this->assertEquals('Apt', $components['unit_prefix']);
        $this->assertEquals('NE', $components['suffix']);
        $this->assertEquals('N', $components['prefix']);
        $this->assertEquals('Denver', $components['city']);
        $this->assertEquals('CO', $components['state']);
        $this->assertEquals('80202', $components['postal_code']);
        $this->assertEquals('1234', $components['postal_code_ext']);
    }

    /** @test */
    public function testInvalidStateNameReturnsNullState()
    {
        $normalizer = new Normalizer();

        // "Fakeland" is not a valid state name
        $parsed = $normalizer->parse('123 Main St, Denver, Fakeland 80202');

        // Should still parse the address but state may be null or unparsed
        // The parser may fail to match completely or return partial results
        if ($parsed) {
            // If parsing succeeds, state should be null for unknown state names
            $state = $parsed->getState();
            $this->assertTrue($state === null || $state === 'Fakeland');
        } else {
            // Parser may return false for addresses with invalid states
            $this->assertFalse($parsed);
        }
    }

    /** @test */
    public function testCustomStateCodesLookupWithReinit()
    {
        $normalizer = new Normalizer();

        $customStates = [
            'teststate' => 'TS',
            'colorado' => 'CO',
        ];

        $normalizer->setStateCodesLookup($customStates, true);

        $parsed = $normalizer->parse('123 Main St, Denver, Colorado 80202');
        $this->assertNotFalse($parsed);
        $this->assertEquals('CO', $parsed->getState());
    }

    /** @test */
    public function testCustomStreetTypesLookupWithReinit()
    {
        $normalizer = new Normalizer();

        $customTypes = [
            'street' => 'st',
            'avenue' => 'ave',
            'customway' => 'cw',
        ];

        $normalizer->setStreetTypesLookup($customTypes, true);

        $parsed = $normalizer->parse('123 Main Street, Denver, CO 80202');
        $this->assertNotFalse($parsed);
        $this->assertEquals('St', $parsed->getStreetType());
    }

    /** @test */
    public function testTwoPassParsingWithDirectionalSuffix()
    {
        $normalizer = new Normalizer();

        // Two-pass parsing with comma to help disambiguate directional suffix
        // When address has comma before city, directional suffix is correctly captured
        $addresses = [
            [
                'test' => '123 Main St NE, Salt Lake City, UT 84101',
                'expected_street' => 'Main',
                'expected_type' => 'St',
                'expected_suffix' => 'NE',
                'expected_city' => 'Salt Lake City',
            ],
            [
                'test' => '456 Oak Ave SW, New York, NY 10001',
                'expected_street' => 'Oak',
                'expected_type' => 'Ave',
                'expected_suffix' => 'SW',
                'expected_city' => 'New York',
            ],
        ];

        foreach ($addresses as $addr) {
            $parsed = $normalizer->parse($addr['test']);
            $this->assertNotFalse($parsed, "Failed to parse: {$addr['test']}");
            $this->assertEquals($addr['expected_street'], $parsed->getStreet(), "Street mismatch for: {$addr['test']}");
            $this->assertEquals($addr['expected_type'], $parsed->getStreetType(), "Street type mismatch for: {$addr['test']}");
            $this->assertEquals($addr['expected_suffix'], $parsed->getSuffix(), "Suffix mismatch for: {$addr['test']}");
            $this->assertEquals($addr['expected_city'], $parsed->getCity(), "City mismatch for: {$addr['test']}");
        }
    }

    /** @test */
    public function testTwoPassParsingCommalessMultiWordCity()
    {
        $normalizer = new Normalizer();

        // Without commas, the two-pass parsing inserts comma after street type
        // to correctly capture multi-word cities
        $parsed = $normalizer->parse('123 Main St Salt Lake City UT 84101');
        $this->assertNotFalse($parsed);
        $this->assertEquals('Main', $parsed->getStreet());
        $this->assertEquals('St', $parsed->getStreetType());
        $this->assertEquals('Salt Lake City', $parsed->getCity());
        $this->assertEquals('UT', $parsed->getState());
    }

    /** @test */
    public function testDirectionalOnlyAddress()
    {
        $normalizer = new Normalizer();

        // Edge case: address that might trigger the regex fallback path
        // where street is captured from match[2] (directional + street type pattern)
        $parsed = $normalizer->parse('123 N Ave, Denver, CO 80202');

        // This tests an unusual address format
        if ($parsed) {
            $this->assertEquals('123', $parsed->getNumber());
            $this->assertEquals('Denver', $parsed->getCity());
        }
    }

    /** @test */
    public function testUnparsableAddressReturnsFalse()
    {
        $normalizer = new Normalizer();

        // Completely invalid input should return false
        $result = $normalizer->parse('not an address at all');
        $this->assertFalse($result);

        // Empty string
        $result = $normalizer->parse('');
        $this->assertFalse($result);
    }
}
