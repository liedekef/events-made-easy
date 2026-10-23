<?php
/**
 * Created by PhpStorm.
 * User: dm
 * Date: 21.05.18
 * Time: 0:15
 */

namespace Cloudipsp;

use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    private $mid = 1396424;
    private $Secret = 'test';
    private $request_types = ['json', 'xml', 'form'];
    private $TestData = [
        'currency' => 'USD',
        'amount' => 111,
        'rectoken' => ''
    ];
    private $TestCardnon3ds = [
        'card_number' => '4444555511116666',
        'cvv2' => '333',
        'expiry_date' => '1222',
        'required_rectoken' => 'Y'
    ];
    private $TestPcidssData = [
        'currency' => 'USD',
        'amount' => 1,
        'client_ip' => '127.2.2.1'
    ];

    /**
     * PaymentTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws Exception\ApiException
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->setTestConfig();
        $this->TestData['rectoken'] = $this->getToken(array_merge($this->TestPcidssData, $this->TestCardnon3ds));
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Setting test config
     */
    private function setTestConfig()
    {
        Configuration::setMerchantId($this->mid);
        Configuration::setSecretKey($this->Secret);

    }

    /**
     * @throws Exception\ApiException
     */
    public function testRecurring()
    {
        $this->setTestConfig();
        Configuration::setApiVersion('1.0');
        foreach ($this->request_types as $type) {
            Configuration::setRequestType($type);
            $result = Payment::recurring($this->TestData);
            $this->assertEquals($result->isApproved(), true);
            $this->assertEquals($result->isValid(), true);
            $this->assertEquals($result->getData()['response_status'], 'success');
        }
    }

    /**
     * @throws Exception\ApiException
     */
    public function testRecurringv2()
    {
        $this->setTestConfig();
        Configuration::setApiVersion('2.0');
        Configuration::setRequestType('json');
        $result = Payment::recurring($this->TestData);
        $this->assertEquals($result->isApproved(), true);
        $this->assertEquals($result->isValid(), true);
        $this->assertEquals($result->getData()['response_status'], 'success');

    }

    /**
     * @throws Exception\ApiException
     */
    public function testReports()
    {
        $this->setTestConfig();
        $data = [
            "date_from" => date('d.m.Y H:i:s', time() - 7200),
            "date_to" => date('d.m.Y H:i:s', time() - 3600),
        ];
        $reports = Payment::reports($data);
        $this->assertEquals($reports->getData()[0]['response_status'], 'success');

    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception\ApiException
     */
    private function getToken($data)
    {
        $data = Pcidss::start($data);
        return $data->getData()['rectoken'];
    }
}
