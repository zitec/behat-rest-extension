<?php

namespace Zitec\ApiZitecExtension\Util;

use DateTime;

/**
 * Class TypeChecker
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class TypeChecker
{
    /**
     * @var array
     */
    protected $dataTypes = [
        'string' => 'checkString',
        'integer' => 'checkInteger',
        'float' => 'checkFloat',
        'array' => 'checkArray',
        'boolean' => 'checkBoolean',
        'date' => 'checkDate',
        'email' => 'checkEmail',
        'url' => 'checkUrl',
        'regex' => 'checkRegex',
        'list' => 'checkValueInList',
        'numeric' => 'checkNumeric',
    ];

    /**
     * Parse the expected result and matches the right function to be called.
     * Returns an array of error messages for each key in the expected response.
     *
     * @param array $current
     * @param array $expected
     * @return array|string
     */
    public function checkType(array $current, array $expected)
    {
        $noMatch = [];
        foreach ($expected as $key => &$value) {
            // Ignore keys that contain info about collections.
            if ((strpos($key, '__info_collection_') === 0)) {
                continue;
            }
            // Manage the flow in case of collections.
            if (strpos($key, '__collection_') === 0) {
                $collectionErr = $this->manageCollection($expected, $key, $current);
                if (!empty($collectionErr)) {
                    $noMatch = array_merge($noMatch, $collectionErr);
                }
                continue;
            }

            if (!array_key_exists($key, $current) ) {
                $noMatch[$key] = 'Expected key not found in response.';
                continue;
            }

            if (is_array($value)) {
                $message = $this->checkType($current[$key], $value);
                if (!empty($message)) {
                    $noMatch[$key] = $message;
                }
                unset($current[$key]);
                continue;
            }
            // Get the data type arguments, if there are any.
            if ($pos = strpos($value, '(')) {
                $type = substr($value, 0, $pos);
                $arguments = trim(substr($value, $pos + 1, strpos($value, ')') - $pos - 1), ')');
                $arguments = $type == 'regex' ? [$arguments] : explode(',', $arguments);
                $arguments = array_map('trim', $arguments);
            } else {
                $type = $value;
                $arguments = [];
            }
            // Check for null option.
            if ($pos = strpos($value, '|')) {
                $type = strpos($type, '|') ? trim(substr($value, 0, $pos)) : $type;
                if (is_null($current[$key])) {
                    unset($current[$key]);
                    continue;
                }
            }

            array_unshift($arguments, $current[$key]);
            if (array_key_exists($type, $this->dataTypes)) {
                $result = call_user_func_array([$this, $this->dataTypes[$type]], $arguments);
                if (is_string($result)) {
                    $noMatch[$key] = $result;
                }
            } else {
                $noMatch[$key] = "Undefined data type.";
            }
            unset($current[$key]);
        }
        // If all response elements were unset in foreach, it means that all response elements were expected.
        if (!empty($current)) {
            $noMatch['unexpected data'] = $current;
        }
        if (!empty($noMatch)) {
            return $noMatch;
        }
    }

    /**
     * Parse the collection info in both situations
     * (when the collection is root in dataset and when the collection is a branch in dataset)
     *
     * @param array $expected
     * @param string $key
     * @param array $current
     * @return array|string
     */
    public function manageCollection(&$expected, $key, &$current)
    {
        $value = $expected[$key];
        $noMatch = [];

        $collectionInfoKey = $this->getCollectionInfoKey($key);
        if (!isset($expected[$collectionInfoKey])) {
            $noMatch[$key] = 'There is no info about the collection.';

            return $noMatch;
        }
        $collectionInfo = $expected[$collectionInfoKey];
        unset($expected[$collectionInfoKey]);

        try {
            $collectionInfo = $this->validateCollectionInfo($collectionInfo);
        } catch (\InvalidArgumentException $ex) {
            $messageKey = $collectionInfo['name'] ? $collectionInfo['name'] : 'unknown';
            $noMatch[$messageKey] = $ex->getMessage();
            unset($current[$messageKey]);

            return $noMatch;
        }

        if (!is_array($value)) {
            $noMatch[$collectionInfo['name']] = "The collection must be array.";

            return $noMatch;
        }

        $response = $this->checkCollection($collectionInfo, $value, $current);
        if ($collectionInfo['name'] == '-') {
            $current = [];
            empty($response) ? $noMatch = [] : $noMatch[] = $response;
        } else {
            unset($current[$collectionInfo['name']]);
            if (!empty($response)) {
                $noMatch[$collectionInfo['name']] = $response;
            }
        }

        return $noMatch;
    }

    /**
     * Extract collection information key.
     * Example: collection key: __collection_users
     *          collection information key should be like: __info_collection_users
     *
     * @param $currentKey
     * @throws \Exception
     * @return string
     */
    public function getCollectionInfoKey($currentKey)
    {
        if (strpos($currentKey, '__collection_') !== 0) {
            throw new \Exception('');
        }

        $collectionName = str_replace('__collection_', '', $currentKey);

        return '__info_collection_' . $collectionName;
    }

    /**
     * Validate collection information
     *
     * @param array $collectionInfo
     * @return array
     */
    private function validateCollectionInfo(array $collectionInfo)
    {
        if (empty($collectionInfo['name'])) {
            throw new \InvalidArgumentException('Collection name must be specified');
        }

        $min = $collectionInfo['min'];
        $max = $collectionInfo['max'];


        if ((isset($min) && !isset($max )) || (!isset($min) && isset($max))) {
            throw new \InvalidArgumentException("'Min' and 'Max' should be set both or none.");
        }

        if (isset($min) && isset($max)) {
            $maxValidation = $this->checkNumericArguments($max);

            if (isset($maxValidation)) {
                throw new \InvalidArgumentException($maxValidation);
            }

            if ($min === '*' || !is_numeric($min)) {
                throw new \InvalidArgumentException(
                    " 'Min' argument should be only numeric, '*' value is not accepted."
                );
            }
        }

        return $collectionInfo;
    }


    /**
     * A collection can be defined as: __collection(key, min, max).
     * Min and max represent the number of elements expected in collection and they are optional.
     * To represent an open range can be used "*" instead of min/max.
     * If the collection has no key use "-" .
     *
     * Checks each element in a collection to match the expected element structure.
     * It also checks the number if elements in the collection if the expected number of elements is set.
     *
     * @param array $collectionInfo
     * @param array $expectedValue
     * @param array $current
     * @return array|string
     */
    public function checkCollection(array $collectionInfo, array $expectedValue, array $current)
    {
        $expectedKey = $collectionInfo['name'];

        if ($expectedKey == '-') {
            $currentValue = $current;
            if (!is_array($current)) {
                return ("The response is not a collection.");
            }
        } else {
            $currentValue = $current[$expectedKey];
            if (!array_key_exists($expectedKey, $current) || !is_array($currentValue)) {
                return sprintf('There is no collection with the key: %s', $expectedKey);
            }
        }

        if (isset($collectionInfo['min']) && isset($collectionInfo['max'])) {
            $min = $collectionInfo['min'];
            $max = $collectionInfo['max'];
            $elements = count($currentValue);
            if (!$this->valueInInterval($elements, $min, $max)) {
                $message = sprintf(
                    'The number of elements in collection ' . $expectedKey . ' is '
                    . $elements . ' but should be between ' . $min . ' and ' . $max
                );

                return $message;
            }
        }

        if ((empty($currentValue) && !empty($expectedValue)) && (null === $collectionInfo['min'] || $collectionInfo['min'] > 0)) {
            $noMatch = [];
            foreach ($expectedValue as $key => $value) {
                if (!array_key_exists($key, $currentValue)) {
                    $noMatch[$key] = "Expected key not found in response.";
                }
            }

            return $noMatch;
        }

        foreach ($currentValue as $key => $value) {
            if (!empty($result = $this->checkType($value, $expectedValue))) {
                return $result;
            }
        }

        return null;
    }

    /**
     *  Checks if the first given argument is string.
     *  If there are 3 arguments it checks the string length to be between those two.
     *  The second argument is considered minimum value and the third is considered the maximum.
     *  If there are only two parameters it checks the string length to be exactly that value.
     *
     * @param string $string The string to be checked
     * @return bool|string
     * @throws \Exception
     */
    public function checkString($string)
    {
        $message = false;
        $type = gettype($string);
        if ($type != "string") {
            $message = sprintf('Expected string but %s given,', $type);
        }
        $length = strlen($string);
        $arguments = func_get_args();
        if (count($arguments) > 2) {
            $min = $arguments[1];
            $max = $arguments[2];
            if (is_string($result = $this->checkNumericArguments($min, $max))) {
                return $result;
            }

            if (!$this->valueInInterval($length, $min, $max)) {
                $message = sprintf("The string's length (%s) should be between %s and %s", $length, $min, $max);
            }

        } elseif (count($arguments) > 1) {
            if (is_string($result = $this->checkNumericArguments($arguments[1]))) {
                return $result;
            }
            $fixedLength = $arguments[1];

            if ($length != $fixedLength) {
                $message = sprintf("The string's length(%s) is  not %s", $length, $fixedLength);
            }
        }
        if (is_string($message)) {
            return $message;
        }

        return true;
    }

    /**
     * Checks the first argument to be integer and between the interval, if there are 3 arguments.
     * If there are only two argument, it is checked the number of digits.
     * It also support open intervals.
     *
     *
     * @param int $int
     * @return string
     */
    public function checkInteger($int)
    {
        $type = gettype($int);
        if ($type != "integer") {
            return sprintf('Expected integer but %s given.', $type);
        }

        $arguments = func_get_args();

        if (count($arguments) > 2) {
            if (is_string($result = $this->checkNumericArguments($arguments[1], $arguments[2]))) {
                return $result;
            }
            $min = $arguments[1];
            $max = $arguments[2];

            if (!$this->valueInInterval($int, $min, $max)) {
                return sprintf('%d is not betweend %s and %s', $int, $min, $max);
            }
        } elseif (count($arguments) > 1) {
            if (is_string($result = $this->checkNumericArguments($arguments[1]))) {
                return $result;
            }
            $numberOfDigits = $arguments[1];
            if (strlen((string)$int) != $numberOfDigits) {
                return sprintf('%d should have %s digits.', $int, $numberOfDigits);
            }
        }
    }

    /**
     * Checks the first argument to be float.
     * If there are 3 arguments it checks the float to be in the interval given.
     * If there are 2 arguments it checks the number of decimals.
     *
     * @param float $float
     * @return string
     */
    public function checkFloat($float)
    {
        $type = gettype($float);
        if ($type != "double") {
            return sprintf('Expected float but %s given.', $type);
        }

        $arguments = func_get_args();

        if (count($arguments) > 2) {
            if (is_string($result = $this->checkNumericArguments($arguments[1], $arguments[2]))) {
                return $result;
            }
            $min = $arguments[1];
            $max = $arguments[2];

            if (!$this->valueInInterval($float, $min, $max)) {
                return sprintf('%f is not betweend %s and %s.', $float, $min, $max);
            }
        } elseif (count($arguments) > 1) {
            if (is_string($result = $this->checkNumericArguments($arguments[1]))) {
                return $result;
            }
            $numberOfDecimals = $arguments[1];
            $actualDecimals = strlen((string)$float) - strpos((string)$float, '.') - 1;
            if ($actualDecimals != $numberOfDecimals) {
                return sprintf('%f should have %d decimals.', $float, $numberOfDecimals);
            }
        }
    }

    /**
     * Checks the first argument to be array.
     * If the second argument is set, it checks the number of elements in array.
     *
     * @param array $array
     * @return string
     */
    public function checkArray($array)
    {
        $type = gettype($array);
        if ($type != "array") {
            return sprintf('Expected array but %s given.', $type);
        }
        $arguments = func_get_args();
        if (count($arguments) > 1) {
            if (is_string($result = $this->checkNumericArguments($arguments[1]))) {
                return $result;
            }
            $elements = $arguments[1];
            if (count($array) != $elements) {
                return sprintf(
                    '%s have %d elements but it should have %d elements.',
                    json_encode($array),
                    count($array),
                    $elements
                );
            }
        }

    }

    /**
     * Checks the argument to be boolean.
     *
     * @param bool $boolean
     * @return string
     */
    public function checkBoolean($boolean)
    {
        $type = gettype($boolean);
        if ($type != "boolean") {
            return sprintf('Expected boolean but %s given.', $type);
        }
    }


    /**
     * Checks the first argument to be a date formatted according to the
     * specified format in the second argument.
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public function checkDate($date, $format)
    {
        if (empty($format)) {
            return 'Date format is mandatory.';
        }
        $dateTime = DateTime::createFromFormat($format, $date);
        if (!($dateTime && $dateTime->format($format) == $date)) {
            return sprintf('%s does not respect the given format %s', $date, $format);
        }
    }

    /**
     * Checks the argument to be a valid email.
     *
     * @param string $email
     * @return string
     */
    public function checkEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return sprintf('%s is not a valid email address.', $email);
        }
    }

    /**
     * Checks the argument to be a valid url.
     *
     * @param string $url
     * @return string
     */
    public function checkUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return sprintf('%s is not a valid url.', $url);
        }
    }

    /**
     * Checks the first argument to match the pattern given in the second argument.
     *
     * @param string $value
     * @param string $regex
     * @return string
     */
    public function checkRegex($value, $regex)
    {
        if (empty($regex)) {
            return 'The pattern cannot be empty.';
        }
        if (!preg_match($regex, $value)) {
            return sprintf('%s does not match the pattern.', $value);
        }
    }

    /**
     * Checks the first argument to be in the list(the other arguments).
     *
     * @param string $value
     * @return string
     */
    public function checkValueInList($value)
    {
        $arguments = func_get_args();
        unset($arguments[0]);
        if (empty($arguments)) {
            return 'The list cannot be empty.';
        }
        if (!in_array($value, $arguments)) {
            return sprintf('%s was not found in %s', $value, implode(', ', $arguments));
        }

    }

    /**
     * Checks the argument to be numeric.
     *
     * @param string $number
     * @return string
     */
    public function checkNumeric($number)
    {
        if (!is_numeric($number)) {
            return sprintf("%s is not numeric.", $number);
        }
    }

    /**
     * Checks the given arguments to be numeric or '*'.
     *
     * @return string
     */
    public function checkNumericArguments()
    {
        $arguments = func_get_args();
        foreach ($arguments as $argument) {
            if (!is_numeric($argument) && trim($argument) !== '*') {
                return sprintf("%s should be numeric or '*'.", $argument);
            }
        }
    }

    /**
     * Checks the value to be between min and max.
     * The * represents an open interval.
     *
     * @param string $value
     * @param string $min
     * @param string $max
     * @return bool
     */
    public function valueInInterval($value, $min, $max)
    {
        $min = trim($min);
        $max = trim($max);
        if (is_numeric($min) && is_numeric($max)) {
            $condition = ($value >= $min && $value <= $max);
        } elseif ($min == '*' && $max == '*') {
            $condition = true;
        } elseif ($min == "*") {
            $condition = $value <= $max;

        } elseif ($max == "*") {
            $condition = $value >= $min;
        } else {
            return false;
        }

        return $condition;
    }
}
