<?php

namespace Tco\Source\Ipn;

use Tco\Exceptions\TcoException;
use Tco\Source\TcoConfig;

class IpnSignature {

    /**
     * @var TcoConfig
     */
    private $tcoConfig;

    public function __construct( $config ) {
        $this->tcoConfig = $config;
    }

    public function isIpnValid( $params ) {
        try {
            $result = '';

            $receivedAlgo = $this->getHashAlgorithm($params);
            switch ($receivedAlgo) {
                case 'sha3-256':
                    $receivedHash = $params['SIGNATURE_SHA3_256'];
                    break;
                case 'sha256':
                    $receivedHash = $params['SIGNATURE_SHA2_256'];
                    break;
                default:
                    $receivedHash = $params['HASH'];
                    break;
            }

            foreach ( $params as $key => $val ) {
                if ( !in_array($key ,["HASH", "SIGNATURE_SHA2_256", "SIGNATURE_SHA3_256"]) ) {
                    if ( is_array( $val ) ) {
                        $result .= $this->arrayExpand( $val );
                    } else {
                        $size = strlen( StripSlashes( $val ) );
                        $result .= $size . StripSlashes( $val );
                    }
                }
            }

            if ( isset( $params['REFNO'] ) && ! empty( $params['REFNO'] ) ) {
                $calcHash = $this->generateHash( $this->tcoConfig->getSecretKey(), $result, $receivedAlgo );
                if ( $receivedHash === $calcHash ) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            throw new TcoException(sprintf('Exception validating ipn signature: %s',$e->getMessage()));
        }
    }

    /**
     * @param $ipnParams
     * @param $secret_key
     *
     * @return string
     */
    public function calculateIpnResponse( $ipnParams ) {
        try {
            $resultResponse    = '';
            $ipnParamsResponse = [];
            // we're assuming that these always exist, if they don't then the problem is on avangate side
            $ipnParamsResponse['IPN_PID'][0]   = $ipnParams['IPN_PID'][0];
            $ipnParamsResponse['IPN_PNAME'][0] = $ipnParams['IPN_PNAME'][0];
            $ipnParamsResponse['IPN_DATE']     = $ipnParams['IPN_DATE'];
            $ipnParamsResponse['DATE']         = date( 'YmdHis' );

            foreach ( $ipnParamsResponse as $key => $val ) {
                $resultResponse .= $this->arrayExpand( (array) $val );
            }

            $algorithm = $this->getHashAlgorithm($ipnParams);
            $signature = $this->generateHash( $this->tcoConfig->getSecretKey(), $resultResponse, $algorithm );
            return $this->formatResponse($algorithm, $ipnParamsResponse['DATE'], $signature);
        } catch (\Exception $e){
            throw new TcoException(sprintf('Exception generating ipn response: %s', $e->getMessage()));
        }
    }

    /**
     * @param $array
     *
     * @return string
     */
    private function arrayExpand( $array ) {
        $retval = '';
        foreach ( $array as $key => $value ) {
            $size   = strlen( stripslashes( $value ) );
            $retval .= $size . stripslashes( $value );
        }

        return $retval;
    }

    /**
     * @param $params
     *
     * @return string
     */
    private function getHashAlgorithm( $params ) {
        if (!empty($params['SIGNATURE_SHA3_256'])) {
            $receivedAlgo ='sha3-256';
        } else if (!empty($params['SIGNATURE_SHA2_256'])) {
            $receivedAlgo ='sha256';
        } else {
            $receivedAlgo ='md5';
        }

        return $receivedAlgo;
    }

    /**
     * generates hmac
     *
     * @param string $key
     * @param string $data
     *
     * @return string
     */
    public function generateHash($key, $data, $receivedAlgo = 'sha3-256') {
        if ('sha3-256' || 'sha256' === $receivedAlgo) {
            return hash_hmac($receivedAlgo, $data, $key);
        }

        $b = 64; // byte length for hash
        if (strlen($key) > $b) {
            $key = pack("H*", hash($receivedAlgo, $key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return hash($receivedAlgo, $k_opad . pack("H*", hash($receivedAlgo, $k_ipad . $data)));
    }


    /**
     * generates hmac
     *
     * @param string $algorithm
     * @param string $date
     * @param string $signature
     * 
     * @return string
     */
    private function formatResponse($algorithm, $date, $signature) {
        if ($algorithm == "md5") {
            return sprintf(
                '<EPAYMENT>%s|%s</EPAYMENT>',
                $date,
                $signature
            );
        } else {
            return sprintf(
                '<sig algo="%s" date="%s">%s</sig>',
                $algorithm,
                $date,
                $signature
            );
        }
    }
}
