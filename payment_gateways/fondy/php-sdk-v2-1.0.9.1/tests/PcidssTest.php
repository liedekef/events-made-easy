<?php

namespace Cloudipsp;

use PHPUnit\Framework\TestCase;

class PcidssTest extends TestCase
{
    private $mid = 1396424;
    private $secret_key = 'test';
    private $request_types = ['json', 'xml', 'form'];
    private $TestCard3ds = [
        'card_number' => '4444555566661111',
        'cvv2' => '444',
        'expiry_date' => '1221'
    ];
    private $TestCardnon3ds = [
        'card_number' => '4444555511116666',
        'cvv2' => '333',
        'expiry_date' => '1222'
    ];
    private $TestPcidssData = [
        'currency' => 'USD',
        'amount' => 1000,
        'client_ip' => '127.2.2.1'
    ];

    private function setTestConfig()
    {
        Configuration::setMerchantId($this->mid);
        Configuration::setSecretKey($this->secret_key);
        Configuration::setApiVersion('1.0');
    }

    /**
     * @throws Exception\ApiException
     */
    public function testStartNon3ds()
    {
        $this->setTestConfig();
        $data = array_merge($this->TestPcidssData, $this->TestCardnon3ds);
        foreach ($this->request_types as $type) {
            Configuration::setRequestType($type);
            $result = Pcidss::start($data)->getData();

            $this->validateNon3dResult($result);
        }
    }

    /**
     * @throws Exception\ApiException
     */
    public function testgetFrom()
    {
        $data = [
            'acs_url' => 'http://some-url.com',
            'pareq' => 'pareq',
            'md' => 'pareq',
            'TermUrl' => 'http://some-url.com'
        ];
        $form = Pcidss::get3dsFrom($data, 'some_url');
        $this->assertTrue(is_string($form), "Got a " . gettype($form) . " instead of a string");
    }

    /**
     * @throws Exception\ApiException
     */
    public function testStart3ds()
    {
        $this->setTestConfig();
        $data = array_merge($this->TestPcidssData, $this->TestCard3ds);
        foreach ($this->request_types as $type) {
            Configuration::setRequestType($type);
            $result = Pcidss::start($data)->getData();
            $this->validate3dResult($result);
        }
    }

    private function validate3dResult($result)
    {
        $this->assertNotEmpty($result['acs_url'], 'asc_url is empty');
        $this->assertEquals($result['response_status'], 'success');
    }

    private function validateNon3dResult($result)
    {
        $this->assertNotEmpty($result['order_id'], 'order_id is empty');
        $this->assertNotEmpty($result['order_status'], 'order_status is empty');
        $this->assertEquals($result['response_status'], 'success');
    }
}
