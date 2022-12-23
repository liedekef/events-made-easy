<?php

namespace Cloudipsp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    private $TestData = [
        'merchant_id' => 1396424,
        'card_number' => '4444555511116666',
        'cvv2' => '333',
        'order_desc' => 'testing',
        'date' => '1994-08-05',
        'client_ip' => '127.0.0.1'
    ];
    private $requiredParams = [
        'merchant_id' => 'integer',
        'order_desc' => 'string',
        'card_number' => 'ccnumber',
        'date' => 'date',
        'client_ip' => 'ip'
    ];

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testValidateCard()
    {
        try {
            $is_true = Helper\ValidationHelper::validateRequiredParams($this->TestData, $this->requiredParams);
            $this->assertTrue($is_true, 'Params is valid');
        } catch (InvalidArgumentException $e) {
            $this->fail($e);
        }
    }
}
