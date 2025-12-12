<?php

namespace Payconiq;

// Require the exception classes
require_once __DIR__ . '/Support/Exceptions/CreatePaymentFailedException.php';
require_once __DIR__ . '/Support/Exceptions/RetrievePaymentFailedException.php';
require_once __DIR__ . '/Support/Exceptions/GetPaymentsListFailedException.php';
require_once __DIR__ . '/Support/Exceptions/RefundFailedException.php';
require_once __DIR__ . '/Support/Exceptions/GetRefundIbanFailedException.php';

use Payconiq\Support\Exceptions\CreatePaymentFailedException;
use Payconiq\Support\Exceptions\RetrievePaymentFailedException;
use Payconiq\Support\Exceptions\GetPaymentsListFailedException;
use Payconiq\Support\Exceptions\RefundFailedException;
use Payconiq\Support\Exceptions\GetRefundIbanFailedException;

class Client {

    const ENVIRONMENT_PROD = 'prod';
    const ENVIRONMENT_EXT = 'ext';
    
    // Toegestane karakters volgens EPC217-08 SEPA Conversion Table
    const ALLOWED_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,:?+-/()'";

    protected $apiKey;
    protected $endpoint;

    /**
     * Construct
     *
     * @param  string $apiKey		Used to secure request between merchant backend and Payconiq backend.
     * @param  string $environment	Environment to use when making API calls
     * 
     * @return void
     */
    public function __construct( $apiKey = null, $environment = self::ENVIRONMENT_PROD ) {
        $this->apiKey = $apiKey;
        $this->endpoint = $environment == self::ENVIRONMENT_PROD
            ? 'https://merchant.api.bancontact.net/v3'
            : 'https://merchant.api.preprod.bancontact.net/v3';
    }

    /**
     * Set the endpoint
     *
     * @param  string $url  The endpoint of the Payconiq API.
     *
     * @return self
     */
    public function setEndpoint( $url ) {
        $this->endpoint = $url;

        return $this;
    }

    /**
     * Set the endpoint to test env
     *
     * @return self
     */
    public function setEndpointTest() {
        $this->endpoint = 'https://merchant.api.preprod.bancontact.net/v3';

        return $this;
    }

    /**
     * Set the API key
     *
     * @param  string $apiKey  Used to secure request between merchant backend and Payconiq backend.
     *
     * @return self
     */
    public function setApiKey( $apiKey ) {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Convert string to EPC217-08 SEPA compliant format
     *
     * @param  string $input  Original string
     * @param  int    $maxLength  Maximum length (optional)
     * 
     * @return string  Converted string
     */
    private function convertToSEPA($input, $maxLength = null) {
        // Convert to UTF-8 if not already
        if (!mb_detect_encoding($input, 'UTF-8', true)) {
            $input = mb_convert_encoding($input, 'UTF-8');
        }
        
        // Normalize: remove diacritics/accents
        $input = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        
        // Filter to only allowed characters
        $result = '';
        $allowedChars = self::ALLOWED_CHARS;
        
        for ($i = 0; $i < mb_strlen($input); $i++) {
            $char = mb_substr($input, $i, 1);
            if (strpos($allowedChars, $char) !== false) {
                $result .= $char;
            }
        }
        
        // Trim and replace multiple spaces with single space
        $result = trim(preg_replace('/\s+/', ' ', $result));
        
        // Apply max length if specified
        if ($maxLength !== null && mb_strlen($result) > $maxLength) {
            $result = mb_substr($result, 0, $maxLength);
        }
        
        return $result;
    }

    /**
     * Create a new payment
     * 
     * @param  float $amount		Payment amount in cents
     * @param  string $currency		Payment currency code in IOS 4217 format
     * @param  string $description	Payment description shown during payment (optional)
     * @param  string $reference	External payment reference used to reference the Payconiq payment in the calling party's system (optional)
     * @param  string $bulkId	    BulkId for bulk payouts (optional)
     * @param  string $callbackUrl  A url to which the merchant or partner will be notified of a payment (optiona)
     * @param  string $returnUrl  Return url to return client after paying on payconiq site itself (optional)
     * 
     * @return object  payment object
     * @throws CreatePaymentFailedException  If the response has no transactionid
     */
    public function createPayment( $amount, $currency = 'EUR', $description='', $reference='', $bulkId='', $callbackUrl='', $returnUrl = null ) {
        // Convert description and reference to SEPA compliant format
        $description = $this->convertToSEPA($description, 140); // SEPA max is 140 chars for description
        $reference = $this->convertToSEPA($reference, 35); // SEPA max is 35 chars for reference
        
        $data_arr = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'reference' => $reference,
            'callbackUrl' => $callbackUrl,
        ];
        if ( ! empty( $bulkId ) ) {
            $data_arr['bulkId'] = $bulkId;
        }
        if ( ! empty($returnUrl ) ) {
            $data_arr['returnUrl'] = $returnUrl;
        }
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments' ), $data_arr );
        if ( empty( $response->paymentId ) ) {
            throw new CreatePaymentFailedException( $response->message ?: 'failed to create payment' );
        }

        return $response;
    }

