<?php

namespace Instamojo;

use Instamojo\Exceptions\AuthenticationException;
use Instamojo\Exceptions\InvalidRequestException;
use Instamojo\Exceptions\MissingParameterException;

final class Instamojo {
    // Constants
    const API_VERSION         = '2';
    const VALID_TYPES         = ['app', 'user'];
    const TEST_BASE_URL       = 'https://test.instamojo.com/';
    const PRODUCTION_BASE_URL = 'https://api.instamojo.com/';
    
    const URIS = [
        'auth'              => 'oauth2/token/',
        'payments'          => 'v'.self::API_VERSION.'/payments/',
        'payment_requests'  => 'v'.self::API_VERSION.'/payment_requests/',
        'gateway_orders'    => 'v'.self::API_VERSION.'/gateway/orders/',
        'refunds'           => 'v'.self::API_VERSION.'/refunds/',
    ];

    // Static Variables

    /**
     * @property string
     * 
     */
    private static $apiVersion;

    /**
     * @property string
     * 
     */
    private static $authType;

    /**
     * @property string
     * 
     */
    private static $baseUrl;

    /**
     * @property string
     * 
     */
    private static $clientId;

    /**
     * @property string
     * 
     */
    private static $clientSecret;

    /**
     * @property string
     * 
     */
    private static $username;

    /**
     * @property string
     * 
     */
    private static $password;

    /**
     * @property string
     * 
     */
    private static $accessToken;

    /**
     * @property string
     * 
     */
    private static $refreshToken;

    /**
     * @property string
     * 
     */
    private static $scope;

    /**
     * @property Instamojo
     * 
     */
    private static $thisObj;

    /**
     * @return string
     * 
     */
    public function getAuthType()
    {
        return self::$authType;
    }

    /**
     * @return string
     * 
     */
    public function getClientId()
    {
        return self::$clientId;
    }

    /**
     * @return string
     * 
     */
    public function getClientSecret()
    {
        return self::$clientSecret;
    }

    /**
     * @return string
     * 
     */
    public function getAccessToken()
    {
        return self::$accessToken;
    }

    /**
     * @return string
     * 
     */
    public function getRefreshToken()
    {
        return self::$refreshToken;
    }

    /**
     * @return string
     * 
     */
    public function getBaseUrl()
    {
        return self::$baseUrl;
    }

    /**
     * @return string
     * 
     */
    public function getScope()
    {
        return self::$scope;
    }

    /**
     * @return string
     * 
     */
    public function __toString()
    {
        return sprintf(
            'Instamojo {'.
            '\nauth_type=%s,'.
            '\nclient_id=%s,'.
            '\nclient_secret=%s,'.
            '\nbase_url=%s,'.
            '\naccess_token=%s'.
            '\n}',
            $this->getAuthType(),
            $this->getClientId(),
            $this->getClientSecret(),
            $this->getBaseUrl(),
            $this->getAccessToken()
        );
    }

    /**
     * __costruct method is defined as private,
     * so "new Instamojo()" will not work
     */
    private function __construct() {}

    /**
     * Initializes the Instamojo environment with default values 
     * and returns a singleton object of Instamojo class.
     * 
     * @param $type 
     * @param $params
     * @param $test
     * 
     * @return Instamojo
     */
    static function init($type='app', $params, $test=false)
    {
        self::validateTypeParams($type, $params);
        self::$authType     = $type;
        self::$clientId     = $params['client_id'];
        self::$clientSecret = $params['client_secret'];
        self::$username     = isset($params['username']) ? $params['username'] : '';
        self::$password     = isset($params['password']) ? $params['password'] : '';
        self::$baseUrl      = Instamojo::PRODUCTION_BASE_URL;
        self::$scope        = isset($params['scope']) ? $params['scope'] : null;

        if ($test) {
            self::$baseUrl = Instamojo::TEST_BASE_URL;
        }

        self::$thisObj = new Instamojo();

        $auth_response = self::$thisObj->auth();
        
        self::$accessToken  = $auth_response['access_token'];
        self::$refreshToken = isset($auth_response['refresh_token']) ? $auth_response['refresh_token'] : '';
        self::$scope = isset($auth_response['scope']) ? $auth_response['scope'] : '';

        return self::$thisObj;
    }

