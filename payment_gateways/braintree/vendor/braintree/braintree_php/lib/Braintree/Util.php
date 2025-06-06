<?php

namespace Braintree;

use DateTime;
use InvalidArgumentException;

/**
 * Braintree Utility methods
 */

class Util
{
    /**
     * extracts an attribute and returns an array of objects
     *
     * extracts the requested element from an array, and converts the contents
     * of its child arrays to objects of type $attributeName, or returns
     * an array with a single element containing the value of that array element
     *
     * @param array  $attribArray   attributes from a search response
     * @param string $attributeName indicates which element of the passed array to extract
     *
     * @return array array of $attributeName objects, or a single element array
     */
    public static function extractAttributeAsArray(&$attribArray, $attributeName)
    {
        if (!isset($attribArray[$attributeName])) :
            return [];
        endif;

        // get what should be an array from the passed array
        $data = $attribArray[$attributeName];
        // set up the class that will be used to convert each array element
        $classFactory = self::buildClassName($attributeName) . '::factory';
        if (is_array($data)) :
            // create an object from the data in each element
            $objectArray = array_map($classFactory, $data);
        else :
            return [$data];
        endif;

        unset($attribArray[$attributeName]);
        return $objectArray;
    }
    /**
     * throws an exception based on the type of error
     *
     * @param string      $statusCode HTTP status code to throw exception from
     * @param null|string $message    optional
     *
     * @throws Exception multiple types depending on the error
     *
     * @return void
     */
    public static function throwStatusCodeException($statusCode, $message = null)
    {
        switch ($statusCode) {
            case 401:
                throw new Exception\Authentication();
            break;
            case 403:
                if (is_null($message)) {
                    $message = "";
                }
                throw new Exception\Authorization($message);
            break;
            case 404:
                throw new Exception\NotFound();
            break;
            case 408:
                throw new Exception\RequestTimeout();
            break;
            case 426:
                throw new Exception\UpgradeRequired();
            break;
            case 429:
                throw new Exception\TooManyRequests();
            break;
            case 500:
                throw new Exception\ServerError($message);
            break;
            case 504:
                throw new Exception\GatewayTimeout();
            break;
            default:
                throw new Exception\Unexpected('Unexpected HTTP_RESPONSE #' . $statusCode);
            break;
        }
    }

    /**
     * throws an exception based on the type of error returned from graphql
     *
     * @param array $response complete graphql response
     *
     * @throws Exception multiple types depending on the error
     *
     * @return void
     */
    public static function throwGraphQLResponseException($response)
    {
        // phpcs:ignore
        if (!array_key_exists("errors", $response) || !($errors = $response["errors"])) {
            return;
        }

        foreach ($errors as $error) {
            $message = $error["message"];
            // phpcs:ignore
            if (!array_key_exists("extensions", $error)) {
                return;
            }
            if ($error["extensions"] == null) {
                throw new Exception\Unexpected("Unexpected exception:" . $message);
            }

            $errorClass = $error["extensions"]["errorClass"];

            if ($errorClass == "VALIDATION") {
                continue;
            } elseif ($errorClass == "AUTHENTICATION") {
                throw new Exception\Authentication();
            } elseif ($errorClass == "AUTHORIZATION") {
                throw new Exception\Authorization($message);
            } elseif ($errorClass == "NOT_FOUND") {
                throw new Exception\NotFound();
            } elseif ($errorClass == "UNSUPPORTED_CLIENT") {
                throw new Exception\UpgradeRequired();
            } elseif ($errorClass == "RESOURCE_LIMIT") {
                throw new Exception\TooManyRequests();
            } elseif ($errorClass == "INTERNAL") {
                throw new Exception\ServerError();
            } elseif ($errorClass == "SERVICE_AVAILABILITY") {
                throw new Exception\ServiceUnavailable();
            } else {
                throw new Exception\Unexpected('Unexpected exception ' . $message);
            }
        }
    }

