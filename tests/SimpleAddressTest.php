<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use UsAddressNormalization\Exceptions\AddressNotNormalizedException;
use UsAddressNormalization\Normalizer;
use UsAddressNormalization\SimpleAddress;

class SimpleAddressTest extends TestCase
{
    /** @test */
    public function testHashesAddress()
    {
        // NOTE: this is the normalized version of the address used in AddressTest
        $address = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');

        $this->assertEquals('9bdbf17a475a0129c0546fc210ef46cb914338d0', $address->getHash());
        $this->assertEquals($address->getHash(), SimpleAddress::hashFromParts('1234 Main St NE', null, 'Minneapolis', 'MN', '55401'));

        $this->assertEquals('c4ced80b9489911b4a66712470833242597aa032', $address->getFullHash());
        $this->assertEquals($address->getFullHash(), SimpleAddress::fullHashFromParts('1234 Main St NE', null, 'Minneapolis', 'MN', '55401'));

        $this->expectException(AddressNotNormalizedException::class);
        $address->getStreetHash();
    }

    /** @test */
    public function testToString()
    {
        $address = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');

        $this->assertEquals('1234 Main St NE, Minneapolis, MN 55401', $address->toString());
        $this->assertEquals('1234 Main St NE, Minneapolis, MN 55401', (string) $address);
    }

    /** @test */
    public function testToStringWithAddress2()
    {
        $address = new SimpleAddress('1234 Main St NE', 'Apt 4B', 'Minneapolis', 'MN', '55401');

        $this->assertEquals('1234 Main St NE Apt 4B, Minneapolis, MN 55401', $address->toString());
    }

    /** @test */
    public function testToArray()
    {
        $address = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');

        $array = $address->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals('1234 Main St NE', $array[0]);
        $this->assertEquals('Minneapolis, MN 55401', $array[1]);
    }

    /** @test */
    public function testToArrayWithAddress2()
    {
        $address = new SimpleAddress('1234 Main St NE', 'Suite 100', 'Minneapolis', 'MN', '55401');

        $array = $address->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals('1234 Main St NE Suite 100', $array[0]);
        $this->assertEquals('Minneapolis, MN 55401', $array[1]);
    }

    /** @test */
    public function testWithoutZip()
    {
        $address = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', null);

        $this->assertEquals('1234 Main St NE, Minneapolis, MN', $address->toString());

        // Hash should still work without zip
        $hash = $address->getHash();
        $this->assertNotEmpty($hash);
    }

    /** @test */
    public function testHashFromPartsStatic()
    {
        $hash = SimpleAddress::hashFromParts('123 Main St', null, 'Denver', 'CO', '80202');
        $fullHash = SimpleAddress::fullHashFromParts('123 Main St', null, 'Denver', 'CO', '80202');

        $this->assertNotEmpty($hash);
        $this->assertNotEmpty($fullHash);
        $this->assertNotEquals($hash, $fullHash);

        // Hash should be consistent
        $hash2 = SimpleAddress::hashFromParts('123 Main St', null, 'Denver', 'CO', '80202');
        $this->assertEquals($hash, $hash2);
    }

    /** @test */
    public function testHashMatchesNormalizedAddress()
    {
        $normalizer = new Normalizer();
        $normalizedAddress = $normalizer->parse('1234 Main St NE, Minneapolis, MN 55401');

        $simpleAddress = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');

        // Hashes should match when addresses are equivalent
        $this->assertEquals($normalizedAddress->getHash(), $simpleAddress->getHash());
        $this->assertEquals($normalizedAddress->getFullHash(), $simpleAddress->getFullHash());
    }

    /** @test */
    public function testDifferentZipsSameHash()
    {
        $address1 = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');
        $address2 = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55402');

        // Hash (without zip) should be the same
        $this->assertEquals($address1->getHash(), $address2->getHash());

        // Full hash (with zip) should be different
        $this->assertNotEquals($address1->getFullHash(), $address2->getFullHash());
    }

    /** @test */
    public function testCaseSensitivity()
    {
        $address1 = new SimpleAddress('1234 Main St NE', null, 'Minneapolis', 'MN', '55401');
        $address2 = new SimpleAddress('1234 MAIN ST NE', null, 'MINNEAPOLIS', 'MN', '55401');

        // Hash uses lowercase internally, so these should match
        $this->assertEquals($address1->getHash(), $address2->getHash());
    }
}
