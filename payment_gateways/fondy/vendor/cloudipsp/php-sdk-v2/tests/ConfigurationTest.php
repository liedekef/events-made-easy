<?php

namespace Cloudipsp;

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testGetApiUrl()
    {
        $this->assertEquals('https://api.fondy.eu/api', Configuration::getApiUrl());
        Configuration::setApiUrl('api.saas.com');
        $this->assertEquals(
            'https://api.saas.com/api',
            Configuration::getApiUrl()
        );
        Configuration::setApiUrl('api.fondy.eu');
        $this->assertEquals(
            'https://api.fondy.eu/api',
            Configuration::getApiUrl()
        );
    }

    public function testSetApiVersion()
    {
        $this->assertEquals(
            '1.0',
            Configuration::getApiVersion()
        );
        Configuration::setApiVersion('2.0');
        $this->assertEquals(
            '2.0',
            Configuration::getApiVersion()
        );
    }

    public function testSetHttpClient()
    {
        Configuration::setHttpClient('HttpGuzzle');
        $this->assertInstanceOf('\\Cloudipsp\\HttpClient\\HttpGuzzle', Configuration::getHttpClient());
        Configuration::setHttpClient('HttpCurl');
        $this->assertInstanceOf('\\Cloudipsp\\HttpClient\\HttpCurl', Configuration::getHttpClient());
        if (method_exists(get_parent_class($this), 'expectNotice')){
            $this->expectNotice();
        } else {
            $this->expectException('PHPUnit_Framework_Error_Notice');
        }
        $this->assertFalse(Configuration::setHttpClient('Unknown'));
    }

    public function testSetHttpClientClass()
    {
        Configuration::setHttpClient(new HttpClient\HttpCurl());
        $this->assertInstanceOf('\\Cloudipsp\\HttpClient\\HttpCurl', Configuration::getHttpClient());
    }

    public function testSetSecretKey()
    {
        Configuration::setSecretKey('something-secret');
        $this->assertEquals('something-secret', Configuration::getSecretKey());
    }


    public function testSetMerchantId()
    {
        Configuration::setMerchantId(123);
        $this->assertEquals(123, Configuration::getMerchantId());
    }

    public function testSetCreditKey()
    {
        Configuration::setCreditKey('something-secret');
        $this->assertEquals('something-secret', Configuration::getCreditKey());
    }
}