    /**
     * Returns a class object or throws an exception
     *
     * @param string $className to be used to determine if objects are present
     * @param object $resultObj the object returned from an API response
     *
     * @throws Exception\ValidationsFailed
     *
     * @return object returns the passed object if successful
     */
    public static function returnObjectOrThrowException($className, $resultObj)
    {
        $resultObjName = self::cleanClassName($className);
        if ($resultObj->success) {
            return $resultObj->$resultObjName;
        } else {
            throw new Exception\ValidationsFailed();
        }
    }

    /**
     * removes the  header from a classname
     *
     * @param string $name ClassName
     *
     * @return camelCased classname minus  header
     */
    public static function cleanClassName($name)
    {
        $classNamesToResponseKeys = [
            'Braintree\CreditCard' => 'creditCard',
            'Braintree\CreditCardGateway' => 'creditCard',
            'Braintree\Customer' => 'customer',
            'Braintree\CustomerGateway' => 'customer',
            'Braintree\Subscription' => 'subscription',
            'Braintree\SubscriptionGateway' => 'subscription',
            'Braintree\Transaction' => 'transaction',
            'Braintree\TransactionGateway' => 'transaction',
            'Braintree\CreditCardVerification' => 'verification',
            'Braintree\CreditCardVerificationGateway' => 'verification',
            'Braintree\AddOn' => 'addOn',
            'Braintree\AddOnGateway' => 'addOn',
            'Braintree\Discount' => 'discount',
            'Braintree\DiscountGateway' => 'discount',
            'Braintree\Dispute' => 'dispute',
            'Braintree\Dispute\EvidenceDetails' => 'evidence',
            'Braintree\DocumentUpload' => 'documentUpload',
            'Braintree\Plan' => 'plan',
            'Braintree\PlanGateway' => 'plan',
            'Braintree\Address' => 'address',
            'Braintree\AddressGateway' => 'address',
            'Braintree\SettlementBatchSummary' => 'settlementBatchSummary',
            'Braintree\SettlementBatchSummaryGateway' => 'settlementBatchSummary',
            'Braintree\Merchant' => 'merchant',
            'Braintree\MerchantGateway' => 'merchant',
            'Braintree\MerchantAccount' => 'merchantAccount',
            'Braintree\MerchantAccountGateway' => 'merchantAccount',
            'Braintree\OAuthCredentials' => 'credentials',
            'Braintree\OAuthResult' => 'result',
            'Braintree\PayPalAccount' => 'paypalAccount',
            'Braintree\PayPalAccountGateway' => 'paypalAccount',
            'Braintree\UsBankAccountVerification' => 'usBankAccountVerification',
        ];

        return $classNamesToResponseKeys[$name];
    }

    /**
     * Returns corresponding class name based on response keys
     *
     * @param string $name className
     *
     * @return string ClassName
     */
    public static function buildClassName($name)
    {
        $responseKeysToClassNames = [
            'creditCard' => 'Braintree\CreditCard',
            'customer' => 'Braintree\Customer',
            'dispute' => 'Braintree\Dispute',
            'documentUpload' => 'Braintree\DocumentUpload',
            'subscription' => 'Braintree\Subscription',
            'transaction' => 'Braintree\Transaction',
            'verification' => 'Braintree\CreditCardVerification',
            'addOn' => 'Braintree\AddOn',
            'discount' => 'Braintree\Discount',
            'plan' => 'Braintree\Plan',
            'address' => 'Braintree\Address',
            'settlementBatchSummary' => 'Braintree\SettlementBatchSummary',
            'merchantAccount' => 'Braintree\MerchantAccount',
        ];

        return (string) $responseKeysToClassNames[$name];
    }

    /**
     * convert alpha-beta-gamma to alphaBetaGamma
     *
     * @param string      $string    to be scrubbed for camelCase formatting
     * @param null|string $delimiter to be replaced
     *
     * @return string modified string
     */
    public static function delimiterToCamelCase($string, $delimiter = '[\-\_]')
    {
        static $callback = null;
        if ($callback === null) {
            $callback = function ($matches) {
                return strtoupper($matches[1]);
            };
        }

        return preg_replace_callback('/' . $delimiter . '(\w)/', $callback, $string);
    }

