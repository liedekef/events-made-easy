<?php

namespace Payconiq;

// Require the exception classes
require_once __DIR__ . '/Support/Exceptions/CreatePaymentFailedException.php';
require_once __DIR__ . '/Support/Exceptions/RetrievePaymentFailedException.php';
require_once __DIR__ . '/Support/Exceptions/GetPaymentsListFailedException.php';
require_once __DIR__ . '/Support/Exceptions/RefundFailedException.php';
require_once __DIR__ . '/Support/Exceptions/GetRefundIbanFailedException.php';

use Payconiq\Support\Exceptions\CreatePaymentFailedException;
use Payconiq\Support\Exceptions\RetrievePaymentFailedException;
use Payconiq\Support\Exceptions\GetPaymentsListFailedException;
use Payconiq\Support\Exceptions\RefundFailedException;
use Payconiq\Support\Exceptions\GetRefundIbanFailedException;

class Client {

    const ENVIRONMENT_PROD = 'prod';
    const ENVIRONMENT_EXT = 'ext';

    protected $apiKey;
    protected $endpoint;

    /**
     * Construct
     *
     * @param  string $apiKey		Used to secure request between merchant backend and Payconiq backend.
     * @param  string $environment	Environment to use when making API calls
     * 
     * @return void
     */
    public function __construct( $apiKey = null, $environment = self::ENVIRONMENT_PROD ) {
        $this->apiKey = $apiKey;
        // Define the transition date and time (21 Sept 2025, 04:00 CET as the safe switch time)
        $transitionDate = new \DateTime('2025-09-21 04:00:00', new \DateTimeZone('Europe/Brussels'));
        $currentDate = new \DateTime('now', new \DateTimeZone('Europe/Brussels'));

        if ($currentDate >= $transitionDate) {
            // Use new endpoints after transition
            $this->endpoint = $environment == self::ENVIRONMENT_PROD
                ? 'https://merchant.api.bancontact.net/v3'
                : 'https://merchant.api.preprod.bancontact.net/v3';
        } else {
            // Use current endpoints before transition
            $this->endpoint = $environment == self::ENVIRONMENT_PROD
                ? 'https://api.payconiq.com/v3'
                : 'https://api.ext.payconiq.com/v3';
        }
    }

    /**
     * Set the endpoint
     *
     * @param  string $url  The endpoint of the Payconiq API.
     *
     * @return self
     */
    public function setEndpoint( $url ) {
        $this->endpoint = $url;

        return $this;
    }

    /**
     * Set the endpoint to test env
     *
     * @return self
     */
    public function setEndpointTest() {
        $this->endpoint = 'https://api.ext.payconiq.com/v3';

        return $this;
    }

    /**
     * Set the API key
     *
     * @param  string $apiKey  Used to secure request between merchant backend and Payconiq backend.
     *
     * @return self
     */
    public function setApiKey( $apiKey ) {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Create a new payment
     * 
     * @param  float $amount		Payment amount in cents
     * @param  string $currency		Payment currency code in IOS 4217 format
     * @param  string $description	Payment description shown during payment (optional)
     * @param  string $reference	External payment reference used to reference the Payconiq payment in the calling party's system (optional)
     * @param  string $bulkId	    BulkId for bulk payouts (optional)
     * @param  string $callbackUrl  A url to which the merchant or partner will be notified of a payment (optiona)
     * @param  string $returnUrl  Return url to return client after paying on payconiq site itself (optional)
     * 
     * @return object  payment object
     * @throws CreatePaymentFailedException  If the response has no transactionid
     */
    public function createPayment( $amount, $currency = 'EUR', $description='', $reference='', $bulkId='', $callbackUrl='', $returnUrl = null ) {
        $data_arr = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'reference' => $reference,
            'callbackUrl' => $callbackUrl,
        ];
        if ( ! empty( $bulkId ) ) {
            $data_arr['bulkId'] = $bulkId;
        }
        if ( ! empty($returnUrl ) ) {
            $data_arr['returnUrl'] = $returnUrl;
        }
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments' ), $data_arr );

        if ( empty( $response->paymentId ) ) {
            throw new CreatePaymentFailedException( $response->message );
        }

        return $response;
    }

    /**
     * Get payment details of an existing payment
     *
     * @param  string $paymentId  The unique Payconiq identifier of a payment as provided by the create payment service
     *
     * @return  object  Response object by Payconiq
     */
    public function retrievePayment( $paymentId ) {
        $response = $this->makeRequest( 'GET', $this->getEndpoint( '/payments/' . $paymentId ) );

        if ( empty( $response->paymentId ) ) {
            throw new RetrievePaymentFailedException( $response->message );
        }

        return $response;
    }

