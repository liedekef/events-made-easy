<?php
/**
 * Author: DM
 */
error_reporting(-1);
ini_set('display_errors', 'On');

require 'vendor/autoload.php';
\Cloudipsp\Configuration::setMerchantId(1396424);
\Cloudipsp\Configuration::setSecretKey('test');
\Cloudipsp\Configuration::setRequestType('json');//setting request type client
\Cloudipsp\Configuration::setHttpClient('HttpCurl');//setting another client
\Cloudipsp\Configuration::setApiUrl('api.fondy.eu'); //api base url
\Cloudipsp\Configuration::setApiVersion('2.0'); //api base url

//start simple test
$dataC = [
    'order_id' => time(),
    'currency' => 'USD',
    'amount' => 111,
    'response_url' => 'http://localhost/result.php'// response page
];

$data = \Cloudipsp\Checkout::url($dataC);
$data->toCheckout();
//end
