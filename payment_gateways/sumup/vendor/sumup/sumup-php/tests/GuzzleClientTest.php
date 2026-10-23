<?php

namespace SumUp\Tests;

use PHPUnit\Framework\TestCase;
use SumUp\Exception\ConnectionException;
use SumUp\HttpClient\GuzzleClient;

class GuzzleClientTest extends TestCase
{
    public function testRequestFailureWithoutResponseRaisesConnectionException(): void
    {
        $client = new GuzzleClient('unsupported://example.test');

        $this->expectException(ConnectionException::class);

        $client->send('GET', '/', [], []);
    }
}
