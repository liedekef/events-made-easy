<?php

/*
 * Run with `php checkout.php`.
 *
 * Required environment variables:
 * - SUMUP_API_KEY
 * - SUMUP_MERCHANT_CODE
 */

require __DIR__ . '/../vendor/autoload.php';

$apiKey = getenv('SUMUP_API_KEY');
$merchantCode = getenv('SUMUP_MERCHANT_CODE');

if (!$apiKey) {
    fwrite(STDERR, "Missing SUMUP_API_KEY environment variable\n");
    exit(1);
}

if (!$merchantCode) {
    fwrite(STDERR, "Missing SUMUP_MERCHANT_CODE environment variable\n");
    exit(1);
}

$sumup = new \SumUp\SumUp($apiKey);

try {
    $checkout = $sumup->checkouts()->create(new \SumUp\Types\CheckoutCreateRequest(
        checkoutReference: 'TX-' . time(),
        amount: 12.30,
        currency: 'EUR',
        merchantCode: $merchantCode,
    ));

    if ($checkout->id === null) {
        fwrite(STDERR, "Checkout was created, but response did not include an ID.\n");
        exit(1);
    }

    echo "[INFO] checkout created: id={$checkout->id}\n";

    $result = $sumup->checkouts()->process($checkout->id, [
        'payment_type' => 'card',
        'card' => [
            'name' => 'Boaty McBoatface',
            'number' => '4200000000000042',
            'expiry_month' => '12',
            'expiry_year' => '2030',
            'cvv' => '123',
        ],
    ]);

    if ($result instanceof \SumUp\Types\CheckoutSuccess) {
        echo "[INFO] checkout success: id={$result->id}, transaction_id={$result->transactionId}\n";
    } elseif ($result instanceof \SumUp\Types\CheckoutAccepted) {
        $redirectUrl = is_array($result->nextStep) && isset($result->nextStep['redirect_url'])
            ? (string) $result->nextStep['redirect_url']
            : '';
        echo "[INFO] checkout accepted, redirect_url={$redirectUrl}\n";
    } else {
        echo "[INFO] checkout processed\n";
    }
} catch (\SumUp\Exception\ApiException $e) {
    fwrite(STDERR, "API error ({$e->getStatusCode()}): {$e->getMessage()}\n");
    fwrite(STDERR, json_encode($e->getResponseBody(), JSON_PRETTY_PRINT) . "\n");
    exit(1);
} catch (\SumUp\Exception\UnexpectedApiException $e) {
    fwrite(STDERR, "Unexpected API error ({$e->getStatusCode()}): {$e->getMessage()}\n");
    fwrite(STDERR, json_encode($e->getErrorEnvelope()->toArray(), JSON_PRETTY_PRINT) . "\n");
    exit(1);
} catch (\SumUp\Exception\SDKException $e) {
    fwrite(STDERR, "SDK error ({$e->getStatusCode()}): {$e->getMessage()}\n");
    exit(1);
}