    /**
     * Get payments list
     *
     * @param  string $reference	External payment reference used to reference the Payconiq payment in the calling party's system
     * 
     * @return  array  Response objects by Payconiq
     */
    public function getPaymentsListByReference( $reference ) {
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/search' ), [
            'reference' => $reference
        ]);

        if ( empty( $response->size ) ) {
            throw new GetPaymentsListFailedException( $response->message );
        }

        return $response->details;
    }

    /**
     * Get payments list
     *
     * @param  string $fromDate	The start date and time to filter the search results.
     *				Default: is the API default: Current date and time minus one day. (Now - 1 day)
     *				Format: YYYY-MM-ddTHH:mm:ss.SSSZ
     * 
     * @param  string $toDate	The end date and time to filter the search results.
     *				Default: is the API default: Current date and time. (Now)
     *				Format: YYYY-MM-ddTHH:mm:ss.SSSZ
     * 
     * @param  int $size	The page size for responses, more used internally
     *              Default: 50
     * 
     * @return  array  Response objects by Payconiq
     */
    public function getPaymentsListByDateRange( $fromDate = '', $toDate = '', $size = 50 ) {
        $param_arr = [
            "paymentStatuses" => [ "SUCCEEDED" ]
        ];
        if ( ! empty( $fromDate ) ) {
            $param_arr['from'] = $fromDate;
        }
        if ( ! empty( $toDate ) ) {
            $param_arr['to'] = $toDate;
        }
        $page = 0;
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/search?page=' . intval( $page ) . '&size=' . intval( $size ) ), $param_arr );

        if ( empty( $response->size ) ) {
            throw new GetPaymentsListFailedException( $response->message );
        }

        $details = $response->details;
        if ( !empty( $response->totalPages ) && $response->totalPages > 1 ) {
            while ($page < $response->totalPages-1) {
                $page=$response->number+1;
                $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/search?page=' . intval( $page ) . '&size=' . intval( $size ) ), $param_arr );
                $details = array_merge($details,$response->details );
            }
        }
        return $details;
    }

    /**
     * Refund an existing payment
     *
     * @param  string $paymentId  The unique Payconiq identifier of a payment as provided by the create payment service
     *
     * @param  float $amount		Payment amount in cents
     * @param  string $currency		Payment currency code in IOS 4217 format
     *
     * @return  object  Response object by Payconiq
     */
    public function refundPayment( $paymentId, $amount, $currency = 'EUR', $description = '' ) {
        $data_arr = [
            'amount' => $amount,
            'currency' => $currency,
        ];
        // description is optional, so add it only when not empty
        if ( ! empty( $description ) ) {
            $data_arr['description'] = $description;
        }
        $response = $this->makeRequest( 'POST', $this->getEndpoint( '/payments/' . $paymentId ), $data_arr );

        if ( empty( $response->paymentId ) ) {
            throw new RefundFailedException( $response->message );
        }

        return $response;
    }

    /**
     * Get refund IBAN
     *
     * @param  string $paymentId  The unique Payconiq identifier of a payment as provided by the create payment service
     *
     * @return  object  Response object by Payconiq
     */
    public function getRefundIban( $paymentId ) {
        $response = $this->makeRequest( 'GET', $this->getEndpoint( '/payments/' . $paymentId . '/debtor/refundIban' ) );

        if ( empty( $response->iban ) ) {
            throw new GetRefundIbanFailedException( $response->message );
        }

        return $response->iban;
    }

    /**
     * Get the endpoint for the call
     *
     * @param  string $route
     */
    private function getEndpoint( $route = null ) {
        return $this->endpoint . $route;
    }

    /**
     * Construct the headers for the cURL call
     * 
     * @return array
     */
    private function constructHeaders() {
        return [
            'Content-Type: application/json',
            'Cache-Control: no-cache',
            'Authorization: Bearer ' . $this->apiKey
        ];
    }

    /**
     * cURL request
     *
     * @param  string $method
     * @param  string $url
     * @param  array $headers
     * @param  array $parameters
     *
     * @return response
     */
    private function makeRequest( $method, $url, $parameters = [] ) {
        $curl = curl_init();

        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 20 );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 20 );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->constructHeaders() );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );
        if ( $method == 'POST') {
            curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $parameters ) );
        }

        $response = curl_exec( $curl );
        curl_close( $curl );

        return json_decode( $response );
    }
}
