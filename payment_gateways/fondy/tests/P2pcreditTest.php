<?php

namespace Cloudipsp;

use PHPUnit\Framework\TestCase;

class P2pcreditTest extends TestCase
{
    private $mid = 1000;
    private $CreditKey = 'testcredit';
    private $request_types = ['json', 'xml', 'form'];
    private $TestData = [
        'currency' => 'USD',
        'amount' => 111,
        'receiver_card_number' => '4444555511116666'
    ];

    private function setTestConfig()
    {
        Configuration::setMerchantId($this->mid);
        Configuration::setSecretKey('');
        Configuration::setCreditKey($this->CreditKey);
        Configuration::setApiVersion('1.0');
    }

    /**
     * @throws Exception\ApiException
     */
    public function testCredit()
    {
        $this->setTestConfig();
        foreach ($this->request_types as $type) {
            Configuration::setRequestType($type);
            $result = P2pcredit::start($this->TestData);
            $this->validateResult($result->getData());
            $this->isValid($result->isValid());
        }
    }

    private function validateResult($result)
    {
        $this->assertNotEmpty($result['order_id'], 'order_id is empty');
        $this->assertNotEmpty($result['payment_id'], 'payment_id is empty');
        $this->assertEquals($result['response_status'], 'success');
    }

    private function isValid($result)
    {
        $this->assertEquals($result, true);
    }
}
