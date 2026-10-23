<?php

/*
 * Example demonstrating how to use the SDK-provided Guzzle HTTP client.
 *
 * Run with `php guzzle-http-client.php`.
 * Requires `composer require guzzlehttp/guzzle`.
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

$baseUrl = 'https://api.sumup.com';
$customHeaders = ['User-Agent' => 'SumUp-PHP-SDK-Guzzle'];
$caBundlePath = realpath(__DIR__ . '/../resources/ca-bundle.crt');

$guzzleClient = new \SumUp\HttpClient\GuzzleClient($baseUrl, $customHeaders, $caBundlePath);

$sumup = new \SumUp\SumUp([
    'api_key' => $apiKey,
    'client' => $guzzleClient,
]);

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
