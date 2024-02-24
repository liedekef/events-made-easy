<?php
require_once __DIR__ . '/../../../../../../autoloader.php';

use Tco\Source\Api\Auth\AuthApi;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../Fixtures/TestsConfig.php';



class AuthTest extends TestCase {
    public $authTest;
    public $sellerId;
    public $secretKey;

    public function setUp(): void {
        try {
            $this->authTest = new AuthApi(TestsConfig::SELLER_ID, TestsConfig::SECRET_KEY);
            $this->sellerId = $this->authTest->getSellerId();
            $this->secretKey = $this->authTest->getSecretKey();
        }
        catch (Exception $e){
            fwrite(STDERR, print_r('Error instantiating Auth: '.$e->getMessage(), TRUE));
        }
    }

    public function __toString() {
        return 'AuthTest';
    }

    public function testAllSubtests() {
        $this->testCheckAttributes();
        $this->testGetHeaders();
        $this->testGetHeadersFail();
    }

    public function testCheckAttributes() {
        //class scope attributes
        $this->assertClassHasAttribute('sellerId', 'Tco\Source\Api\Auth\AuthApi');
        $this->assertClassHasAttribute('secretKey', 'Tco\Source\Api\Auth\AuthApi');
    }

    public function testCredentialsSet(){
        $this->assertEquals(TestsConfig::SELLER_ID, $this->authTest->getSellerId());
        $this->assertEquals(TestsConfig::SECRET_KEY, $this->authTest->getSecretKey());
    }

    public function testGetHeaders(){
        $gmtDate = gmdate( 'Y-m-d H:i:s' );
        $string  = strlen( $this->sellerId ) . $this->sellerId . strlen( $gmtDate ) . $gmtDate;
        $hash    = hash_hmac( 'sha3-256', $string, $this->secretKey );

        $expectedHeadersString = 'Content-Type: application/json'.'Accept: application/json'.'X-Avangate-Authentication: code="' . $this->sellerId . '" date="' . $gmtDate . '" hash="' . $hash . '" algo="sha3-256"';
        $actualHeaderString = implode('', $this->authTest->getHeaders());

        $this->assertEquals($expectedHeadersString, $actualHeaderString);
    }

    public function testGetHeadersFail(){
        $gmtDate = gmdate( 'Y-m-d H:i:s' );
        $string  = strlen( $this->sellerId.'1' ) . $this->sellerId . strlen( $gmtDate ) . $gmtDate;
        $hash    = hash_hmac( 'sha3-256', $string, $this->secretKey );

        $expectedHeadersString = 'Content-Type: application/json'.'Accept: application/json'.'X-Avangate-Authentication: code="' . $this->sellerId . '" date="' . $gmtDate . '" hash="' . $hash . '" algo="sha3-256"';
        $actualHeaderString = implode('', $this->authTest->getHeaders());

        $this->assertNotEquals($expectedHeadersString, $actualHeaderString);
    }


}
