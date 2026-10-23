<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require 'vendor/autoload.php';
\Cloudipsp\Configuration::setMerchantId(13964224);
\Cloudipsp\Configuration::setSecretKey('test');
\Cloudipsp\Configuration::setApiVersion('1.0');
\Cloudipsp\Configuration::setRequestType('form');

$result = new \Cloudipsp\Result\Result([], '', '', true);
var_dump($result->getData());
var_dump($result->isValid());
var_dump($result->isApproved());
