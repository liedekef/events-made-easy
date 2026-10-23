<?php
/**
 * Created by PhpStorm.
 * User: dm
 * Date: 21.05.18
 * Time: 12:03
 */

namespace Cloudipsp;

use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private $mid = 1396424;
    private $secret_key = 'test';
    private $TestCardnon3ds = [
        'card_number' => '4444555511116666',
        'cvv2' => '333',
        'expiry_date' => '1222'
    ];
    private $TestPcidssData = [
        'currency' => 'USD',
        'preauth' => 'Y',
        'amount' => 1000,
        'client_ip' => '127.2.2.1'
    ];
    private $orderID = null;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->setTestConfig();
        $TestData = array_merge($this->TestPcidssData, $this->TestCardnon3ds);
        $this->orderID['order_id'] = $this->createOrder($TestData);
        parent::__construct($name, $data, $dataName);
    }

    private function setTestConfig()
    {
        Configuration::setMerchantId($this->mid);
        Configuration::setSecretKey($this->secret_key);
        Configuration::setApiVersion('1.0');
    }

    /**
     * @throws Exception\ApiException
     */
    public function testStatus()
    {
        $this->setTestConfig();
        $data = Order::status($this->orderID);
        $result = $data->getData();
        $this->assertNotEmpty($result['order_id'], 'order_id is empty');
        $this->assertNotEmpty($result['order_status'], 'order_status is empty');
        $this->assertEquals($result['response_status'], 'success');
        $this->assertEquals( true, $data->isApproved());
        $this->assertEquals( true, $data->isValid());

    }

    /**
     * @throws Exception\ApiException
     */
    public function testCapture()
    {
        $this->setTestConfig();
        $captureData = [
            'currency' => 'USD',
            'amount' => 1000,
            'order_id' => $this->orderID['order_id']
        ];
        $data = Order::capture($captureData);
        $result = $data->getData();
        $this->assertIsMyArray($result);
        $this->assertEquals($result['capture_status'], 'captured');
        $this->assertEquals(true, $data->isCaptured(true));
    }

    /**
     * @throws Exception\ApiException
     */
    public function testReverse()
    {
        $this->setTestConfig();
        $reverseData = [
            'currency' => 'USD',
            'amount' => 1000,
            'order_id' => $this->orderID['order_id']
        ];
        $data = Order::reverse($reverseData);
        $result = $data->getData();
        $this->assertNotEmpty($result['order_id'], 'order_id is empty');
        $this->assertEquals($result['response_status'], 'success');
        $this->assertEquals($result['reverse_status'], 'approved');
        $this->assertEquals(true, $data->isReversed());
    }

    /**
     * @throws Exception\ApiException
     */
    public function testTransactionList()
    {
        $this->setTestConfig();
        $data = Order::transactionList($this->orderID);
        $result = $data->getData();
        $this->assertIsMyArray($result);
        $this->assertEquals('approved', $result[0]['transaction_status']);

    }

    /**
     * @param $array
     * @param $message
     */
    private function assertIsMyArray($array, $message = '')
    {
        if (method_exists(get_parent_class($this), 'assertIsArray')) {
            $this->assertIsArray($array, $message);
        } else {
            $this->assertInternalType('array', $array, $message);
        }
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception\ApiException
     */
    private function createOrder($data)
    {
        $data = Pcidss::start($data);
        return $data->getData()['order_id'];
    }
}
