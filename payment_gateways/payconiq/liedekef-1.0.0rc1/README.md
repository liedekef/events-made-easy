<img src="https://s3-eu-west-1.amazonaws.com/eventsquare.assets/ext/payconiq_logo.png" alt="Payconiq" width="300"/>

# Payconiq API client for PHP #

Accepting [Payconiq](https://www.payconiq.com/) payments with the use of the QR code.

## Requirements ##
To use the Payconiq API client, the following things are required:

+ Payconiq Merchant Id and API key
+ PHP >= 5.6
+ PHP cURL extension

## Installation ##

The best way to install the Payconiq API client is to require it with [Composer](http://getcomposer.org/doc/00-intro.md).

    $ composer require eventsquare/payconiq

You may also git checkout or [download all the files](https://github.com/EventSquare/payconiq-api-php/archive/master.zip), and include the Payconiq API client manually.


## Parameters ##

We use the following parameters in the examples below:

```php
$apiKey = 'apiKey 123456'; // Used to secure request between merchant backend and Payconiq backend.
$merchantId = 'merchantid'; // payconiq merchantid (not really used, unless to verify more in notification callback)
$amount = 1000; // Transaction amount in cents
$currency = 'EUR'; // Currency
$reference = "my internal payment reference"; // an internal reference (e.g. a booking id)
      // the reference is given in the callback, allowing you to know what local payment is being handled
$callbackUrl = 'http://yoursite.com/postback'; // Callback where Payconiq needs to POST confirmation status
$returnUrl = 'http://yoursite.com/returnpage'; // Optional. the page a buyer is returned to after payment. You'll need to check
     // the payment status there
```

To learn more about how, when and what Payconiq  will POST to your callbackUrl, please refer to the developer documentation [right here](https://dev.payconiq.com/online-payments-dock).

## Usage ##


### Create a payment ###


```php
use Payconiq\Client;

$payconiq = new Client($apiKey);
	
// Create a new payment
$payment = $payconiq->createPayment($amount, $currency, $reference, $callbackUrl, $returnUrl);

// Get payment id
// you may want to store this paymentId internally, to be able to do verify on callback
$paymentId = $payconiq_payment->paymentId;

// Assemble QR code content
$qrcode = $payment->_links->qrcode->href;

// Or get the href at payconiq and redirect to there, avoiding to need to generate qrcode yourself
$url = $payment->_links->checkout->href;
header("Location: $url");exit;
```

### Create a payment in test ###


```php
use Payconiq\Client;

$payconiq = new Client($apiKey);
$payconiq->setEndpointTest();
	
// Create a new payment
$payment = $payconiq->createPayment($amount, $currency, $reference, $callbackUrl, $returnUrl);

// Get payment id
// you may want to store this paymentId internally, to be able to do verify on callback
$paymentId = $payconiq_payment->paymentId;

// Assemble QR code content
$qrcode = $payment->_links->qrcode->href;

// Or get the href at payconiq and redirect to there, avoiding to need to generate qrcode yourself
$url = $payment->_links->checkout->href;
// fix a payconiq api bug where the href-links in sandbox point to prod too
$url = str_replace("https://payconiq.com","https://ext.payconiq.com",$url);
header("Location: $url");exit;
```

### Retrieve a payment ###

```php
use Payconiq\Client;

$payconiq = new Client($apiKey);

// Retrieve a payment
$payment = $payconiq->retrievePayment($paymentId);

// use try-catch:
   try {
           $payment = $payconiq->retrievePayment($paymentId);
   } catch (Exception $e) {
           error_log("ayconiq error getting payment id $paymentId");
           return;
   }

```

### Handle notification callback ###


```php
use Payconiq\Client;

$payconiq = new Client($apiKey);
$payload = @file_get_contents('php://input');
$data = json_decode($payload);
$paymentid = $data->paymentId;
$payment = $payconiq->retrievePayment($paymentid);

// verify merchantid
$payment_merchantid = $payconiq_payment->creditor->merchantId;
if ($payment_merchantid != $merchantId) {
           error_log("Payconiq wrong merchant id $payment_merchantid");
           return;
}
// get reference
$reference = $payment->reference;
// based on the reference, check the received payment id with the one you stored locally (if you did that)

// verify status and price
if ($payment->status == "SUCCEEDED" && $payment->totalAmount == $amount ) {
    // the status is ok and all is paid, update internal info based on the found reference
}

```

