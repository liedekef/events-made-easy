<?php

namespace Cloudipsp;

use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    private $mid = 1396424;
    private $secret_key = 'test';
    private $TestSubscriptionData = [
        'currency' => 'USD',
        'amount' => 10000,
        'recurring_data' => [
            'start_time' => '2021-12-24',
            'amount' => 1000,
            'every' => 30,
            'period' => 'day',
            'state' => 'y',
            'readonly' => 'y'
        ]
    ];

    private function setTestConfig()
    {
        Configuration::setMerchantId($this->mid);
        Configuration::setSecretKey($this->secret_key);
        Configuration::setRequestType('json');
        Configuration::setApiVersion('2.0');
    }

    /**
     * @throws Exception\ApiException
     */
    public function testSubscriptionToken()
    {
        $this->setTestConfig();
        $result = Subscription::token($this->TestSubscriptionData)->getData();
        $this->assertNotEmpty($result['token'], 'payment_id is empty');
    }

    /**
     * @throws Exception\ApiException
     */
    public function testSubscriptionUrl()
    {
        $this->setTestConfig();
        $result = Subscription::url($this->TestSubscriptionData)->getData();
        $this->validate($result);

    }

    /**
     * @param $result
     */
    private function validate($result)
    {
        $this->assertNotEmpty($result['checkout_url'], 'checkout_url is empty');
        $this->assertNotEmpty($result['payment_id'], 'payment_id is empty');
    }
}
