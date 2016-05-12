<?php
/**
 * Created by PhpStorm.
 * User: bianca.vadean
 * Date: 1/19/2016
 * Time: 12:22 PM
 */

namespace Zitec\ApiZitecExtension\Util;

use DateTime;

class TypeChecker
{
    /**
     * @var array
     */
    protected $dataTypes = array(
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
    );

    /**
     * Parse the expected result and matches the right function to be called.
     * Returns an array of error messages for each key in the expected response.
     *
     * @param array $current
     * @param array $expected
     * @return array|string
     */
    public function checkType (array $current, array $expected)
    {
        $noMatch = array();
        foreach ($expected as $key => $value) {
            // Manage the flow in case if collections.
            if (substr($key, 0, strpos($key, '(')) == "__collection") {
                $response = $this->checkCollection($key, $value, $current);
                preg_match_all("/\((.*?)\)/u", $key, $collectionArgs);
                $collectionArgs = explode(',', $collectionArgs[1][0]);
                if ($collectionArgs[0] == '-') {
                    $current = array();
                    $noMatch = empty($response) ? array() : $response;
                } else {
                    unset($current[$collectionArgs[0]]);
                    if (!empty($response)) {
                        $noMatch[$collectionArgs[0]] = $response;
                    }
                }
                continue;
            }

            if (!array_key_exists($key, $current)) {
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
                $arguments = $type == 'regex' ? array($arguments) : explode(',', $arguments);
                $arguments = array_map('trim', $arguments);
            } else {
                $type = $value;
                $arguments = array();
            }
            // Check for null option.
            if ($pos = strpos($value, '|')) {
                $type = strpos($type, '|') ? trim(substr($value, 0, $pos)) : $type;
                $option = trim(substr($value, $pos + 1));
                if (is_null($current[$key])) {
                    unset($current[$key]);
                    continue;
                }
            }

            array_unshift($arguments, $current[$key]);
            if (array_key_exists($type, $this->dataTypes)) {
                $result = call_user_func_array(array($this, $this->dataTypes[$type]), $arguments);
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
     * Checks each element in a collection to match the expected element structure.
     * It also checks the number if elements in the collection if the expected number of elements is set.
     *
     * @param $collectionKey
     * @param array $expectedValue
     * @param array $current
     * @return array|string
     */
    public function checkCollection ($collectionKey, array $expectedValue, array $current)
    {
        preg_match_all("/\((.*?)\)/u", $collectionKey, $arguments);
        $arguments = explode(',', $arguments[1][0]);
        $arguments = array_map('trim', $arguments);

        $expectedKey = $arguments[0];

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

        if(isset($arguments[1]) && isset($arguments[2])) {
            $min = $arguments[1];
            $max = $arguments[2];
            $elements = count($currentValue);
            if (!$this->valueInInterval($elements, $min, $max)) {
                $message = sprintf('The number of elements in collection ' . $expectedKey . ' is ' . $elements . ' but should be between ' . $min . ' and ' . $max);
                return $message;
            }
        }

        foreach ($currentValue as $key => $value) {
            if(!empty($result = $this->checkType($value, $expectedValue))) {
                return $result;
            }
        }


    }

    /**
     *  Checks if the first given argument is string.
     *  If there are 3 arguments it checks the string length to be between those two.
     *  The second argument is considered minimum value and the third is considered the maximum.
     *  If there are only two parameters it checks the string length to be exactly that value.
     *
     * @param $string  The string to be checked
     * @return bool|string
     * @throws \Exception
     */
    public function checkString ($string)
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
     * @param $int
     * @return string
     */
    public function checkInteger ($int)
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
     * @param $float
     * @return string
     */
    public function checkFloat ($float)
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
     * @param $array
     * @return string
     */
    public function checkArray ($array)
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
                return sprintf('%s have %d elements but it should have %d elements.', json_encode($array), count($array), $elements);
            }
        }

    }

    /**
     * Checks the argument to be boolean.
     *
     * @param $boolean
     * @return string
     */
    public function checkBoolean ($boolean)
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
     * @param $date
     * @param $format
     * @return string
     */
    public function checkDate ($date, $format)
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
     * @param $email
     * @return string
     */
    public function checkEmail ($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return sprintf('%s is not a valid email address.', $email);
        }
    }

    /**
     * Checks the argument to be a valid url.
     *
     * @param $url
     * @return string
     */
    public function checkUrl ($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return sprintf('%s is not a valid url.', $url);
        }
    }

    /**
     * Checks the first argument to match the pattern given in the second argument.
     *
     * @param $value
     * @param $regex
     * @return string
     */
    public function checkRegex ($value, $regex)
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
     * @param $value
     * @return string
     */
    public function checkValueInList ($value)
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
     * @param $number
     * @return string
     */
    public function checkNumeric($number)
    {
        if(!is_numeric($number)) {
            return sprintf("%s is not numeric.", $number);
        }
    }

    /**
     * Checks the given arguments to be numeric or '*'.
     *
     * @return string
     */
    public function checkNumericArguments ()
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
     * @param $value
     * @param numeric $min
     * @param numeric $max
     * @return bool
     */
    public function valueInInterval ($value, $min, $max)
    {
        if (is_numeric($min) && is_numeric($max)) {
            $condition = ($value >= $min && $value <= $max);
        } elseif ($min == '*' && $max == '*'){
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
