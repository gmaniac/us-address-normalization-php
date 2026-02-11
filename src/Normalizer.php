<?php

namespace UsAddressNormalization;

class Normalizer
{
    private $directional = [];

    private $streetTypes = [];

    private $streetTypesList = [];

    private $stateCodes = [];

    private $strictMode = true;

    public $street_type_regexp;
    public $number_regexp;
    public $fraction_regexp;
    public $state_regexp;
    public $city_and_state_regexp;
    public $direct_regexp;
    public $zip_regexp;
    public $corner_regexp;
    public $unit_regexp;
    public $street_regexp;
    public $place_regexp;
    public $address_regexp;
    public $informal_address_regexp;
    public $regex;

    public function __construct(array $options = [])
    {
        $this->setDirectionalLookup($options['directional_lookups'] ?? include(__DIR__ . '/lookups/directional.php'), false);
        $this->setStateCodesLookup($options['state_codes_lookups'] ?? include(__DIR__ . '/lookups/state_codes.php'), false);
        $this->setStreetTypesLookup($options['street_types_lookups'] ?? include(__DIR__ . '/lookups/street_types.php'), false);
        $this->setStrictMode($options['strict_mode'] ?? true);
        $this->init();
    }

    /**
     * Re-initializes the normalizer. Use after setting new lookups.
     *
     * @return void
     */
    private function init()
    {
        $this->setupStreetTypeList();
        $this->setupRegularExpressions();
    }

    public function setStrictMode(bool $strictMode): void
    {
        $this->strictMode = $strictMode;
    }

    public function getStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * Sets the directional lookups
     *
     * @param array $lookups
     * @param bool $reinit re-initializes after setting new lookups
     *
     * @return void
     */
    public function setDirectionalLookup(array $lookups, bool $reinit = true): void
    {
        $this->directional = $lookups;
        if ($reinit) {
            $this->init();
        }
    }

    /**
     * Sets the state codes lookups
     *
     * @param array $lookups
     * @param bool $reinit re-initializes after setting new lookups
     *
     * @return void
     */
    public function setStateCodesLookup(array $lookups, bool $reinit = true): void
    {
        $this->stateCodes = $lookups;
        if ($reinit) {
            $this->init();
        }
    }

    /**
     * Sets the street types lookups
     *
     * @param array $lookups
     * @param bool $reinit re-initializes after setting new lookups
     *
     * @return void
     */
    public function setStreetTypesLookup(array $lookups, bool $reinit = true): void
    {
        $this->streetTypes = $lookups;
        if ($reinit) {
            $this->init();
        }
    }

    private function setupStreetTypeList()
    {
        foreach ($this->streetTypes as $streetType => $streetTypeAbbr) {
            $this->streetTypesList[$streetType] = true;
            $this->streetTypesList[$streetTypeAbbr] = true;
        }
    }

