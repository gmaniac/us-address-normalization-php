<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use UsAddressNormalization\Normalizer;
use UsAddressNormalization\Address;

class AddressTest extends TestCase
{
    /** @test */
    public function testHashesAddress()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55401");
        $sameAddressDiffZip = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55402");
        $sameAddress = $normalizer->parse("1234 Main Street Northeast, Minneapolis, Minnesota 55401");
        $differentNumberAddress = $normalizer->parse("5678 Main Street Northeast, Minneapolis, Minnesota 55401");

        $this->assertEquals('9bdbf17a475a0129c0546fc210ef46cb914338d0', $address->getHash());
        $this->assertEquals('c4ced80b9489911b4a66712470833242597aa032', $address->getFullHash());
        $this->assertEquals('f7f77f11493cccb50c5827dff7cb8b26d31f8442', $address->getStreetHash());

        $this->assertEquals($address->getHash(), $sameAddressDiffZip->getHash());
        $this->assertNotEquals($address->getFullHash(), $sameAddressDiffZip->getFullHash());

        $this->assertEquals($address->getHash(), $sameAddress->getHash());
        $this->assertEquals($address->getFullHash(), $sameAddress->getFullHash());
        $this->assertEquals($address->getStreetHash(), $sameAddress->getStreetHash());
        $this->assertTrue($address->is($sameAddress));
        $this->assertTrue($sameAddress->is($address));
        $this->assertTrue($address->is($sameAddress, true));
        $this->assertTrue($sameAddress->is($address, true));

