<?php

namespace Cloudipsp\Api\Order;

use Cloudipsp\Api\Api;

class Subscription extends Api
{
    private $url = '/subscription/';
    /**
     * Minimal required params
     * @var array
     */
    private $requiredParams = [
        'merchant_id' => 'integer',
        'action' => 'string',
        'order_id' => 'string'
    ];

    /**
     * @param $data
     * @param array $headers
     * @return mixed
     * @throws \Cloudipsp\Exception\ApiException
     */
    public function get($data, $headers = [])
    {
        $requestData = $this->prepareParams($data);
        $this->validate($requestData, $this->requiredParams);
        return $this->Request($method = 'POST', $this->url, $headers, $requestData);
    }
}
