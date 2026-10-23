<?php

namespace SumUp\HttpClient;

use SumUp\SdkInfo;

/**
 * Builds the standard SDK headers for outbound requests.
 */
class RequestHeaders
{
    /**
     * @param string|null $accessToken
     * @param RequestOptions|null $options
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    public static function build(?string $accessToken = null, ?RequestOptions $options = null, array $headers = []): array
    {
        $requestHeaders = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => SdkInfo::getUserAgent(),
        ], SdkInfo::getRuntimeHeaders(), $headers);

        if (!empty($accessToken)) {
            $requestHeaders['Authorization'] = 'Bearer ' . $accessToken;
        }

        if ($options !== null && !empty($options->headers)) {
            $requestHeaders = array_merge($requestHeaders, $options->headers);
        }

        return $requestHeaders;
    }
}