        $this->assertEquals($address->getStreetHash(), $differentNumberAddress->getStreetHash());
        $this->assertNotEquals($address->getFullHash(), $differentNumberAddress->getFullHash());
        $this->assertTrue($address->isSameStreet($differentNumberAddress));
        $this->assertTrue($differentNumberAddress->isSameStreet($address));
    }

    /** @test */
    public function testToString()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55401");

        $this->assertEquals('1234 Main St NE, Minneapolis, MN 55401', $address->toString());
        $this->assertEquals('1234 Main St NE, Minneapolis, MN 55401', (string) $address);
    }

    /** @test */
    public function testToArray()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55401");

        $array = $address->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals('1234 Main St NE', $array[0]);
        $this->assertEquals('Minneapolis, MN 55401', $array[1]);
    }

    /** @test */
    public function testToArrayWithUnit()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 Main St. NE Apt 4B, Minneapolis, MN 55401");

        $array = $address->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals('1234 Main St NE Apt 4B', $array[0]);
        $this->assertEquals('Minneapolis, MN 55401', $array[1]);
    }

    /** @test */
    public function testToArrayWithHashUnit()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 Main St. NE #4B, Minneapolis, MN 55401");

        $array = $address->toArray();

        $this->assertEquals('1234 Main St NE #4B', $array[0]);
    }

    /** @test */
    public function testIndividualGetters()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 N Main St NE Apt 4B, Minneapolis, MN 55401-1234");

        $this->assertEquals('1234', $address->getNumber());
        $this->assertEquals('Main', $address->getStreet());
        $this->assertEquals('St', $address->getStreetType());
        $this->assertEquals('N', $address->getPrefix());
        $this->assertEquals('NE', $address->getSuffix());
        $this->assertEquals('4B', $address->getUnit());
        $this->assertEquals('Apt', $address->getUnitPrefix());
        $this->assertEquals('Minneapolis', $address->getCity());
        $this->assertEquals('MN', $address->getState());
        $this->assertEquals('55401', $address->getPostalCode());
        $this->assertEquals('1234', $address->getPostalCodeExt());
    }

    /** @test */
    public function testFromParsedArray()
    {
        $parsedArray = [
            'number' => '123',
            'street' => 'Test',
            'street_type' => 'St',
            'unit' => '4B',
            'unit_prefix' => 'Apt',
            'suffix' => 'NE',
            'prefix' => 'N',
            'city' => 'Denver',
            'state' => 'CO',
            'postal_code' => '80202',
            'postal_code_ext' => '1234',
            'street_type2' => null,
            'prefix2' => null,
            'suffix2' => null,
            'street2' => null,
        ];

        $address = Address::fromParsedArray($parsedArray);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('123', $address->getNumber());
        $this->assertEquals('Test', $address->getStreet());
        $this->assertEquals('St', $address->getStreetType());
        $this->assertEquals('4B', $address->getUnit());
        $this->assertEquals('Apt', $address->getUnitPrefix());
        $this->assertEquals('NE', $address->getSuffix());
        $this->assertEquals('N', $address->getPrefix());
        $this->assertEquals('Denver', $address->getCity());
        $this->assertEquals('CO', $address->getState());
        $this->assertEquals('80202', $address->getPostalCode());
        $this->assertEquals('1234', $address->getPostalCodeExt());
    }

    /** @test */
    public function testSetters()
    {
        $address = new Address();

        $address->setNumber('999')
            ->setStreet('Oak')
            ->setStreetType('Ave')
            ->setPrefix('S')
            ->setSuffix('SW')
            ->setUnit('100')
            ->setUnitPrefix('Suite')
            ->setCity('Portland')
            ->setState('OR')
            ->setPostalCode('97201')
            ->setPostalCodeExt('5678');

        $this->assertEquals('999', $address->getNumber());
        $this->assertEquals('Oak', $address->getStreet());
        $this->assertEquals('Ave', $address->getStreetType());
        $this->assertEquals('S', $address->getPrefix());
        $this->assertEquals('SW', $address->getSuffix());
        $this->assertEquals('100', $address->getUnit());
        $this->assertEquals('Suite', $address->getUnitPrefix());
        $this->assertEquals('Portland', $address->getCity());
        $this->assertEquals('OR', $address->getState());
        $this->assertEquals('97201', $address->getPostalCode());
        $this->assertEquals('5678', $address->getPostalCodeExt());
    }

    /** @test */
    public function testHashCaching()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55401");

        // First call computes hash
        $hash1 = $address->getHash();
        // Second call should return cached value
        $hash2 = $address->getHash();

        $this->assertEquals($hash1, $hash2);

        // Same for fullHash
        $fullHash1 = $address->getFullHash();
        $fullHash2 = $address->getFullHash();

        $this->assertEquals($fullHash1, $fullHash2);

        // Same for streetHash
        $streetHash1 = $address->getStreetHash();
        $streetHash2 = $address->getStreetHash();

        $this->assertEquals($streetHash1, $streetHash2);
    }

    /** @test */
    public function testIsWithFullHashOption()
    {
        $normalizer = new Normalizer();
        $address1 = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55401");
        $address2 = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55402");

        // Without full hash (ignores zip), should match
        $this->assertTrue($address1->is($address2, false));

        // With full hash (includes zip), should not match
        $this->assertFalse($address1->is($address2, true));
    }

    /** @test */
    public function testCustomHashAlgorithm()
    {
        $normalizer = new Normalizer();

        // Use separate address objects since hash values are cached per instance
        $address1 = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55401");
        $address2 = $normalizer->parse("1234 Main St. NE, Minneapolis, MN 55401");

        $sha1Hash = $address1->getHash('sha1');
        $md5Hash = $address2->getHash('md5');

        // SHA1 produces 40 hex chars, MD5 produces 32 hex chars
        $this->assertEquals(40, strlen($sha1Hash));
        $this->assertEquals(32, strlen($md5Hash));
        $this->assertNotEquals($sha1Hash, $md5Hash);
    }

    /** @test */
    public function testAddressWithoutOptionalComponents()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse("1234 Main, Denver, CO 80202");

        $this->assertNotFalse($address);
        $this->assertEquals('1234', $address->getNumber());
        $this->assertEquals('Denver', $address->getCity());
        $this->assertEquals('CO', $address->getState());
    }

    /** @test */
    public function testSecondaryStreetComponents()
    {
        $address = new Address();

        $address->setStreet2('Oak')
            ->setStreetType2('Ave')
            ->setPrefix2('N')
            ->setSuffix2('NE');

        $this->assertEquals('Oak', $address->getStreet2());
        $this->assertEquals('Ave', $address->getStreetType2());
        $this->assertEquals('N', $address->getPrefix2());
        $this->assertEquals('NE', $address->getSuffix2());

        $components = $address->getAddressComponents();
        $this->assertEquals('Oak', $components['street2']);
        $this->assertEquals('Ave', $components['street_type2']);
        $this->assertEquals('N', $components['prefix2']);
        $this->assertEquals('NE', $components['suffix2']);
    }

    /** @test */
    public function testToStringWithEmptyLineTwo()
    {
        // Address with only street line, no city/state/zip
        $address = new Address();
        $address->setNumber('123')
            ->setStreet('Main')
            ->setStreetType('St');

        // toString should handle empty line2 without adding comma
        $result = $address->toString();
        $this->assertEquals('123 Main St', $result);
    }

    /** @test */
    public function testToStringWithEmptyLineOne()
    {
        // Address with only city/state (unusual but possible edge case)
        $address = new Address();
        $address->setCity('Denver')
            ->setState('CO')
            ->setPostalCode('80202');

        // Line 1 will be empty, line 2 has city/state/zip
        $result = $address->toString();
        // Should not have leading comma
        $this->assertEquals('Denver, CO 80202', $result);
    }

    /** @test */
    public function testToArrayWithEmptyComponents()
    {
        $address = new Address();
        $address->setNumber('123')
            ->setStreet('Main')
            ->setStreetType('St');

        $array = $address->toArray();
        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals('123 Main St', $array[0]);
        // Line 2 should be empty string
        $this->assertEquals('', $array[1]);
    }
}