    /**
     * convert alpha-beta-gamma to alpha_beta_gamma
     *
     * @param string $string to be modified
     *
     * @return string modified string
     */
    public static function delimiterToUnderscore($string)
    {
        return preg_replace('/-/', '_', $string);
    }


    /**
     * find capitals and convert to delimiter + lowercase
     *
     * @param string      $string    to be scrubbed
     * @param null|string $delimiter to replace camelCase
     *
     * @return string modified string
     */
    public static function camelCaseToDelimiter($string, $delimiter = '-')
    {
        return strtolower(preg_replace('/([A-Z])/', "$delimiter\\1", $string));
    }

    /**
     * converts a-string-here to [aStringHere]
     *
     * @param array       $array     to be iterated over
     * @param null|string $delimiter to be replaced with camelCase
     *
     * @return array modified array
     */
    public static function delimiterToCamelCaseArray($array, $delimiter = '[\-\_]')
    {
        $converted = [];
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                $key = self::delimiterToCamelCase($key, $delimiter);
            }

            if (is_array($value)) {
                // Make an exception for custom fields, which must be underscore (can't be
                // camelCase).
                if ($key === 'customFields') {
                    $value = self::delimiterToUnderscoreArray($value);
                } else {
                    $value = self::delimiterToCamelCaseArray($value, $delimiter);
                }
            }
            $converted[$key] = $value;
        }
        return $converted;
    }

    /**
     * find capitals and convert to delimiter + lowercase
     *
     * @param array       $array     to be iterated over
     * @param null|string $delimiter to replace camelCase
     *
     * @return array modified array
     */
    public static function camelCaseToDelimiterArray($array, $delimiter = '-')
    {
        $converted = [];
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                $key = self::camelCaseToDelimiter($key, $delimiter);
            }
            if (is_array($value)) {
                $value = self::camelCaseToDelimiterArray($value, $delimiter);
            }
            $converted[$key] = $value;
        }
        return $converted;
    }

    /**
     * converts a-string-here to [a_string_here]
     *
     * @param array $array to be iterated over
     *
     * @return array modified array
     */
    public static function delimiterToUnderscoreArray($array)
    {
        $converted = [];
        foreach ($array as $key => $value) {
            $key = self::delimiterToUnderscore($key);
            $converted[$key] = $value;
        }
        return $converted;
    }

    /**
     * Join arrays with string or return false
     *
     * @param array  $array     associative array to implode
     * @param string $separator (optional, defaults to =)
     * @param string $glue      (optional, defaults to ', ')
     * @param string $parens    parentheses to enclose nested arrays (optional, defaults to '[]')
     *
     * @return string|false
     */
    public static function implodeAssociativeArray($array, $separator = '=', $glue = ', ', $parens = '[]')
    {
        // build a new array with joined keys and values
        $tmpArray = null;
        foreach ($array as $key => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('r');
            }
            if (is_array($value)) {
                $nested_value = self::implodeAssociativeArray($value);
                $tmpArray[] = $key . $separator . $parens[0] . $nested_value . $parens[1];
            } else {
                $tmpArray[] = $key . $separator . $value;
            }
        }
        // implode and return the new array
        return (is_array($tmpArray)) ? implode($glue, $tmpArray) : false;
    }

    /*
     * Turn all attributes into a string
     *
     * @param array $attributes to be turned into a string
     *
     * @return string|false
     */
    public static function attributesToString($attributes, $preserveIndexedArray = false)
    {
        if ($preserveIndexedArray && self::isIndexedArray($attributes)) {
            return '[' . implode(',', $attributes) . ']';
        }
        $printableAttribs = [];
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $pAttrib = self::attributesToString($value, $preserveIndexedArray);
            } elseif ($value instanceof DateTime) {
                $pAttrib = $value->format(DateTime::RFC850);
            } else {
                $pAttrib = $value;
            }
            $printableAttribs[$key] = sprintf('%s', $pAttrib);
        }
        return self::implodeAssociativeArray($printableAttribs);
    }

    private static function isIndexedArray($array)
    {
        return array_keys($array) == range(0, count($array) - 1);
    }

    /**
     * verify user request structure
     *
     * compares the expected signature of a gateway request
     * against the actual structure sent by the user
     *
     * @param array $signature  expected signature
     * @param array $attributes actual structure sent by user
     *
     * @throws InvalidArgumentException
     *
     * @return self
     */
    public static function verifyKeys($signature, $attributes)
    {
        $validKeys = self::_flattenArray($signature);
        $userKeys = self::_flattenUserKeys($attributes);
        $invalidKeys = array_diff($userKeys, $validKeys);
        $invalidKeys = self::_removeWildcardKeys($validKeys, $invalidKeys);

        if (!empty($invalidKeys)) {
            asort($invalidKeys);
            $sortedList = join(', ', $invalidKeys);
            throw new InvalidArgumentException('invalid keys: ' . $sortedList);
        }
    }

    /**
     * replaces the value of a key in an array
     *
     * @param array  $array  to have key replaced
     * @param string $oldKey to be replace
     * @param string $newKey to replace
     *
     * @return array
     */
    public static function replaceKey($array, $oldKey, $newKey)
    {
        // phpcs:ignore
        if (array_key_exists($oldKey, $array)) {
            $array[$newKey] = $array[$oldKey];
            unset($array[$oldKey]);
        }
        return $array;
    }

    /**
     * flattens a numerically indexed nested array to a single level
     *
     * @param array  $keys
     * @param string $namespace
     *
     * @return array
     */
    private static function _flattenArray($keys, $namespace = null)
    {
        $flattenedArray = [];
        foreach ($keys as $key) {
            if (is_array($key)) {
                $theKeys = array_keys($key);
                $theValues = array_values($key);
                $scope = $theKeys[0];
                $fullKey = empty($namespace) ? $scope : $namespace . '[' . $scope . ']';
                $flattenedArray = array_merge($flattenedArray, self::_flattenArray($theValues[0], $fullKey));
            } else {
                $fullKey = empty($namespace) ? $key : $namespace . '[' . $key . ']';
                $flattenedArray[] = $fullKey;
            }
        }
        sort($flattenedArray);
        return $flattenedArray;
    }

    private static function _flattenUserKeys($keys, $namespace = null)
    {
        $flattenedArray = [];

        foreach ($keys as $key => $value) {
            $fullKey = empty($namespace) ? $key : $namespace;
            if (!is_numeric($key) && $namespace != null) {
                $fullKey .= '[' . $key . ']';
            }
            if (is_numeric($key) && is_string($value)) {
                $fullKey .= '[' . $value . ']';
            }
            if (is_array($value)) {
                $more = self::_flattenUserKeys($value, $fullKey);
                $flattenedArray = array_merge($flattenedArray, $more);
            } else {
                $flattenedArray[] = $fullKey;
            }
        }
        sort($flattenedArray);
        return $flattenedArray;
    }

    /**
     * removes wildcard entries from the invalid keys array
     *
     * @param array  $validKeys
     * @param <array $invalidKeys
     *
     * @return array
     */
    private static function _removeWildcardKeys($validKeys, $invalidKeys)
    {
        foreach ($validKeys as $key) {
            if (stristr($key, '[_anyKey_]')) {
                $wildcardKey = str_replace('[_anyKey_]', '', $key);
                foreach ($invalidKeys as $index => $invalidKey) {
                    if (stristr($invalidKey, $wildcardKey)) {
                        unset($invalidKeys[$index]);
                    }
                }
            }
        }
        return $invalidKeys;
    }
}