    /**
     * Validates params for Instamojo initialization
     * 
     * @param $type
     * @param $params
     * 
     * @return null
     * 
     * @throws InvalidRequestException 
     * @throws MissingParameterException
     * 
     */
    private static function validateTypeParams($type, $params)
    {
        if (!in_array(strtolower($type), Instamojo::VALID_TYPES)) {
            throw new InvalidRequestException('Invalid init type');
        }

        if (empty($params['client_id'])) {
            throw new MissingParameterException('Client Id is missing');
        }

        if (empty($params['client_secret'])) {
            throw new MissingParameterException('Client Secret is missing');
        }

        if (strtolower($type) == 'user') {
            if (empty($params['username'])) {
                throw new MissingParameterException('Username is missing');
            }

            if (empty($params['password'])) {
                throw new MissingParameterException('Password is missing');
            }
        }
    }

    /**
     * Initializes baseUrl property of Instamojo class
     * 
     * @return object
     */
    public function withBaseUrl($baseUrl) 
    {
        self::$baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Build headers for api request
     * 
     * @return array
     */
    private function build_headers($auth=false) 
    {
        $headers = [];

        if(!$auth && empty(Instamojo::$accessToken)) {
            throw new InvalidRequestException('Access token not available');
        }

        $headers[] = 'Authorization: Bearer '.Instamojo::$accessToken;

        return $headers;        
    }

    /**
     * Requests api data
     * 
     * @param $method
     * @param $path
     * @param $data
     * 
     * @return array
     * 
     */
    private function request_api_data($method, $path, $data=[])
    {
        $headers = $this->build_headers(Instamojo::URIS['auth'] == $path);

        $url = self::$baseUrl . $path;
        
        return api_request($method, $url, $data, $headers);
    }

    /**
     * Make auth request
     * 
     * @return array
     * 
     * @throws Exception
     * 
     */
    public function auth($refresh=false) {
        $data = [
            'client_id'     => self::$clientId,
            'client_secret' => self::$clientSecret,
        ];

        if ($refresh) {
            $data['grant_type']    = 'refresh_token';
            $data['refresh_token'] = self::$refreshToken;
        } else {
            switch(self::$authType) {
                case 'app':
                    $data['grant_type'] = 'client_credentials';
                break;

                case 'user':
                    $data['grant_type'] = 'password';
                    $data['username'] = self::$username;
                    $data['password'] = self::$password;
                break;
            };
        }

        if(self::$scope !=null ) {

            $data['scope'] = self::$scope;
        }
        
        $response = $this->request_api_data('POST', Instamojo::URIS['auth'], $data);
        
        // check for access token
        if (!isset($response['access_token'])) {
            throw new AuthenticationException();
        }

        // check refresh token, incase of auth refresh
        if ($refresh) {
            if (!isset($response['refresh_token'])) {
                throw new AuthenticationException();
            } else {
                self::$refreshToken = $response['refresh_token'];
            }
        }

        self::$accessToken = $response['access_token'];

        return $response;
    }

    /**
     * Get payments
     * 
     * @return array
     * 
     */
    public function getPayments($limit=null, $page=null) {
        $data = [];

        // Check per_page limit
        if (!is_null($limit)) {
            $data['limit'] = $limit;
        }

        // Check page number
        if (!is_null($page)) {
            $data['page'] = $page;
        }

        $response = $this->request_api_data('GET', Instamojo::URIS['payments'], $data);

        return $response['payments'];
    }

    /**
     * Get details of payment
     * 
     * @return object
     * 
     */
    public function getPaymentDetails($id) {
        return $this->request_api_data('GET', Instamojo::URIS['payments'] . $id . '/');
    }

    /**
     * Get refund request for a payment
     * 
     * @param $payment_id
     * @param $params
     * 
     * @return array
     */
    public function createRefundForPayment($payment_id, $params)
    {
        $data = [];

        // transaction id
        $data['transaction_id'] = (!empty($params['transaction_id'])) ? $params['transaction_id'] : null;

        // refund type
        $data['type'] = (!empty($params['type'])) ? $params['type'] : null;

        // explaination body
        $data['body'] = (!empty($params['body'])) ? $params['body'] : null;

        // refund amount
        $data['refund_amount'] = (!empty($params['refund_amount'])) ? $params['refund_amount'] : null;
       
        $response = $this->request_api_data('POST', Instamojo::URIS['payments'] . $payment_id . '/refund/', $data);

         return $response;
    }

    /**
     * Create payment request
     * 
     * @param $params
     * 
     * @return array
     * 
     */
    public function createPaymentRequest($params)
    {
        $response = $this->request_api_data('POST', Instamojo::URIS['payment_requests'], $params);
        
        return $response;
    }

    /**
     * get payment request
     * 
     * @param $params
     * 
     * @return array
     * 
     */
    public function getPaymentRequests($limit=null, $page=null)
    {
        $data = [];

        // Check per_page limit
        if (!is_null($limit)) {
            $data['limit'] = $limit;
        }

        // Check page number
        if (!is_null($page)) {
            $data['page'] = $page;
        }
      
        $response = $this->request_api_data('GET', Instamojo::URIS['payment_requests'], $data);
        
        return $response['payment_requests'];
    }

    /**
     * Get gateway order
     * 
     * @param $id
     * 
     * @return array
     * 
     */
    public function getPaymentRequestDetails($id)
    {

        $response = $this->request_api_data('GET', Instamojo::URIS['payment_requests'] . $id .'/');
        
        return $response;
    }

    /**
     * Create gateway order
     * 
     * @param $params
     * 
     * @return array
     * 
     */
    public function createGatewayOrder($params)
    {
        $response = $this->request_api_data('POST', Instamojo::URIS['gateway_orders'], $params);
        
        return $response;
    }

    /**
     * Create gateway order for payment request
     * 
     * @param $payment_request_id
     * @param $params
     * 
     * @return array
     * 
     */
    public function createGatewayOrderForPaymentRequest($payment_request_id, $params)
    {
        // payment request id
        $data = [
            'id' => $payment_request_id
        ];

        // name
        $data['name'] = (!empty($params['name'])) ? $params['name'] : null;

        // email
        $data['email'] = (!empty($params['email'])) ? $params['email'] : null;

        // phone
        $data['phone'] = (!empty($params['phone'])) ? $params['phone'] : null;
        
        $response = $this->request_api_data('POST', Instamojo::URIS['gateway_orders'] . 'payment-request/', $data);
        
        return $response;
    }

    /**
     * Get gateway order
     * 
     * @param $id
     * 
     * @return array
     * 
     */
    public function getGatewayOrder($id)
    {
        $response = $this->request_api_data('GET', Instamojo::URIS['gateway_orders'] . 'id:$id/');
        
        return $response;
    }

    /**
     * Get gateway orders list
     * 
     * @param $limit
     * @param $page
     * 
     * @return array
     * 
     */
    public function getGatewayOrders($limit=null, $page=null) {
        $data = [];

        // Check per_page limit
        if (!is_null($limit)) {
            $data['limit'] = $limit;
        }

        // Check page number
        if (!is_null($page)) {
            $data['page'] = $page;
        }

        $response = $this->request_api_data('GET', Instamojo::URIS['gateway_orders'], $data);

        return $response['orders'];
    }

    /**
     * Get refunds
     * 
     * @param $limit
     * @param $page
     * 
     * @return array
     * 
     */
    public function getRefunds($limit=null, $page=null) {

        $data = [];

        // Check per_page limit
        if (!is_null($limit)) {
            $data['limit'] = $limit;
        }

        // Check page number
        if (!is_null($page)) {
            $data['page'] = $page;
        }

        $response = $this->request_api_data('GET', Instamojo::URIS['refunds'], $data);

        return $response['refunds'];
    }

    /**
     * Get details of refund
     * 
     * @param $id
     * 
     * @return object
     * 
     */
    public function getRefundDetails($id) {
        return $this->request_api_data('GET', Instamojo::URIS['refunds'] . $id . '/');
    }

    /**
     * __clone method is defined as private,
     * so nobody can clone the instance
     */
    private function __clone() {}

    /**
     * __wakeup method is defined as private,
     * so nobody can unserialize the instance
     */
    private function __wakeup() {}

    /**
     * __sleep method is defined as private,
     * so nobody can serialize the instance
     */
    private function __sleep() {}
}