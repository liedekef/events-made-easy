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
    const ENVIRONMENT_TEST = 'test';
    const JWKS_CACHE_TTL = 3600*12; // needs to be at least 3600 

    // Allowed characters according to EPC217-08 SEPA Conversion Table
    const ALLOWED_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,:?+-/()'";

    protected $apiKey;
    protected $endpoint;
    protected $jwksUrl;
    protected $environment;
    private $cacheDir;

    /**
     * Construct
     *
     * @param  string $apiKey		Used to secure request between merchant backend and Payconiq backend.
     * @param  string $environment	Environment to use when making API calls
     * 
     * @return void
     */
    public function __construct( $apiKey = null, $environment = self::ENVIRONMENT_PROD ) {
        if (!empty($apiKey)) {
            $this->setApiKey($apiKey);
        }

        // Normalize environment string
        $environment = strtolower(trim($environment));

        $this->configureForEnvironment($environment);
    }

    /**
     * Set endpoints based on environment
     * 
     * @param string $environment The environment string
     */
    private function configureForEnvironment($environment) {
        // Store the environment
        $this->environment = $environment;

        // Only 'prod' uses production endpoints, everything else uses test
        if ( $environment === self::ENVIRONMENT_PROD ) {
            $this->endpoint = 'https://merchant.api.bancontact.net/v3';
            $this->jwksUrl = 'https://jwks.bancontact.net/';
        } else {
            $this->endpoint = 'https://merchant.api.preprod.bancontact.net/v3';
            $this->jwksUrl = 'https://jwks.preprod.bancontact.net/';
        }
    }

    /**
     * Set optional own endpoints
     *
     * @param  string $url  The endpoint of the Payconiq API.
     * @param  string $jwksUrl  The jwks endpoint of the Payconiq API.
     *
     * @return self
     */
    public function setEndpoints($url = null, $jwksUrl = null) {
        $this->endpoint = $url ?: $this->endpoint;
        $this->jwksUrl = $jwksUrl ?: $this->jwksUrl;
        return $this;
    }

    /**
     * Set the environment to test env
     *
     * @return self
     */
    public function setEndpointTest() {
        $this->configureForEnvironment(self::ENVIRONMENT_TEST);
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
        if (!empty($apiKey)) {
            $this->apiKey = $apiKey;
        }

        return $this;
    }

    public function setCacheDir($dir) {
        $this->cacheDir = rtrim($dir, '/');
        return $this;
    }

    /**
     * Get current environment
     *
     * @return string
     */
    public function getEnvironment() {
        return $this->environment;
    }

    /**
     * Create a new payment
     * 
     * @param  float $amount		Payment amount in cents
     * @param  string $currency		Payment currency code in ISO 4217 format
     * @param  string $description	Payment description shown during payment (optional)
     * @param  string $reference	External payment reference used to reference the Payconiq payment in the calling party's system (optional)
     * @param  string $bulkId	    BulkId for bulk payouts (optional)
     * @param  string $callbackUrl  A url to which the merchant or partner will be notified of a payment (optiona)
     * @param  string $returnUrl    Return url to return client after paying on payconiq site itself (optional)
     * 
     * @return object  payment object
     * @throws CreatePaymentFailedException  If the response has no transactionid
     */
    public function createPayment( $amount, $currency = 'EUR', $description='', $reference='', $bulkId='', $callbackUrl='', $returnUrl = null ) {
        // Convert description and reference to SEPA compliant format
        $description = self::convertToSEPA($description, 140); // SEPA max is 140 chars for description
        $reference = self::convertToSEPA($reference, 35); // SEPA max is 35 chars for reference
        
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
            throw new RetrievePaymentFailedException( $response->message ?: 'failed to retrieve payment' );
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
        $reference = self::convertToSEPA($reference, 35);
        
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
     * @param  string $paymentId      The unique Payconiq identifier of a payment
     * @param  float  $amount         Payment amount in cents
     * @param  string $currency       Payment currency code in ISO 4217 format
     * @param  string $description    Optional refund description
     * @param  string $idempotencyKey Optional idempotency key (UUIDv4 recommended)
     * @param  string $refundurl      Optional refund url, if not get it from the payment
     *
     * @return object  Response object by Payconiq
     * @throws RefundFailedException
     */
    public function refundPayment($paymentId, $amount, $currency = 'EUR', $description = '', $idempotencyKey = null, $refundUrl = null) {
        // Convert description to SEPA compliant format
        $description = self::convertToSEPA($description, 140);

        $data_arr = [
            'amount' => $amount,
            'currency' => $currency,
        ];
        if (!empty($description)) {
            $data_arr['description'] = $description;
        }

        // Ensure idempotency key is provided
        if (empty($idempotencyKey)) {
            $idempotencyKey = $this->generateUuidV4();
        }

        $extraHeaders = [
            'Idempotency-Key: ' . $idempotencyKey
        ];

        if (empty($refundUrl)) {
            $payment = $this->retrievePayment($paymentId);
            if (empty($payment->_links->refund->href)) {
                throw new \LogicException("Refund not allowed for payment {$paymentId}");
            }
            $refundUrl = $payment->_links->refund->href;
        }

        $response = $this->makeRequest(
            'POST',
            $refundUrl,
            $data_arr,
            $extraHeaders
        );

        if (empty($response->paymentId)) {
            throw new RefundFailedException($response->message ?: 'failed to refund payment');
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
     * @param  array $extraHeaders  Optional extra headers to add to the request headers
     * 
     * @return array
     */
    private function constructHeaders($extraHeaders = []) {
        $headers = [
            'Content-Type: application/json',
            'Cache-Control: no-cache',
            'Authorization: Bearer ' . $this->apiKey
        ];
        return array_merge($headers, $extraHeaders);
    }

    /**
     * Generate a random UUID v4 (RFC 4122)
     *
     * @return string
     */
    private function generateUuidV4() {
        $bytes = random_bytes(16);
        // Set version to 0100 (4)
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        // Set variant to 10xx (RFC 4122)
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * cURL request
     *
     * @param  string $method
     * @param  string $url
     * @param  array $parameters
     * @param  array $extraHeaders
     *
     * @return response
     */
    private function makeRequest( $method, $url, $parameters = [], $extraHeaders = [] ) {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('API key is not set. Call setApiKey() first.');
        }
        $curl = curl_init();

        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 20 );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 20 );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->constructHeaders($extraHeaders) );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );

        if ( $method === 'POST' ) {
            curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
        }

        $response_body = curl_exec( $curl );
        $http_code     = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        $curl_error    = curl_error( $curl );

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
     * @return bool true if valid, false otherwise
     * @throws \Exception on errors or malformed data
     */
    public function verifyWebhookSignature($payload, $headers) {
        $signatureHeader = $headers['Signature'] ?? null;
        if (!$signatureHeader) {
            throw new \Exception("Missing Signature header");
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

        $kid = $headerJson['kid'];

        // --- 3. Try verification with cached JWKS ---
        try {
            return $this->attemptVerification($encodedHeader, $payload, $encodedSignature, $kid, false);
        } catch (\Exception $e) {
            // First attempt failed, try with forced refresh
            try {
                return $this->attemptVerification($encodedHeader, $payload, $encodedSignature, $kid, true);
            } catch (\Exception $e2) {
                // Both attempts failed
                throw new \Exception("Signature verification failed after refresh: " . $e2->getMessage());
            }
        }
    }

    /**
     * Attempt to verify the signature with optional forced refresh
     */
    private function attemptVerification($encodedHeader, $payload, $encodedSignature, $kid, $forceRefresh = false) {
        // Fetch JWKS keys (with optional forced refresh)
        $keys = $this->getJWKS($forceRefresh);

        // Find matching key
        $jwk = self::findKeyByKid($keys, $kid);
        if (!$jwk) {
            throw new \Exception("No matching key found for kid={$kid}");
        }

        $kty = $jwk['kty'] ?? 'unknown';
        $alg = $jwk['alg'] ?? 'unknown';

        // Convert JWK to PEM based on key type
        if ($kty === 'RSA') {
            $pem = self::rsaJwkToPem($jwk);
        } elseif ($kty === 'EC') {
            $pem = self::ecJwkToPem($jwk);
        } else {
            throw new \Exception("Unsupported key type: {$kty}");
        }

        // Verify the PEM is valid
        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) {
            throw new \Exception("Invalid PEM format generated from JWK");
        }

        // Verify signature
        $reconstructedPayload = self::base64urlEncode($payload);
        $signingInput = $encodedHeader . '.' . $reconstructedPayload;
        $signature = self::base64urlDecode($encodedSignature);

        // Choose the right verification method
        if ($kty === 'EC' && ($alg === 'ES256' || $alg === 'unknown')) {
            // EC signature needs DER encoding
            $signatureDer = self::ecdsaRawToDer($signature);
            $verified = openssl_verify($signingInput, $signatureDer, $pem, OPENSSL_ALGO_SHA256);
        } elseif ($kty === 'RSA' && ($alg === 'RS256' || $alg === 'unknown')) {
            // RSA signature is already in the right format
            $verified = openssl_verify($signingInput, $signature, $pem, OPENSSL_ALGO_SHA256);
        } else {
            throw new \Exception("Unsupported algorithm: {$alg} for key type: {$kty}");
        }

        if ($verified !== 1) {
            throw new \Exception("Signature verification failed");
        }

        return true;
    }

    private function getJWKS($forceRefresh = false) {
        $cacheDir = $this->cacheDir ?: sys_get_temp_dir();
        $cacheFile = $cacheDir . "/payconiq_jwks_{$this->environment}.json";

        // Anti-abuse: don't allow forced refresh more than once per hour
        if ($cacheDir && $forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
            $forceRefresh = false;
        }

        // Use cache if valid and not forcing refresh
        if ($cacheDir && !$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::JWKS_CACHE_TTL) {
            $jwksContent = @file_get_contents($cacheFile);
            if ($jwksContent !== false) {
                $jwks = json_decode($jwksContent, true);
                if (isset($jwks['keys'])) {
                    return $jwks['keys'];
                }
                // If cache is corrupt, fall through to fetch
            }
        }

        // Attempt to fetch fresh JWKS
        $jwksContent = null;
        try {
            $jwksContent = self::fetchUrl($this->jwksUrl);
            $jwks = json_decode($jwksContent, true);
            if (!isset($jwks['keys'])) {
                throw new \Exception("Invalid JWKS format");
            }
            // Save successful response
            if ($cacheDir)
                @file_put_contents($cacheFile, $jwksContent, LOCK_EX);
            return $jwks['keys'];
        } catch (\Exception $e) {
            // Log the fetch failure
            error_log("Payconiq JWKS fetch failed: " . $e->getMessage());

            // Fallback: if any cache exists (even stale), try to use it
            if ($cacheDir && file_exists($cacheFile)) {
                $fallbackContent = @file_get_contents($cacheFile);
                if ($fallbackContent !== false) {
                    $fallbackJwks = json_decode($fallbackContent, true);
                    if (isset($fallbackJwks['keys'])) {
                        // Log that we're using stale keys
                        error_log("Using stale Payconiq JWKS due to fetch failure");
                        return $fallbackJwks['keys'];
                    }
                }
            }

            // No cache, no fetch => rethrow
            throw new \Exception("JWKS unavailable: " . $e->getMessage());
        }
    }

    // -----------------------
    // Helper methods
    // -----------------------

    /**
     * Convert string to EPC217-08 SEPA compliant format
     *
     * @param  string $input  Original string
     * @param  int    $maxLength  Maximum length (optional)
     *
     * @return string  Converted string
     */
    private static function convertToSEPA($input, $maxLength = null) {
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

    private static function findKeyByKid(array $keys, string $kid): ?array {
        foreach ($keys as $key) {
            if (!isset($key['kid'])) {
                continue;
            }

            if (hash_equals($key['kid'], $kid)) {
                return $key;
            }
        }
        return null;
    }

    private static function fetchUrl($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 5,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_USERAGENT => 'Payconiq-PHP-Client/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Failed to fetch URL: {$error}");
        }

        curl_close($ch);

        // Check for HTTP errors (4xx, 5xx)
        if ($httpCode >= 400) {
            throw new \Exception("HTTP error {$httpCode} when fetching URL: {$url}");
        }

        return $response;
    }

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

        // Verify lengths
        if (strlen($x) !== 32 || strlen($y) !== 32) {
            throw new \Exception("Invalid EC coordinate length");
        }

        $pubKey = "\x04" . $x . $y; // Uncompressed point format

        // OID for ecPublicKey: 1.2.840.10045.2.1
        $oidEcPublicKey = "\x2A\x86\x48\xCE\x3D\x02\x01";

        // OID for prime256v1 (P-256): 1.2.840.10045.3.1.7
        $oidPrime256v1 = "\x2A\x86\x48\xCE\x3D\x03\x01\x07";

        // Build algorithm identifier SEQUENCE
        $algoid = "\x30\x13" // SEQUENCE, length 19
            . "\x06\x07" . $oidEcPublicKey  // OID ecPublicKey
            . "\x06\x08" . $oidPrime256v1;   // OID prime256v1

        // Public key as BIT STRING
        $pubKeyBitString = "\x03\x42\x00" . $pubKey; // BIT STRING, length 66, no unused bits

        // Complete SEQUENCE
        $seq = "\x30\x59" . $algoid . $pubKeyBitString; // SEQUENCE, length 89

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

    private static function rsaJwkToPem($jwk) {
        if (!isset($jwk['n']) || !isset($jwk['e'])) {
            throw new \Exception("Invalid RSA JWK: missing n or e parameters");
        }

        // Decode base64url encoded parameters
        $n = self::base64urlDecode($jwk['n']);
        $e = self::base64urlDecode($jwk['e']);

        // Ensure the modulus has a leading zero if the first byte is > 0x7F
        if (ord($n[0]) > 0x7F) {
            $n = "\x00" . $n;
        }

        // Build DER sequence
        $modulus = "\x02" . self::encodeLength(strlen($n)) . $n;
        $exponent = "\x02" . self::encodeLength(strlen($e)) . $e;

        // Combine modulus and exponent
        $sequence = "\x30" . self::encodeLength(strlen($modulus) + strlen($exponent)) . $modulus . $exponent;

        // Bit string wrapper
        $bitString = "\x03" . self::encodeLength(strlen($sequence) + 1) . "\x00" . $sequence;

        // RSA algorithm identifier (OID 1.2.840.113549.1.1.1 with NULL parameters)
        $algorithmIdentifier = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        // Public key info
        $publicKeyInfo = "\x30" . self::encodeLength(strlen($algorithmIdentifier) + strlen($bitString)) . $algorithmIdentifier . $bitString;

        // Create PEM
        $pem = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($publicKeyInfo), 64, "\n") .
            "-----END PUBLIC KEY-----\n";

        // Verify the PEM is valid
        if (openssl_pkey_get_public($pem) === false) {
            throw new \Exception("Generated RSA PEM is invalid: " . openssl_error_string());
        }

        return $pem;
    }

    private static function encodeLength($length) {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }
}
