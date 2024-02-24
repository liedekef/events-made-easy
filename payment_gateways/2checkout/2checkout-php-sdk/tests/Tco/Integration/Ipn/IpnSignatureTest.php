<?php
require_once __DIR__ . '/../../../../autoloader.php';
require_once __DIR__ . '/../../../../tests/Tco/Fixtures/TestsConfig.php';
require_once __DIR__ . '/../../../../tests/Tco/Fixtures/Tokens.php';

use PHPUnit\Framework\TestCase;
use Tco\Source\Ipn\IpnSignature;

final class IpnSignatureTest extends TestCase {

    private $ipnParamsMockArray;
    public $configArr;


    public function __toString() {
        return 'IpnSignatureTest';
    }

    public function setUp(): void {
        $this->configArr = array(
            'sellerId'          => TestsConfig::SELLER_ID,
            'secretKey'         => TestsConfig::SECRET_KEY,
            'buyLinkSecretWord' => TestsConfig::SECRET_WORD,
            'jwtExpireTime'     => 30, //minutes
            'curlVerifySsl'     => 0
        );

        $this->ipnParamsMockArray = json_decode(Tokens::IPN_CALLBACK_JSON, true);
    }

    public function testAll() {
        $this->_testIsSha3IpnValid();
        $this->_testIsSha2IpnValid();
        $this->_testIsMd5IpnValid();
        $this->_testCalculateSha3IpnResponse();
        $this->_testCalculateSha2IpnResponse();
        $this->_testCalculateMd5IpnResponse();
        $this->_testCalculateIpnResponseFailure();
    }

    public function _testIsSha3IpnValid() {
        $tcoConfig = new \Tco\Source\TcoConfig($this->configArr);
        $ipnSignature = new IpnSignature($tcoConfig);
        $params = $this->ipnParamsMockArray;
        unset($params['SIGNATURE_SHA2_256']);
        unset($params['HASH']);
        $isValid = $ipnSignature->isIpnValid($params);
        $this->assertTrue($isValid);
    }

    public function _testIsSha2IpnValid() {
        $tcoConfig = new \Tco\Source\TcoConfig($this->configArr);
        $ipnSignature = new IpnSignature($tcoConfig);
        $params = $this->ipnParamsMockArray;
        unset($params['SIGNATURE_SHA3_256']);
        unset($params['HASH']);
        $isValid = $ipnSignature->isIpnValid($params);
        $this->assertTrue($isValid);
    }

    public function _testIsMd5IpnValid() {
        $tcoConfig = new \Tco\Source\TcoConfig($this->configArr);
        $ipnSignature = new IpnSignature($tcoConfig);
        $params = $this->ipnParamsMockArray;
        unset($params['SIGNATURE_SHA3_256']);
        unset($params['SIGNATURE_SHA2_256']);
        $isValid = $ipnSignature->isIpnValid($params);
        $this->assertTrue($isValid);
    }

    public function _testCalculateSha3IpnResponse(){
        $tcoConfig = new \Tco\Source\TcoConfig($this->configArr);
        $ipnSignature = new IpnSignature($tcoConfig);
        $params = $this->ipnParamsMockArray;
        unset($params['SIGNATURE_SHA2_256']);
        unset($params['HASH']);
        $response = $ipnSignature->calculateIpnResponse($params);
        $this->assertNotEmpty($response);
    }

    public function _testCalculateSha2IpnResponse(){
        $tcoConfig = new \Tco\Source\TcoConfig($this->configArr);
        $ipnSignature = new IpnSignature($tcoConfig);
        $params = $this->ipnParamsMockArray;
        unset($params['SIGNATURE_SHA3_256']);
        unset($params['HASH']);        
        $response = $ipnSignature->calculateIpnResponse($params);
        $this->assertNotEmpty($response);
    }

    public function _testCalculateMd5IpnResponse(){
        $tcoConfig = new \Tco\Source\TcoConfig($this->configArr);
        $ipnSignature = new IpnSignature($tcoConfig);
        $params = $this->ipnParamsMockArray;
        unset($params['SIGNATURE_SHA3_256']);
        unset($params['SIGNATURE_SHA2_256']);
        $response = $ipnSignature->calculateIpnResponse($params);
        $this->assertNotEmpty($response);
    }

    public function _testCalculateIpnResponseFailure(){
        $tcoConfig = new \Tco\Source\TcoConfig($this->configArr);
         unset($this->ipnParamsMockArray['IPN_PID']);
        $ipnSignature = new IpnSignature($tcoConfig);
        $this->expectException(Exception::class);
        $response = $ipnSignature->calculateIpnResponse($this->ipnParamsMockArray);
    }
}
