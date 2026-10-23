<?php

/*
 * Example demonstrating how to use a custom HTTP client with the SumUp SDK.
 *
 * Run with `php custom-http-client.php`. Don't forget to set the SUMUP_API_KEY
 * environment variable before running the script.
 */

require __DIR__ . '/../vendor/autoload.php';

$apiKey = getenv('SUMUP_API_KEY');

if (!$apiKey) {
    fwrite(STDERR, "Missing SUMUP_API_KEY environment variable\n");
    exit(1);
}

$merchantCode = getenv('SUMUP_MERCHANT_CODE');

if (!$merchantCode) {
    fwrite(STDERR, "Missing SUMUP_MERCHANT_CODE environment variable\n");
    exit(1);
}

/**
 * Example custom HTTP client implementation.
 *
 * This demonstrates how to create your own HTTP client by implementing
 * the HttpClientInterface. You could use this to:
 * - Add custom logging
 * - Implement retry logic
 * - Use a different HTTP library
 * - Add custom middleware
 */
class CustomLoggingHttpClient implements \SumUp\HttpClient\HttpClientInterface
{
    private \SumUp\HttpClient\HttpClientInterface $wrappedClient;

    public function __construct(\SumUp\HttpClient\HttpClientInterface $wrappedClient)
    {
        $this->wrappedClient = $wrappedClient;
    }

    public function send(
        string $method,
        string $url,
        array $body,
        array $headers,
        ?\SumUp\HttpClient\RequestOptions $options = null
    ): \SumUp\HttpClient\Response {
        // Log the request
        echo "[HTTP Request] {$method} {$url}\n";
        if (!empty($body)) {
            echo "[HTTP Body] " . json_encode($body) . "\n";
        }

        // Forward to the wrapped client
        $response = $this->wrappedClient->send($method, $url, $body, $headers, $options);

        // Log the response
        echo "[HTTP Response] Status: " . $response->getHttpResponseCode() . "\n";

        return $response;
    }
}

// First, create a base HTTP client (cURL by default)
$baseUrl = 'https://api.sumup.com';
$customHeaders = ['User-Agent' => 'SumUp-PHP-SDK-Custom'];
$caBundlePath = null;

// Create base cURL client
$baseClient = new \SumUp\HttpClient\CurlClient(
    $baseUrl,
    $customHeaders,
    $caBundlePath
);

// Wrap it with our custom logging client
$customClient = new CustomLoggingHttpClient($baseClient);

// Pass the custom client to the SumUp SDK
$sumup = new \SumUp\SumUp([
    'api_key' => $apiKey,
    'client' => $customClient,
]);

// Use the SDK normally - all requests will be logged
try {
    $merchant = $sumup->merchants()->get($merchantCode);
    echo "\nMerchant retrieved successfully!\n";
    echo "Merchant code: " . $merchant->merchantCode . "\n";
} catch (\SumUp\Exception\ApiException $e) {
    echo "API error: " . $e->getMessage() . "\n";
} catch (\SumUp\Exception\UnexpectedApiException $e) {
    echo "Unexpected API error: " . $e->getMessage() . "\n";
    echo "Envelope: " . json_encode($e->getErrorEnvelope()->toArray()) . "\n";
} catch (\SumUp\Exception\SDKException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
