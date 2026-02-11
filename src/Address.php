<?php

namespace UsAddressNormalization;

class Address
{
    protected $number;
    protected $street;
    protected $streetType;
    protected $unit;
    protected $unitPrefix;
    protected $suffix;
    protected $prefix;
    protected $city;
    protected $state;
    protected $postalCode;
    protected $postalCodeExt;
    protected $streetType2;
    protected $prefix2;
    protected $suffix2;
    protected $street2;

    protected $hash;
    protected $fullHash;
    protected $streetHash;

    /**
     * Factory to generate from a parsed array of values
     *
     * @param array $address]s
     *
     * @return Address
     */
    public static function fromParsedArray(array $address): Address
    {
        return (new self())
            ->setNumber($address['number'])
            ->setStreet($address['street'])
            ->setStreetType($address['street_type'])
            ->setUnit($address['unit'])
            ->setUnitPrefix($address['unit_prefix'])
            ->setSuffix($address['suffix'])
            ->setPrefix($address['prefix'])
            ->setCity($address['city'])
            ->setState($address['state'])
            ->setPostalCode($address['postal_code'])
            ->setPostalCodeExt($address['postal_code_ext'])
            ->setStreetType2($address['street_type2'])
            ->setPrefix2($address['prefix2'])
            ->setSuffix2($address['suffix2'])
            ->setStreet2($address['street2']);
    }

    /**
     * Gets the individual pieces of the address
     *
     * @return array
     */
    public function getAddressComponents()
    {
        return [
            'number' => $this->getNumber(),
            'street' => $this->getStreet(),
            'street_type' => $this->getStreetType(),
            'unit' => $this->getUnit(),
            'unit_prefix' => $this->getUnitPrefix(),
            'suffix' => $this->getSuffix(),
            'prefix' => $this->getPrefix(),
            'city' => $this->getCity(),
            'state' => $this->getState(),
            'postal_code' => $this->getPostalCode(),
            'postal_code_ext' => $this->getPostalCodeExt(),
            'street_type2' => $this->getStreetType2(),
            'prefix2' => $this->getPrefix2(),
            'suffix2' => $this->getSuffix2(),
            'street2' => $this->getStreet2(),
        ];
    }

    /**
     * Formats the address as a two line string
     *
     * @return string
     */
    public function toString(): string
    {
        $line1 = $this->getLineOne();
        $line2 = $this->getLineTwo();
        if ($line1 && $line2) {
            return $line1 . ", " . $line2;
        }
        return $line1 . $line2;
    }

    /**
     * PHP Magic helper to cast to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Formats the address as a two line string, returned as a two element array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            $this->getLineOne(),
            $this->getLineTwo()
        ];
    }

    /**
     * Gets the street hash
     *
     * @param string $algo
     *
     * @return string
     */
    public function getStreetHash(string $algo = 'sha1'): string
    {
        if ($this->streetHash) {
            return $this->streetHash;
        }

        $this->streetHash = hash($algo, strtolower(
            $this->prefix .
            $this->street .
            $this->streetType .
            $this->suffix
        ));

        return $this->streetHash;
    }

    /**
     * Gets the full hash (of all address components, including zip code)
     *
     * @param string $algo
     *
     * @return string
     */
    public function getFullHash(string $algo = 'sha1'): string
    {
        if ($this->fullHash) {
            return $this->fullHash;
        }
        $this->fullHash = hash($algo, strtolower($this->toString()));
        return $this->fullHash;
    }

    /**
     * Gets the address hash (of all components MINUS the zip code)
     *
     * NOTE: zip codes have the potential to change, and something like 4% of
     * ZIP codes change every month, so this hash is likely the better comparison
     *
     * @param string $algo
     *
     * @return string
     */
    public function getHash(string $algo = 'sha1'): string
    {
        if ($this->hash) {
            return $this->hash;
        }
        $line1 = $this->getLineOne();
        $line2 = $this->getLineTwo(false);
        $this->hash = hash($algo, strtolower(
            ($line1 && $line2) ? $line1 . ', ' . $line2 : $line1
        ));
        return $this->hash;
    }

