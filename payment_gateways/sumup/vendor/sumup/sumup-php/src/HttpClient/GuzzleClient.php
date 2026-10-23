<?php

namespace SumUp\HttpClient;

use SumUp\Exception\ConfigurationException;
use SumUp\Exception\ConnectionException;

/**
 * Guzzle-based HTTP client (optional dependency).
 */
class GuzzleClient implements HttpClientInterface
{
    /**
     * @var string
     */
    private string $baseUrl;

    /**
     * @var array<string, string>
     */
    private array $customHeaders;

    /**
     * @var string|null
     */
    private $caBundlePath;

    /**
     * GuzzleClient constructor.
     *
     * @param string $baseUrl
     * @param array<string, string> $customHeaders
     * @param string|null $caBundlePath
     *
     * @throws ConfigurationException
     */
    public function __construct(string $baseUrl, array $customHeaders = [], ?string $caBundlePath = null)
    {
        $this->ensureGuzzleInstalled();

        $this->baseUrl = $baseUrl;
        $this->customHeaders = $customHeaders;
        $this->caBundlePath = $caBundlePath;
    }

    /**
     * @param string $method      The request method.
     * @param string $url         The endpoint to send the request to.
     * @param array<int|string, mixed> $body        The body of the request.
     * @param array<string, string> $headers     The headers of the request.
     * @param RequestOptions|null $options Optional typed request options.
     *
     * @return Response
     *
     * @throws ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function send(string $method, string $url, array $body, array $headers, ?RequestOptions $options = null): Response
    {
        $this->ensureGuzzleInstalled();

        $reqHeaders = array_merge($headers, $this->customHeaders);
        $retries = $options !== null ? ($options->retries ?? 0) : 0;
        $backoffMs = $options !== null ? ($options->retryBackoffMs ?? 0) : 0;

        $handler = \GuzzleHttp\HandlerStack::create();
        if ($retries > 0) {
            $handler->push(\GuzzleHttp\Middleware::retry(
                function ($retry, $request, $response = null, $exception = null) use ($retries) {
                    if ($retry >= $retries) {
                        return false;
                    }
                    if ($exception !== null) {
                        return true;
                    }
                    if ($response && $response->getStatusCode() >= 500) {
                        return true;
                    }
                    return false;
                },
                function ($retry) use ($backoffMs) {
                    if ($backoffMs <= 0) {
                        return 0;
                    }
                    return (int) ($backoffMs * pow(2, $retry));
                }
            ));
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'handler' => $handler,
            'http_errors' => false,
            'verify' => $this->caBundlePath ?: true,
        ]);

        $requestParams = ['headers' => $reqHeaders];

        if (!empty($body)) {
            $requestParams['json'] = $body;
        }

        if ($options?->timeout !== null) {
            $requestParams['timeout'] = $options->timeout;
        }

        if ($options?->connectTimeout !== null) {
            $requestParams['connect_timeout'] = $options->connectTimeout;
        }

        try {
            $response = $client->request($method, $url, $requestParams);
        } catch (\GuzzleHttp\Exception\TransferException $exception) {
            // Guzzle 8 exposes getResponse() only on response-bearing exceptions.
            $response = method_exists($exception, 'getResponse') ? $exception->getResponse() : null;
            if (!$response instanceof \Psr\Http\Message\ResponseInterface) {
                throw new ConnectionException($exception->getMessage(), $exception->getCode());
            }
        }

        $statusCode = $response->getStatusCode();
        $responseBody = (string) $response->getBody();
        $parsedBody = $this->parseBody($responseBody);
        $headers = $this->normalizeHeaders($response->getHeaders());

        return new Response($statusCode, $parsedBody, $headers, $responseBody);
    }

    /**
     * @param string $response
     *
     * @return mixed
     */
    private function parseBody(string $response)
    {
        $jsonBody = json_decode($response, true);
        if (isset($jsonBody)) {
            return $jsonBody;
        }
        return $response;
    }

    /**
     * @param array<string, array<int, string>> $headers
     *
     * @return array<string, array<int, string>>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $values) {
            if ($name === '') {
                continue;
            }

            $items = [];
            foreach ($values as $value) {
                if ($value !== '') {
                    $items[] = $value;
                }
            }
            if (!empty($items)) {
                $normalized[$name] = $items;
            }
        }

        return $normalized;
    }

    /**
     * @throws ConfigurationException
     */
    private function ensureGuzzleInstalled(): void
    {
        if (!class_exists('\\GuzzleHttp\\Client')) {
            throw new ConfigurationException(
                'Guzzle is not installed. Run `composer require guzzlehttp/guzzle` to use SumUp\\HttpClient\\GuzzleClient.'
            );
        }
    }
}
