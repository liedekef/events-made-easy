<?php

class EME_PayPal_Client {

    private $client_id;
    private $client_secret;
    private $is_sandbox;
    private $base_url;

    public function __construct() {
        $this->client_id     = get_option( 'eme_paypal_clientid' );
        $this->client_secret = get_option( 'eme_paypal_secret' );
        $this->is_sandbox    = get_option( 'eme_paypal_url' ) === 'sandbox';
        $this->base_url      = $this->is_sandbox ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';
    }

    private function get_access_token() {
        $response = wp_remote_post( "{$this->base_url}/v1/oauth2/token", [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( "{$this->client_id}:{$this->client_secret}" ),
            ],
            'body'    => 'grant_type=client_credentials',
            'timeout' => 10,
        ]);

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'PayPal auth error: ' . $response->get_error_message() );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['access_token'] ) ) {
            throw new Exception( 'PayPal: failed to obtain access token' );
        }

        return $data['access_token'];
    }

    private function make_request( $method, $endpoint, $body = null ) {
        $token = $this->get_access_token();
        $args = [
            'method'  => $method,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer $token",
            ],
            'timeout' => 15,
        ];

        if ( $body ) {
            $args['body'] = is_string( $body ) ? $body : json_encode( $body );
        }

        $url = $this->base_url . $endpoint;
        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            throw new Exception( "PayPal API error ({$endpoint}): " . $response->get_error_message() );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            throw new Exception( "PayPal API error ({$endpoint}): HTTP {$code}, " . print_r( $data, true ) );
        }

        return $data;
    }

    public function create_order( $price, $currency, $description, $payment_id, $return_url, $cancel_url ) {
        $order_data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $payment_id,
                'description'  => $description,
                'amount'       => [
                    'currency_code' => $currency,
                    'value'         => number_format( $price, 2, '.', '' ),
                ],
            ]],
            'application_context' => [
                'cancel_url'  => $cancel_url,
                'return_url'  => $return_url,
                'brand_name'  => get_bloginfo( 'name' ),
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
            ],
        ];

        return $this->make_request( 'POST', '/v2/checkout/orders', $order_data );
    }

    public function capture_order( $order_id ) {
        return $this->make_request( 'POST', "/v2/checkout/orders/{$order_id}/capture" );
    }

    public function refund_capture( $capture_id, $amount, $currency ) {
        $refund_data = [
            'amount' => [
                'value'         => number_format( $amount, 2, '.', '' ),
                'currency_code' => $currency,
            ],
            'note_to_payer' => __( 'Booking cancelled and refunded', 'events-made-easy' ),
        ];
        return $this->make_request( 'POST', "/v2/payments/captures/{$capture_id}/refund", $refund_data );
    }

    public function register_webhook( $webhook_url ) {
        // First, list and delete existing webhooks for this URL
        try {
            $list = $this->make_request( 'GET', '/v1/notifications/webhooks' );
            if ( ! empty( $list['webhooks'] ) ) {
                foreach ( $list['webhooks'] as $hook ) {
                    if ( isset( $hook['url'] ) && $hook['url'] === $webhook_url ) {
                        $this->make_request( 'DELETE', "/v1/notifications/webhooks/{$hook['id']}" );
                    }
                }
            }
        } catch ( Exception $e ) {
            // Ignore list errors; proceed to create
        }

        $webhook_data = [
            'url' => $webhook_url,
            'event_types' => [
                ['name' => 'CHECKOUT.ORDER.APPROVED'],
                // Add more if needed
            ],
        ];

        return $this->make_request( 'POST', '/v1/notifications/webhooks', $webhook_data );
    }

    public function verify_webhook_signature( $payload, $headers ) {
        $required = ['Paypal-Transmission-Id', 'Paypal-Transmission-Time', 'Paypal-Transmission-Sig', 'Paypal-Cert-Url', 'Paypal-Auth-Algo'];
        foreach ( $required as $h ) {
            if ( ! isset( $headers[ $h ] ) ) {
                throw new Exception( "Missing header: $h" );
            }
        }

        $webhook_id = get_option( 'eme_paypal_webhook_id' );
        if ( ! $webhook_id ) {
            throw new Exception( 'Webhook ID not configured' );
        }

        // Validate cert URL
        $cert_url = $headers['Paypal-Cert-Url'];
        if ( ! preg_match( '#^https://api\.paypal\.(com|sandbox\.com)/#', $cert_url ) ) {
            throw new Exception( 'Invalid cert URL' );
        }

        $verify_data = [
            'transmission_id'   => $headers['Paypal-Transmission-Id'],
            'transmission_time' => $headers['Paypal-Transmission-Time'],
            'cert_url'          => $cert_url,
            'auth_algo'         => $headers['Paypal-Auth-Algo'],
            'transmission_sig'  => $headers['Paypal-Transmission-Sig'],
            'webhook_id'        => $webhook_id,
            'webhook_event'     => json_decode( $payload, true ),
        ];

        $result = $this->make_request( 'POST', '/v1/notifications/verify-webhook-signature', $verify_data );
        return ( $result['verification_status'] ?? '' ) === 'SUCCESS';
    }

    public function is_configured() {
        return ! empty( $this->client_id ) && ! empty( $this->client_secret );
    }
}
