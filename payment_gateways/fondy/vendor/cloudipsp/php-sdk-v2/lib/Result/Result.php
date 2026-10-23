<?php

namespace Cloudipsp\Result;

use Cloudipsp\Helper\ResponseHelper;
use Cloudipsp\Helper\ResultHelper;
use Cloudipsp\Configuration;

class Result
{
    /**
     * @var array
     */
    protected $result;
    /**
     * @var array
     */
    protected $requestType;
    /**
     * @var array
     */
    protected $secretKey;
    /**
     * @var array
     */
    protected $apiVersion;

    /**
     * Result constructor.
     * @param array $data
     * @param string $key
     * @param string $type
     * @param bool $formatted
     */
    public function __construct(array $data = [], $key = '', $type = '', $formatted = true)
    {
        $this->apiVersion = Configuration::getApiVersion();
        if (!$type) {
            $this->requestType = Configuration::getRequestType();
        } else {
            $this->requestType = $type;
        }
        if (!$data) {
            $this->result = $this->parseResult();
        } else {
            $this->result = $data;
        }
        if (!$key) {
            $this->secretKey = Configuration::getSecretKey();
        } else {
            $this->secretKey = $key;
        }
        if ($formatted)
            $this->result = $this->formatResult($this->result);
    }

    /**
     * @return array|string
     */
    private function parseResult()
    {
        $result = $_POST;
        if (empty($result))
            $result = file_get_contents('php://input');
        return $result;

    }

    /**
     * @param $result
     * @return array|string
     */
    private function formatResult($result)
    {
        if ($this->apiVersion === '1.0' && is_string($result)) {
            switch ($this->requestType) {
                case 'xml':
                    $result = ResponseHelper::xmlToArray($result, true, true, 'UTF-8');
                    break;
                case 'json':
                    $result = ResponseHelper::jsonToArray($result);
                    break;
            }
        } else if ($this->apiVersion === '2.0' and is_string($result)) {
            $result = ResponseHelper::jsonToArray($result);
        }
        return $result;
    }

    /**
     * Get formatted data
     * @return array
     */
    public function getData()
    {
        if(!$this->result or $this->result == '')
            return [];

        if(isset($this->result['response']))
            $this->result = $this->result['response'];

        if ($this->apiVersion === '2.0') {
            if(!isset($this->result['data']))
                return [];
            $result = ResponseHelper::getBase64Data(['response' => $this->result]);
            $result['encodedData'] = $this->result['data'];
            $result['signature'] = $this->result['signature'];
            return $result;
        } else {
            return $this->result;
        }
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        $data = $this->getData();
        return ResultHelper::isPaymentApproved($data, $this->secretKey, $this->apiVersion);
    }

    /**
     * @return bool
     */
    public function getToken()
    {
        $data = $this->getData();
        return $data['rectoken'] ? $data['rectoken'] : false;
    }

    /**
     * @param $param
     * @return bool
     */
    public function getParam($param)
    {
        $data = $this->getData();
        return $data[$param] ? $data[$param] : false;
    }

    /**
     * @param null $data
     * @return bool
     */
    public function isValid($data = null)
    {
        if ($data == null)
            $data = $this->getData();
        return ResultHelper::isPaymentValid($data, $this->secretKey, $this->apiVersion);
    }

    /**
     * @return bool
     */
    public function isProcessing()
    {
        $data = $this->getData();
        if (!isset($data['order_status']))
            return false;
        $valid = $this->isValid($data);
        if ($valid && $data['order_status'] === 'processing')
            return true;

        return false;
    }

    /**
     * @return bool
     */
    public function isDeclined()
    {
        $data = $this->getData();
        if (!isset($data['order_status']))
            return false;
        $valid = $this->isValid($data);
        if ($valid && $data['order_status'] === 'declined')
            return true;

        return false;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        $data = $this->getData();
        if (!isset($data['order_status']))
            return false;
        $valid = $this->isValid($data);
        if ($valid && $data['order_status'] === 'expired')
            return true;

        return false;
    }
}