    /**
     * Is this address is the same as another address?
     *
     * @param Address $address
     * @param bool $fullHash uses the full hash comparison (with zip) vs without zip
     *
     * @return bool
     */
    public function is(Address $address, bool $fullHash = false): bool
    {
        if ($fullHash) {
            return $this->getFullHash() === $address->getFullHash();
        }
        return $this->getHash() === $address->getHash();
    }

    /**
     * Is this address on the same street as another address?
     *
     * @param Address $address
     *
     * @return bool
     */
    public function isSameStreet(Address $address): bool
    {
        return $this->getStreetHash() === $address->getStreetHash();
    }

    /**
     * Gets the first line of a formatted address
     *
     * @return string
     */
    protected function getLineOne(): string
    {
        $line = (string) $this->number;
        $line .= $this->prefix ? " " . $this->prefix : "";
        $line .= $this->street ? " " . $this->street : "";
        $line .= $this->streetType ? " " . $this->streetType : "";
        $line .= $this->suffix ? " " . $this->suffix : "";
        if ($this->unitPrefix && $this->unit) {
            $line .= " " . $this->unitPrefix;
            $line .= " " . $this->unit;
        } elseif (!$this->unitPrefix && $this->unit) {
            $line .= " #" . $this->unit;
        }
        return $line;
    }

    /**
     * Gets the second line of a formatted address
     *
     * @param bool $withZip include/exclude zip code
     *
     * @return string
     */
    protected function getLineTwo(bool $withZip = true): string
    {
        $line = (string) $this->city;
        $line .= $this->state ? ", " . $this->state : "";
        if ($withZip) {
            $line .= $this->postalCode ? " " . $this->postalCode : "";
            $line .= $this->postalCodeExt ? "-" . $this->postalCodeExt : "";
        }
        return $line;
    }

    /**
     * Get the value of number
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set the value of number
     *
     * @return  self
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get the value of street
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set the value of street
     *
     * @return  self
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get the value of streetType
     */
    public function getStreetType()
    {
        return $this->streetType;
    }

    /**
     * Set the value of streetType
     *
     * @return  self
     */
    public function setStreetType($streetType)
    {
        $this->streetType = $streetType;

        return $this;
    }

    /**
     * Get the value of unit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set the value of unit
     *
     * @return  self
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get the value of unitPrefix
     */
    public function getUnitPrefix()
    {
        return $this->unitPrefix;
    }

    /**
     * Set the value of unitPrefix
     *
     * @return  self
     */
    public function setUnitPrefix($unitPrefix)
    {
        $this->unitPrefix = $unitPrefix;

        return $this;
    }

    /**
     * Get the value of suffix
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Set the value of suffix
     *
     * @return  self
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Get the value of prefix
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the value of prefix
     *
     * @return  self
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the value of city
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set the value of city
     *
     * @return  self
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get the value of state
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the value of state
     *
     * @return  self
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the value of postalCode
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set the value of postalCode
     *
     * @return  self
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get the value of postalCodeExt
     */
    public function getPostalCodeExt()
    {
        return $this->postalCodeExt;
    }

    /**
     * Set the value of postalCodeExt
     *
     * @return  self
     */
    public function setPostalCodeExt($postalCodeExt)
    {
        $this->postalCodeExt = $postalCodeExt;

        return $this;
    }

    /**
     * Get the value of streetType2
     */
    public function getStreetType2()
    {
        return $this->streetType2;
    }

    /**
     * Set the value of streetType2
     *
     * @return  self
     */
    public function setStreetType2($streetType2)
    {
        $this->streetType2 = $streetType2;

        return $this;
    }

    /**
     * Get the value of prefix2
     */
    public function getPrefix2()
    {
        return $this->prefix2;
    }

    /**
     * Set the value of prefix2
     *
     * @return  self
     */
    public function setPrefix2($prefix2)
    {
        $this->prefix2 = $prefix2;

        return $this;
    }

    /**
     * Get the value of suffix2
     */
    public function getSuffix2()
    {
        return $this->suffix2;
    }

    /**
     * Set the value of suffix2
     *
     * @return  self
     */
    public function setSuffix2($suffix2)
    {
        $this->suffix2 = $suffix2;

        return $this;
    }

    /**
     * Get the value of street2
     */
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * Set the value of street2
     *
     * @return  self
     */
    public function setStreet2($street2)
    {
        $this->street2 = $street2;

        return $this;
    }
}
