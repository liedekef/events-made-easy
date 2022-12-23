<?php

    use Instamojo\Exceptions\ApiException;
    use Instamojo\Exceptions\InvalidRequestException;
    use Instamojo\Exceptions\AuthenticationException;
    use Instamojo\Exceptions\ActionForbiddenException;

    /**
     * 
     * @param string $method ('GET', 'POST', 'DELETE', 'PATCH')
     * @param string $request_url whichever API url you want to target.
     * @param array $data contains the POST data to be sent to the API.
     * @param array $headers contains the necessary.
     * @return array decoded json returned by API.
     */
    function api_request($method, $request_url, array $data=[], array $headers=[]) 
    {
        $http_success_codes = [200, 201, 202, 204];

        $method = (string) $method;
        $data = (array) $data;

        $package_name = 'instamojo-php';
        $package_version='2.0';
        $os = php_uname('s');
        $os_version = php_uname('r');
        $php_version =  phpversion();

        $userAgent = $package_name.'/'.$package_version.' '.$os.'/'.$os_version.' '.'php/'.$php_version;

        $headers['User-Agent'] = $userAgent;

        $options = array();
        $options[CURLOPT_HTTPHEADER] = $headers;
        $options[CURLOPT_RETURNTRANSFER] = true;

       
        if($method == 'POST') {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        } else if($method == 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        } else if($method == 'PATCH') {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);         
            $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        } else if ($method == 'GET' or $method == 'HEAD') {
            if (!empty($data)) {
                /* Update URL to container Query String of Paramaters */
                $request_url .= '?' . http_build_query($data);
            }
        }
        // $options[CURLOPT_VERBOSE] = true;
        $options[CURLOPT_URL] = $request_url;
        $options[CURLOPT_SSL_VERIFYPEER] = true;
        $options[CURLOPT_CAINFO] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem';

        $curl_request = curl_init();
        $setopt = curl_setopt_array($curl_request, $options);
        $response = curl_exec($curl_request);
        
        $http_code = curl_getinfo($curl_request, CURLINFO_HTTP_CODE);
        $headers = curl_getinfo($curl_request);

        $error_number = curl_errno($curl_request);
        $error_message = curl_error($curl_request);
        $response_obj = json_decode($response, true);
      
        if($error_number != 0){
            if($error_number == 60){
                throw new InvalidRequestException(
                    'Something went wrong. cURL raised an error with number: '.
                    $error_number.' and message: '.$error_message.
                    'Please check http://stackoverflow.com/a/21114601/846892 '.
                    'for a fix.'.PHP_EOL
                );
            }
            else{
                throw new InvalidRequestException(
                    'Something went wrong. cURL raised an error with number: '.
                    $error_number.' and message: '.$error_message.
                    '.'.PHP_EOL);
            }
        }

        if(!in_array($http_code, $http_success_codes) || (isset($response_obj['success']) && $response_obj['success'] == false)) {
            
            $message = isset($response_obj['message']) ? $response_obj['message'] : 'Invalid request';
            
            switch($http_code) {
                case 401:
                    throw new AuthenticationException();
                case 403:
                    throw new ActionForbiddenException($message);
               
                default:
                    $message = isset($response_obj['reason']) ? $response_obj['reason'] :$message;
                    throw new ApiException($http_code, $error_number, $message);
            }  
        }
        
        return $response_obj;
    }