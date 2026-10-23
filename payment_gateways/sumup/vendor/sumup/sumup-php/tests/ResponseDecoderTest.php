<?php

namespace SumUp\Tests;

use PHPUnit\Framework\TestCase;
use SumUp\Exception\ApiException;
use SumUp\Exception\ErrorEnvelope;
use SumUp\Exception\UnexpectedApiException;
use SumUp\HttpClient\Response;
use SumUp\ResponseDecoder;
use SumUp\Types\Error;

class ResponseDecoderTest extends TestCase
{
    public function testDecodeOrThrowHydratesNestedArrayItemsInDescriptorClass()
    {
        $response = new Response(400, [
            'errors' => [
                [
                    'message' => 'invalid request',
                    'error_code' => 'INVALID',
                ],
            ],
        ]);

        try {
            ResponseDecoder::decodeOrThrow(
                $response,
                null,
                [
                    '400' => ['type' => 'class', 'class' => ResponseDecoderNestedErrorEnvelope::class],
                ],
                'POST',
                '/v0.1/checkouts'
            );
            $this->fail('ApiException was not thrown');
        } catch (ApiException $exception) {
            $body = $exception->getResponseBody();
            $this->assertInstanceOf(ResponseDecoderNestedErrorEnvelope::class, $body);
            $this->assertIsArray($body->errors);
            $this->assertInstanceOf(Error::class, $body->errors[0]);
            $this->assertSame('invalid request', $body->errors[0]->message);
            $this->assertSame('INVALID', $body->errors[0]->errorCode);
        }
    }

    public function testDecodeOrThrowDecodesSuccessArrayDescriptorWithClassItems()
    {
        $response = new Response(200, [
            ['message' => 'a', 'error_code' => 'ONE'],
            ['message' => 'b', 'error_code' => 'TWO'],
        ]);

        $result = ResponseDecoder::decodeOrThrow(
            $response,
            [
                '200' => [
                    'type' => 'array',
                    'items' => ['type' => 'class', 'class' => Error::class],
                ],
            ],
            null,
            'GET',
            '/v0.1/errors'
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Error::class, $result[0]);
        $this->assertSame('ONE', $result[0]->errorCode);
    }

    public function testDecodeOrThrowDecodesOpaqueObjectDescriptorFromStdClass()
    {
        $response = new Response(200, (object) [
            'merchantSessionIdentifier' => 'session_123',
            'nested' => (object) ['value' => 'ok'],
        ]);

        $result = ResponseDecoder::decodeOrThrow(
            $response,
            [
                '200' => ['type' => 'object'],
            ],
            null,
            'POST',
            '/v0.1/checkouts/chk_123/create_apple_pay_session'
        );

        $this->assertIsArray($result);
        $this->assertSame('session_123', $result['merchantSessionIdentifier']);
        $this->assertInstanceOf(\stdClass::class, $result['nested']);
    }

    public function testDecodeOrThrowUsesUnexpectedFallbackForUnknownStatusWithNestedPayload()
    {
        $rawBody = [
            'error' => 'rate limited',
            'meta' => ['retry_after' => 30],
        ];
        $response = new Response(429, $rawBody);

        try {
            ResponseDecoder::decodeOrThrow(
                $response,
                null,
                [
                    '400' => ['type' => 'class', 'class' => Error::class],
                ],
                'GET',
                '/v0.1/checkouts'
            );
            $this->fail('UnexpectedApiException was not thrown');
        } catch (UnexpectedApiException $exception) {
            $this->assertSame('rate limited', $exception->getMessage());
            $this->assertSame($rawBody, $exception->getErrorEnvelope()->getRaw());
            $this->assertSame(429, $exception->getErrorEnvelope()->getStatus());
        }
    }

