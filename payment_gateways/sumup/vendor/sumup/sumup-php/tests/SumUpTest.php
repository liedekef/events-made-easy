<?php

namespace SumUp\Tests;

use PHPUnit\Framework\TestCase;
use SumUp\HttpClient\RequestOptions;
use SumUp\HttpClient\Response;
use SumUp\SumUp;
use SumUp\Tests\Doubles\FakeHttpClient;

class SumUpTest extends TestCase
{
    private $originalApiKey;
    private $originalAccessToken;

    protected function setUp(): void
    {
        $this->originalApiKey = getenv('SUMUP_API_KEY');
        $this->originalAccessToken = getenv('SUMUP_ACCESS_TOKEN');
        putenv('SUMUP_API_KEY');
        putenv('SUMUP_ACCESS_TOKEN');
    }

    protected function tearDown(): void
    {
        if ($this->originalApiKey !== false && $this->originalApiKey !== null) {
            putenv('SUMUP_API_KEY=' . $this->originalApiKey);
        } else {
            putenv('SUMUP_API_KEY');
        }

        if ($this->originalAccessToken !== false && $this->originalAccessToken !== null) {
            putenv('SUMUP_ACCESS_TOKEN=' . $this->originalAccessToken);
        } else {
            putenv('SUMUP_ACCESS_TOKEN');
        }
    }

    public function testCanCreateWithApiKey()
    {
        $sumup = new SumUp('secret-api-key');

        $token = $sumup->getDefaultAccessToken();
        $this->assertIsString($token);
        $this->assertSame('secret-api-key', $token);
    }

    public function testCanCreateWithAccessToken()
    {
        $sumup = new SumUp([
            'access_token' => 'access-token-value',
        ]);

        $token = $sumup->getDefaultAccessToken();
        $this->assertIsString($token);
        $this->assertSame('access-token-value', $token);
    }

    public function testCanCreateWithoutToken()
    {
        $sumup = new SumUp();

        $this->assertNull($sumup->getDefaultAccessToken());
    }

    public function testCanSetDefaultAccessToken()
    {
        $sumup = new SumUp();
        $sumup->setDefaultAccessToken('new-token');

        $token = $sumup->getDefaultAccessToken();
        $this->assertIsString($token);
        $this->assertSame('new-token', $token);
    }

    public function testRawRequestDoesNotAuthenticateWhenNoAccessTokenIsSet()
    {
        $fakeClient = new FakeHttpClient(new Response(200, ['ok' => true]));
        $sumup = new SumUp(['client' => $fakeClient]);

        $response = $sumup->request('GET', '/ping');

        $requests = $fakeClient->getRequests();
        $this->assertCount(1, $requests);
        $this->assertSame('GET', $requests[0]['method']);
        $this->assertSame('/ping', $requests[0]['url']);
        $this->assertArrayNotHasKey('Authorization', $requests[0]['headers']);
        $this->assertSame(['ok' => true], $response->getBody());
    }

    public function testRawRequestAppliesAuthenticationAndAdditionalHeaders()
    {
        $fakeClient = new FakeHttpClient(new Response(200, ['ok' => true]));
        $sumup = new SumUp([
            'client' => $fakeClient,
            'access_token' => 'default-token',
        ]);

        $sumup->request('POST', '/custom', ['foo' => 'bar'], new RequestOptions(
            headers: [
                'X-Integrator' => 'example',
                'Authorization' => 'Bearer override-token',
            ]
        ));

        $requests = $fakeClient->getRequests();
        $this->assertCount(1, $requests);
        $this->assertSame(['foo' => 'bar'], $requests[0]['body']);
        $this->assertSame('example', $requests[0]['headers']['X-Integrator']);
        $this->assertSame('Bearer override-token', $requests[0]['headers']['Authorization']);
    }

    public function testServiceRequestsApplyAdditionalHeadersOnTopOfSdkHeaders()
    {
        $fakeClient = new FakeHttpClient(new Response(204, null));
        $sumup = new SumUp([
            'client' => $fakeClient,
            'access_token' => 'default-token',
        ]);

        $sumup->customers()->deactivatePaymentInstrument('customer-id', 'token-id', new RequestOptions(
            headers: [
                'X-Integrator' => 'example',
                'Authorization' => 'Bearer override-token',
            ]
        ));

        $requests = $fakeClient->getRequests();
        $this->assertCount(1, $requests);
        $this->assertSame('application/json', $requests[0]['headers']['Content-Type']);
        $this->assertSame('example', $requests[0]['headers']['X-Integrator']);
        $this->assertSame('Bearer override-token', $requests[0]['headers']['Authorization']);
    }

    public function testMethodAccessUsesDefaultToken()
    {
        $sumup = new SumUp('test-key');

        $checkouts = $sumup->checkouts();
        $this->assertInstanceOf(\SumUp\Services\Checkouts::class, $checkouts);
    }

    public function testMagicPropertyAccessIsNotSupported()
    {
        $this->assertFalse(method_exists(SumUp::class, '__get'));
    }
}
