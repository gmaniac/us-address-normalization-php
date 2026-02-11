<?php

namespace UsAddressNormalization;

use UsAddressNormalization\Address;
use UsAddressNormalization\Exceptions\AddressNotNormalizedException;

/**
 * This is a non-normalized version of Address::class to make use of hashing.
 */
class SimpleAddress extends Address
{
    protected $address1;
    protected $address2;

    public function __construct(
        string $address1,
        ?string $address2,
        string $city,
        string $state,
        ?string $postalCode
    ) {
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
    }

    /**
     * @inheritDoc
     */
    protected function getLineOne(): string
    {
        if (empty($this->address2)) {
            return $this->address1;
        }
        return $this->address1 . ' ' . $this->address2;
    }

    /**
     * @inheritDoc
     */
    public function getStreetHash(string $algo = 'sha1'): string
    {
        throw new AddressNotNormalizedException('Address is not normalized and cannot hash the street.');
    }

    /**
     * Helper to get an address hash from 5-part address
     *
     * @param string $address1
     * @param string|null $address2
     * @param string $city
     * @param string $state
     * @param string|null $postalCode
     *
     * @return string
     */
    public static function hashFromParts(string $address1, ?string $address2, string $city, string $state, ?string $postalCode = null): string
    {
        $address = new self($address1, $address2, $city, $state, $postalCode);
        return $address->getHash();
    }

    /**
     * Helper to get a full address hash from 5-part address
     *
     * @param string $address1
     * @param string|null $address2
     * @param string $city
     * @param string $state
     * @param string|null $postalCode
     *
     * @return string
     */
    public static function fullHashFromParts(string $address1, ?string $address2, string $city, string $state, ?string $postalCode = null): string
    {
        $address = new self($address1, $address2, $city, $state, $postalCode);
        return $address->getFullHash();
    }
}