    /**
     * Get payment details of an existing payment
     *
     * @param  string $paymentId  The unique Payconiq identifier of a payment as provided by the create payment service
     *
     * @return  object  Response object by Payconiq
     */
    public function retrievePayment( $paymentId ) {
        $response = $this->makeRequest( 'GET', $this->getEndpoint( '/payments/' . $paymentId ) );

        if ( empty( $response->paymentId ) ) {
            throw new RetrievePaymentFailedException( $response->message ?: 'failed ro retrieve payment' );
        }

        return $response;
    }

    /**
     * Get payments list
     *
     * @param  string $reference	External payment reference used to reference the Payconiq payment in the calling party's system
     * 
     * @return  array  Response objects by Payconiq
     */
    public function getPaymentsListByReference( $reference ) {
        // Convert reference to SEPA compliant format voor consistentie
        $reference = $this->convertToSEPA($reference, 35);
        
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/search' ), [
            'reference' => $reference
        ]);

        if ( empty( $response->size ) ) {
            throw new GetPaymentsListFailedException( $response->message ?: 'failed to retrieve payment list or no payments retrieved' );
        }

        return $response->details;
    }

    /**
     * Get payments list
     *
     * @param  string $fromDate	The start date and time to filter the search results.
     *				Default: is the API default: Current date and time minus one day. (Now - 1 day)
     *				Format: YYYY-MM-ddTHH:mm:ss.SSSZ
     * 
     * @param  string $toDate	The end date and time to filter the search results.
     *				Default: is the API default: Current date and time. (Now)
     *				Format: YYYY-MM-ddTHH:mm:ss.SSSZ
     * 
     * @param  int $size	The page size for responses, more used internally
     *              Default: 50
     * 
     * @return  array  Response objects by Payconiq
     */
    public function getPaymentsListByDateRange( $fromDate = '', $toDate = '', $size = 50 ) {
        $param_arr = [
            "paymentStatuses" => [ "SUCCEEDED" ]
        ];
        if ( ! empty( $fromDate ) ) {
            $param_arr['from'] = $fromDate;
        }
        if ( ! empty( $toDate ) ) {
            $param_arr['to'] = $toDate;
        }
        $page = 0;
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/search?page=' . intval( $page ) . '&size=' . intval( $size ) ), $param_arr );

        if ( empty( $response->size ) ) {
            throw new GetPaymentsListFailedException( $response->message ?: 'failed to retrieve payment list or no payments retrieved' );
        }

        $details = $response->details;
        if ( !empty( $response->totalPages ) && $response->totalPages > 1 ) {
            while ($page < $response->totalPages-1) {
                $page=$response->number+1;
                $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/search?page=' . intval( $page ) . '&size=' . intval( $size ) ), $param_arr );
                $details = array_merge($details,$response->details );
            }
        }
        return $details;
    }

    /**
     * Refund an existing payment
     *
     * @param  string $paymentId  The unique Payconiq identifier of a payment as provided by the create payment service
     *
     * @param  float $amount		Payment amount in cents
     * @param  string $currency		Payment currency code in IOS 4217 format
     * @param  string $description	Optional refund description
     *
     * @return  object  Response object by Payconiq
     */
    public function refundPayment( $paymentId, $amount, $currency = 'EUR', $description = '' ) {
        // Convert description to SEPA compliant format
        $description = $this->convertToSEPA($description, 140);
        
        $data_arr = [
            'amount' => $amount,
            'currency' => $currency,
        ];
        // description is optional, so add it only when not empty
        if ( ! empty( $description ) ) {
            $data_arr['description'] = $description;
        }
        // JWS to be calculated and added as header 'Idempotency-Key' to the data_arr
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/' . $paymentId ), $data_arr );

        if ( empty( $response->paymentId ) ) {
            throw new RefundFailedException( $response->message ?: 'failed to refund payment' );
        }

        return $response;
    }

    /**
     * Get refund IBAN
     *
     * @param  string $paymentId  The unique Payconiq identifier of a payment as provided by the create payment service
     *
     * @return  object  Response object by Payconiq
     */
    public function getRefundIban( $paymentId ) {
        $response = $this->makeRequest( 'GET', $this->getEndpoint( '/payments/' . $paymentId . '/debtor/refundIban' ) );

        if ( empty( $response->iban ) ) {
            throw new GetRefundIbanFailedException( $response->message ?: 'failed to get IBAN number' );
        }

        return $response->iban;
    }

    /**
     * Get the endpoint for the call
     *
     * @param  string $route
     */
    private function getEndpoint( $route = null ) {
        return $this->endpoint . $route;
    }

    /**
     * Construct the headers for the cURL call
     * 
     * @return array
     */
    private function constructHeaders() {
        return [
            'Content-Type: application/json',
            'Cache-Control: no-cache',
            'Authorization: Bearer ' . $this->apiKey
        ];
    }

    /**
     * cURL request
     *
     * @param  string $method
     * @param  string $url
     * @param  array $headers
     * @param  array $parameters
     *
     * @return response
     */
    private function makeRequest( $method, $url, $parameters = [] ) {
        $curl = curl_init();

        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 20 );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 20 );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->constructHeaders() );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );

        if ( $method === 'POST' ) {
            curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
        }

        $response_body = curl_exec( $curl );
        $http_code      = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        $curl_error     = curl_error( $curl );

        curl_close( $curl );

        // Start with a default response structure
        $default_response = (object) [
            'message' => '',
            'size' => 0,
            'totalPages' => 0,
            'totalElements' => 0,
            'number' => 0,
        ];

        // If cURL failed entirely
        if ( $curl_error ) {
            $default_response->message = 'cURL error: ' . $curl_error;
            return $default_response;
        }

        // If HTTP error (e.g. 4xx, 5xx)
        if ( $http_code >= 400 ) {
            $default_response->message = "HTTP error: {$http_code}";
            // Optionally include response body if it contains useful info
            return $default_response;
        }

        // Decode JSON
        $decoded = json_decode( $response_body );

        // If JSON is invalid or null, return safe default
        if ( ! is_object( $decoded ) ) {
            $default_response->message = 'Invalid or empty JSON response';
            return $default_response;
        }

        // Ensure message exists
        if ( ! isset( $decoded->message ) ) {
            $decoded->message = '';
        }

        return $decoded;
    }

    /**
     * Verify Payconiq webhook signature
     *
     * @param string $payload   Raw request body (php://input)
     * @param array  $headers   All request headers (getallheaders())
     * @param string $environment 'prod' or 'ext'
     * @return bool true if valid, false otherwise
     * @throws \Exception on errors or malformed data
     */
    public function verifyWebhookSignature( $payload, $headers, $environment = self::ENVIRONMENT_PROD ) {
        $signatureHeader = $headers['JWS-Request-Signature-Payment'] ?? null;
        if (!$signatureHeader) {
            throw new \Exception("Missing JWS-Request-Signature-Payment header");
        }

        // --- 1. Split JWS parts ---
        $parts = explode('.', $signatureHeader);
        if (count($parts) !== 3) {
            throw new \Exception("Invalid JWS format (expected 3 parts)");
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        // --- 2. Decode JOSE header ---
        $headerJson = json_decode(self::base64urlDecode($encodedHeader), true);
        if (!$headerJson || !isset($headerJson['kid'])) {
            throw new \Exception("Invalid JOSE header or missing kid");
        }

        // --- 3. Fetch JWKS ---
        $jwksUrl = ($environment === self::ENVIRONMENT_PROD)
            ? 'https://jwks.bancontact.net/'
            : 'https://jwks.preprod.bancontact.net/';
        $jwks = json_decode(file_get_contents($jwksUrl), true);
        if (!isset($jwks['keys'])) {
            throw new \Exception("Invalid JWKS format from $jwksUrl");
        }

        // --- 4. Find matching key ---
        $jwk = null;
        foreach ($jwks['keys'] as $key) {
            if ($key['kid'] === $headerJson['kid']) {
                $jwk = $key;
                break;
            }
        }
        if (!$jwk) {
            throw new \Exception("No matching key found for kid={$headerJson['kid']}");
        }

        // --- 5. Convert JWK to PEM ---
        $pem = self::ecJwkToPem($jwk);

        // --- 6. Verify signature ---
        $reconstructedPayload = self::base64urlEncode($payload);
        $signingInput = $encodedHeader . '.' . $reconstructedPayload;
        $signature = self::base64urlDecode($encodedSignature);

        $signatureDer = self::ecdsaRawToDer($signature);

        $verified = openssl_verify(
            $signingInput,
            $signatureDer,
            $pem,
            OPENSSL_ALGO_SHA256
        );

        return $verified === 1;
    }

    // -----------------------
    // Helper methods
    // -----------------------

    private static function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function ecJwkToPem($jwk) {
        if (($jwk['kty'] ?? '') !== 'EC' || ($jwk['crv'] ?? '') !== 'P-256') {
            throw new \Exception("Unsupported JWK type or curve");
        }

        $x = self::base64urlDecode($jwk['x']);
        $y = self::base64urlDecode($jwk['y']);
        $pubKey = "\x04" . $x . $y;

        $oidEcPublicKey = "\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01";
        $oidPrime256v1  = "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07";

        $algoid = "\x30" . chr(strlen($oidEcPublicKey . $oidPrime256v1) + 4)
            . "\x06" . chr(strlen($oidEcPublicKey)) . $oidEcPublicKey
            . "\x06" . chr(strlen($oidPrime256v1)) . $oidPrime256v1;

        $pubKeyBitString = "\x03" . chr(strlen($pubKey) + 1) . "\x00" . $pubKey;

        $seq = "\x30" . chr(strlen($algoid . $pubKeyBitString)) . $algoid . $pubKeyBitString;

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($seq), 64, "\n")
            . "-----END PUBLIC KEY-----\n";

        return $pem;
    }

    private static function ecdsaRawToDer($signature) {
        $length = strlen($signature);
        if ($length % 2 !== 0) {
            throw new \Exception("Invalid ECDSA signature length");
        }
        $r = substr($signature, 0, $length / 2);
        $s = substr($signature, $length / 2);

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        if (ord($r[0]) > 0x7F) $r = "\x00" . $r;
        if (ord($s[0]) > 0x7F) $s = "\x00" . $s;

        $der = "\x30"
            . chr(strlen($r) + strlen($s) + 4)
            . "\x02" . chr(strlen($r)) . $r
            . "\x02" . chr(strlen($s)) . $s;

        return $der;
    }
}