    public function testDecodeOrThrowUsesExpectedApiExceptionForKnownErrorDescriptor()
    {
        $response = new Response(401, [
            'error_code' => 'NOT_AUTHORIZED',
            'message' => 'Token expired',
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Token expired');

        ResponseDecoder::decodeOrThrow(
            $response,
            null,
            [
                '401' => ['type' => 'class', 'class' => Error::class],
            ],
            'GET',
            '/v0.1/me'
        );
    }

    public function testDecodeOrThrowUsesUnexpectedApiExceptionForUnknownErrorFormat()
    {
        $response = new Response(400, [
            ['error_code' => 'MISSING', 'param' => 'merchant_code'],
            ['error_code' => 'INVALID', 'param' => 'currency'],
        ]);

        try {
            ResponseDecoder::decodeOrThrow($response, null, null, 'POST', '/v0.1/checkouts');
            $this->fail('UnexpectedApiException was not thrown');
        } catch (UnexpectedApiException $exception) {
            $this->assertSame('POST', $exception->getHttpMethod());
            $this->assertSame('/v0.1/checkouts', $exception->getPath());
            $this->assertSame(400, $exception->getStatusCode());
            $this->assertSame($response->getBody(), $exception->getResponseBody());
            $this->assertSame('Unexpected API response (400)', $exception->getMessage());
            $this->assertInstanceOf(ErrorEnvelope::class, $exception->getErrorEnvelope());
            $this->assertSame([
                'status' => 400,
                'message' => 'Unexpected API response (400)',
                'raw' => $response->getBody(),
                'headers' => [],
            ], $exception->getErrorEnvelope()->toArray());
        }
    }

    public function testDecodeOrThrowUnexpectedExceptionIncludesHeadersAndRawBody()
    {
        $response = new Response(
            429,
            ['error' => 'rate limited'],
            ['Retry-After' => ['30'], 'X-Trace-Id' => ['abc-123']],
            '{"error":"rate limited"}'
        );

        try {
            ResponseDecoder::decodeOrThrow($response, null, null, 'GET', '/v0.1/checkouts');
            $this->fail('UnexpectedApiException was not thrown');
        } catch (UnexpectedApiException $exception) {
            $this->assertSame('{"error":"rate limited"}', $exception->getRawResponseBody());
            $this->assertSame([
                'Retry-After' => ['30'],
                'X-Trace-Id' => ['abc-123'],
            ], $exception->getErrorEnvelope()->getHeaders());
            $this->assertSame('{"error":"rate limited"}', $exception->getErrorEnvelope()->getRaw());
        }
    }

    public function testDecodeOrThrowUsesKnownErrorDescriptorFromOpenApi()
    {
        $response = new Response(404, [
            'message' => 'Checkout not found',
            'error_code' => 'NOT_FOUND',
        ]);

        try {
            ResponseDecoder::decodeOrThrow(
                $response,
                null,
                [
                    '404' => ['type' => 'class', 'class' => Error::class],
                ],
                'GET',
                '/v0.1/checkouts/chk_123'
            );
            $this->fail('ApiException was not thrown');
        } catch (ApiException $exception) {
            $this->assertSame('GET', $exception->getHttpMethod());
            $this->assertSame('/v0.1/checkouts/chk_123', $exception->getPath());
            $this->assertInstanceOf(Error::class, $exception->getResponseBody());
            $this->assertSame('Checkout not found', $exception->getMessage());
        }
    }

    public function testDecodeOrThrowReturnsUnexpectedApiExceptionForUnknownErrorFormat()
    {
        $response = new Response(502, '<html>bad gateway</html>');

        try {
            ResponseDecoder::decodeOrThrow($response, null, null, 'GET', '/v0.1/checkouts');
            $this->fail('UnexpectedApiException was not thrown');
        } catch (UnexpectedApiException $exception) {
            $this->assertSame('GET', $exception->getHttpMethod());
            $this->assertSame('/v0.1/checkouts', $exception->getPath());
            $this->assertSame(502, $exception->getStatusCode());
            $this->assertSame('<html>bad gateway</html>', $exception->getResponseBody());
            $this->assertSame('Unexpected API response (502)', $exception->getMessage());
            $this->assertSame(502, $exception->getErrorEnvelope()->getStatus());
            $this->assertSame('<html>bad gateway</html>', $exception->getErrorEnvelope()->getRaw());
        }
    }

    public function testUnexpectedApiExceptionNormalizesEnvelopeHeaders()
    {
        $exception = new UnexpectedApiException(
            'Unexpected API response (500)',
            500,
            ['error' => 'boom'],
            'GET',
            '/v0.1/test',
            [
                'x-trace-id' => 'abc',
                'retry-after' => [30, '60'],
                'empty' => [],
            ]
        );

        $this->assertSame([
            'x-trace-id' => ['abc'],
            'retry-after' => ['30', '60'],
        ], $exception->getErrorEnvelope()->getHeaders());
    }
}

class ResponseDecoderNestedErrorEnvelope
{
    /**
     * @var \SumUp\Types\Error[]|null
     */
    public ?array $errors = null;
}
