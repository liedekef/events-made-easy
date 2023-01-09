<?php

namespace Cloudipsp;

use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    private $mid = 1396424;
    private $secret_key = 'test';
    private $request_types = ['json', 'xml', 'form'];
    private $fullTestData = [
        'order_desc' => 'tests SDK',
        'currency' => 'USD',
        'amount' => 21321312,
        'default_payment_system' => 'card',
        'response_url' => 'http://site.com/responseurl',
        'server_callback_url' => 'http://site.com/callbackurl',
        'payment_systems' => 'qiwi,yandex,webmoney,card,p24',
        'preauth' => 'N',
        'sender_email' => 'tests@fondy.eu',
        'delayed' => 'Y',
        'lang' => 'ru',
        'product_id' => 'some_product_id',
        'required_rectoken' => 'N',
        'lifetime' => 36000,
        'verification' => 'N',
        'subscription' => 'N',
        'merchant_data' => array(
            'custom_field1' => 1111,
            'custom_field2' => '2222',
            'custom_field3' => '3!@#$%^&(()_+?"}',
            'custom_field4' => ['custom_field4_test', 'custom_field4_test2', 'custom_field4_test3' => ['custom_field4_test3_33' => 'hello world!']]
        )
    ];

    /**
     * Setup config
     */
    private function setTestConfig()
    {
        Configuration::setMerchantId($this->mid);
        Configuration::setSecretKey($this->secret_key);
        Configuration::setApiVersion('1.0');
    }

    /**
     * @throws Exception\ApiException
     */
    public function testUrl()
    {
        $this->setTestConfig();
        foreach ($this->request_types as $type) {
            Configuration::setRequestType($type);
            $result = Checkout::url($this->fullTestData)->getData();
            $this->validateCheckoutUrlResult($result);
        }
    }

    /**
     * @throws Exception\ApiException
     */
    public function testToken()
    {
        $this->setTestConfig();
        Configuration::setRequestType('json');
        $result = Checkout::token($this->fullTestData)->getData();
        $this->validateTokenResult($result);
    }

    /**
     * @throws Exception\ApiException
     */
    public function testForm()
    {
        $this->setTestConfig();
        $result = Checkout::form($this->fullTestData);
        $this->assertIsMyString($result, "Got a " . gettype($result) . " instead of a string");
    }

    /**
     * Checking correct result for token request
     * @param $result
     */
    private function validateTokenResult($result)
    {
        $this->assertNotEmpty($result['token'], 'payment_id is empty');
        $this->assertIsMyString($result['token'], "Got a " . gettype($result['token']) . " instead of a string");
    }

    /**
     * @param $string
     * @param $message
     */
    private function assertIsMyString($string, $message)
    {
        if (method_exists(get_parent_class($this), 'assertIsString')) {
            $this->assertIsString($string, $message);
        } else {
            $this->assertInternalType('string', $string, $message);
        }
    }

    /**
     * Checking correct result of get checkout url
     * @param $result
     */
    private function validateCheckoutUrlResult($result)
    {
        $this->assertNotEmpty($result['checkout_url'], 'checkout_url is empty');
        $this->assertNotEmpty($result['payment_id'], 'payment_id is empty');
        $this->assertEquals($result['response_status'], 'success');
    }
}
