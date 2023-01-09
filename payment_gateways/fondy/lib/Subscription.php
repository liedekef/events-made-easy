<?php


namespace Cloudipsp;

use Cloudipsp\Api\Checkout as Api;
use Cloudipsp\Api\Order as ApiRecurring;
use Cloudipsp\Response\Response;

class Subscription
{
    private static $requiredApiVersion = '2.0';
    /**
     * Minimal required params to get checkout
     * @var array
     */
    private static $requiredParams = [
        'recurring_data' => [
            'start_time' => 'date',
            'amount' => 'integer',
            'every' => 'integer',
            'period' => 'string'
        ]
    ];
    private static $defaultParams = [
        'subscription' => 'Y'
    ];

    /**
     * return checkout url with calendar
     * @param $data
     * @param array $headers
     * @return Response
     * @throws Exception\ApiException
     */
    public static function url($data, $headers = [])
    {
        if (\Cloudipsp\Configuration::getApiVersion() !== self::$requiredApiVersion) {
            trigger_error('Reccuring_data allowed only for api version \'2.0\'', E_USER_NOTICE);
            \Cloudipsp\Configuration::setApiVersion(self::$requiredApiVersion);
        }
        $data = array_merge($data, self::$defaultParams);
        $api = new Api\Url();
        $result = $api->get($data, $headers, self::$requiredParams);
        return new Response($result);
    }

    /**
     * stop calendar recurring payments
     * @param $order_id
     * @param array $headers
     * @return Response
     * @throws Exception\ApiException
     */
    public static function stop($order_id, $headers = [])
    {
        if (\Cloudipsp\Configuration::getApiVersion() !== self::$requiredApiVersion) {
            trigger_error('Reccuring_data allowed only for api version \'2.0\'', E_USER_NOTICE);
            \Cloudipsp\Configuration::setApiVersion(self::$requiredApiVersion);
        }
        $api = new ApiRecurring\Subscription();
        $data = [
            "order_id" => $order_id,
            "action" => "stop"
        ];
        $result = $api->get($data, $headers);
        return new Response($result);
    }

    /**
     * return checkout token with calendar
     * @param $data
     * @param array $headers
     * @return Response
     * @throws Exception\ApiException
     */
    public static function token($data, $headers = [])
    {
        if (\Cloudipsp\Configuration::getApiVersion() !== self::$requiredApiVersion) {
            trigger_error('Reccuring_data allowed only for api version \'2.0\'', E_USER_NOTICE);
            \Cloudipsp\Configuration::setApiVersion(self::$requiredApiVersion);
        }
        $data = array_merge($data, self::$defaultParams);
        $api = new Api\Token;
        $result = $api->get($data, $headers, self::$requiredParams);
        return new Response($result);
    }


}