    private function setupRegularExpressions()
    {
        $this->street_type_regexp = implode("|", array_keys($this->streetTypesList));
        $this->number_regexp = '\d+-?\d*';
        $this->fraction_regexp = '\d+\/\d+';
        $statesAndCodes = array();
        foreach ($this->stateCodes as $state => $code) {
            $statesAndCodes[] = $state;
            $statesAndCodes[] = $code;
        }
        $this->state_regexp = preg_replace('/ /', "\\s", implode("|", $statesAndCodes));
        $this->city_and_state_regexp =
            '(?:'
            . '([^\d,]+?)\W+'
            . '(' . $this->state_regexp . ')'
            . ')';
        $directionalValues = array_values($this->directional);
        $expandedDirectionalValues = array();
        foreach ($directionalValues as $directionalValue) {
            $expandedDirectionalValues[] = preg_replace('/(\w)/', "$1\\\\.", $directionalValue);
            $expandedDirectionalValues[] = $directionalValue;
        }
        $this->direct_regexp = implode("|", array_keys($this->directional))
            . "|"
            . implode("|", $expandedDirectionalValues);

        $alts = explode('|', $this->direct_regexp);
        usort($alts, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
        $this->direct_regexp = implode('|', $alts);

        $this->zip_regexp = '(\d{5})(?:-?(\d{4})?)';
        $this->corner_regexp = '(?:\band\b|\bat\b|&|\@)';
        $this->unit_regexp = '(?:(su?i?te|p\W*[om]\W*b(?:ox)?|dept|apt|apartment|ro*m|fl|unit|box)\W+|\#\W*)([\w-]+)';
        $this->street_regexp =
            '(?:'
            . '(?:(' . $this->direct_regexp . ')\W+'
            . '(' . $this->street_type_regexp . ')\b)'
            . '|'
            . '(?:(' . $this->direct_regexp . ')\W+)?'
            . '(?:'
            . '([^,]+)'
            . '(?:[^\w,]+(' . $this->street_type_regexp . ')\b)'
            . '(?:[^\w,]+(' . $this->direct_regexp . ')\b)?'
            . '|'
            . '([^,]*\d)'
            . '(' . $this->direct_regexp . ')\b'
            . '|'
            . '([^,]+?)'
            . '(?:[^\w,]+(' . $this->street_type_regexp . ')\b)?'
            . '(?:[^\w,]+(' . $this->direct_regexp . ')\b)?'
            . ')'
            . ')';

        $this->place_regexp =
            '(?:' . $this->city_and_state_regexp . '\W*)?'
            . '(?:' . $this->zip_regexp . ')?';

        $this->address_regexp =
            '\A\W*'
            . '(' . $this->number_regexp . ')\W*'
            . '(?:' . $this->fraction_regexp . '\W*)?'
            . $this->street_regexp . '\W+'
            . '(?:' . $this->unit_regexp . '\W+)?'
            . $this->place_regexp .
            '\W*\Z';

        $this->informal_address_regexp =
            '\A\s*'
            . '(' . $this->number_regexp . ')\W*'
            . '(?:' . $this->fraction_regexp . '\W*)?'
            . $this->street_regexp . '(?:\W+|\Z)'
            . '(?:' . $this->unit_regexp . '(?:\W+|\Z))?'
            . '(?:' . $this->place_regexp . ')?';
    }

    /**
     * Parses from a 5-part address
     *
     * NOTE: set strict mode to false to return a SimpleAddress if the address parts do not normalize
     *
     * @param string $address1
     * @param string|null $address2
     * @param string $city
     * @param string $state
     * @param string|null $zip
     *
     * @return Address|SimpleAddress|false
     */
    public function parseFromComponents(string $address1, ?string $address2, string $city, string $state, ?string $zip)
    {
        $address = new SimpleAddress($address1, $address2, $city, $state, $zip);

        $parsed = $this->parse((string)$address);

        if (!$parsed) {
            return ($this->getStrictMode()) ? false : $address;
        }

        return $parsed;
    }

    protected function parseToArray($address)
    {
        $match = array();
        preg_match('/' . $this->address_regexp . '/i', $address, $match);

        if (!$match) {
            return false;
        }
        $street = @$match[5];
        if (!$street) {
            $street = @$match[10];
            if (!$street) {
                $street = @$match[2];
            }
        }

        $streetType = @$match[6];
        if (!$streetType) {
            $streetType = @$match[3];
        }

        $suffix = @$match[7];
        if (!$suffix) {
            $suffix = @$match[12];
        }

        $parsedAddress = array(
             'number' => @$match[1],
             'street' => $street,
             'street_type' => $streetType,
             'unit' => @$match[14],
             'unit_prefix' => @$match[13],
             'suffix' => $suffix,
             'prefix' => @$match[4],
             'city' => @$match[15],
             'state' => @$match[16],
             'postal_code' => @$match[17],
             'postal_code_ext' => @$match[18]
        );

        return $this->normalizeAddress($parsedAddress);
    }

    private function normalizeAddress($addr)
    {
          $addr['state'] = (isset($addr['state'])) ? $this->normalizeState($addr['state']) : null;
          $addr['street_type'] = (isset($addr['street_type'])) ? $this->normalizeStreetType($addr['street_type']) : null;
          $addr['prefix'] = (isset($addr['prefix'])) ? $this->normalizeDirectional($addr['prefix']) : null;
          $addr['suffix'] = (isset($addr['suffix'])) ? $this->normalizeDirectional($addr['suffix']) : null;
          $addr['street'] = (isset($addr['street'])) ? ucwords($addr['street']) : null;
          $addr['street_type2'] = (isset($addr['street_type2'])) ? $this->normalizeStreetType($addr['street_type2']) : null;
          $addr['prefix2'] = (isset($addr['prefix2'])) ? $this->normalizeDirectional($addr['prefix2']) : null;
          $addr['suffix2'] = (isset($addr['suffix2'])) ? $this->normalizeDirectional($addr['suffix2']) : null;
          $addr['street2'] =  (isset($addr['street2'])) ? ucwords($addr['street2']) : null;
          $addr['city'] = (isset($addr['city'])) ? ucwords($addr['city']) : null;
          $addr['unit_prefix'] = (isset($addr['unit_prefix'])) ? ucfirst(strtolower($addr['unit_prefix'])) : null;
          return $addr;
    }

    private function normalizeState($state)
    {
        if (strlen($state) < 3) {
            return strtoupper($state);
        }
        $state = strtolower($state);
        if (isset($this->stateCodes[$state])) {
            return $this->stateCodes[$state];
        }
        return null;
    }

    private function normalizeStreetType($sType)
    {
        $sType = strtolower($sType);
        if (isset($this->streetTypes[$sType])) {
            return ucfirst($this->streetTypes[$sType]);
        }
        if (isset($this->streetTypesList[$sType])) {
            return ucfirst($sType);
        }
        return null;
    }

    protected function normalizeDirectional($dir)
    {
        $stripped = str_replace('.', '', $dir);
        if (strlen($stripped) <= 2) {
            return strtoupper($stripped);
        }
        $lower = strtolower($stripped);
        if (isset($this->directional[$lower])) {
            return $this->directional[$lower];
        }
        return null;
    }

    /**
     * @return array
     */
    protected function getStreetTypeSet(): array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            foreach ($this->streetTypes as $long => $short) {
                $cache[strtolower($long)] = true;
                $cache[strtolower($short)] = true;
            }
        }
        return $cache;
    }

    /**
     * @param Address|false|null $result
     * @return int
     */
    protected function scoreResult($result): int
    {
        if ($result === null || $result === false) {
            return -100;
        }

        $components = $result->getAddressComponents();
        $score = 0;

        if (!empty($components['state'])) {
            $score += 10;
        }
        if (!empty(trim($components['city'] ?? ''))) {
            $score += 5;
            $score += count(preg_split('/\s+/', trim($components['city'])));
        }
        if (!empty($components['postal_code'])) {
            $score += 3;
        }

        // Penalize: street contains a type word followed by a non-type word.
        // e.g. "Main St Salt" -> "St" (type) then "Salt" (not type) = overshot.
        $street = $result->getStreet();
        if ($street) {
            $words = preg_split('/\s+/', trim($street));
            $allTypes = $this->getStreetTypeSet();
            for ($i = 0; $i < count($words) - 1; $i++) {
                if (isset($allTypes[strtolower($words[$i])])) {
                    if (!isset($allTypes[strtolower($words[$i + 1])])) {
                        $score -= 15;
                    }
                }
            }
        }

        return $score;
    }

    /**
     * Internal parse that does not apply two-pass logic.
     * Used by the two-pass parse() to avoid infinite recursion.
     *
     * @param string $address
     * @return Address|false
     */
    protected function parseInternal(string $address)
    {
        $addressArray = $this->parseToArray($address);
        if (!$addressArray) {
            return false;
        }
        return Address::fromParsedArray($addressArray);
    }

    /**
     * Parses the given address.
     *
     * @param string $address
     *
     * @return Address|false
     */
    public function parse($address)
    {
        $result = $this->parseInternal($address);

        // Only apply two-pass logic to comma-less input
        if (strpos($address, ',') !== false || $result === false) {
            return $result;
        }

        $firstScore = $this->scoreResult($result);
        if ($firstScore >= 10) {
            return $result;
        }

        // Embedded-type penalty fired - try inserting commas
        $words = preg_split('/\s+/', trim($address));
        $allTypes = $this->getStreetTypeSet();
        $dirAbbrevs = array_flip(array_map('strtolower', array_values($this->directional)));
        $dirAll = array_merge(
            array_flip(array_map('strtolower', array_keys($this->directional))),
            $dirAbbrevs
        );

        // Skip past house number
        $numEnd = 0;
        while ($numEnd < count($words) && preg_match('/^\d/', $words[$numEnd])) {
            $numEnd++;
        }

        // Skip optional leading directional
        $scanStart = $numEnd;
        if ($scanStart < count($words)) {
            $w = strtolower(str_replace('.', '', $words[$scanStart]));
            if (isset($dirAll[$w])) {
                $scanStart++;
            }
        }

        $bestResult = $result;
        $bestScore = $firstScore;

        for ($i = $scanStart + 1; $i < count($words); $i++) {
            $w = strtolower(rtrim($words[$i], '.'));
            if (!isset($allTypes[$w])) {
                continue;
            }

            // Try comma right after the street type
            if ($i + 1 < count($words)) {
                $before = implode(' ', array_slice($words, 0, $i + 1));
                $after = implode(' ', array_slice($words, $i + 1));
                $retry = $this->parseInternal($before . ', ' . $after);
                $retryScore = $this->scoreResult($retry);
                if ($retryScore > $bestScore) {
                    $bestResult = $retry;
                    $bestScore = $retryScore;
                }
            }

            // If next word is a directional, also try comma after type+directional
            if ($i + 2 < count($words)) {
                $next = strtolower(str_replace('.', '', $words[$i + 1]));
                if (isset($dirAll[$next])) {
                    $before = implode(' ', array_slice($words, 0, $i + 2));
                    $after = implode(' ', array_slice($words, $i + 2));
                    $retry = $this->parseInternal($before . ', ' . $after);
                    $retryScore = $this->scoreResult($retry);
                    if ($retryScore > $bestScore) {
                        $bestResult = $retry;
                        $bestScore = $retryScore;
                    }
                }
            }
        }

        return $bestResult;
    }
}
