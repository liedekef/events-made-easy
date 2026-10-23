<?php

declare(strict_types=1);

namespace SumUp\Roles;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\RequestEncoder;
use SumUp\ResponseDecoder;

class RolesCreateRequest
{
    /**
     * User-defined name of the role.
     *
     * @var string
     */
    public string $name;

    /**
     * User's permissions.
     *
     * @var string[]
     */
    public array $permissions;

    /**
     * Set of user-defined key-value pairs attached to the object. Partial updates are not supported. When updating, always submit whole metadata. Maximum of 64 parameters are allowed in the object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * User-defined description of the role.
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Create request DTO.
     *
     * @param string $name
     * @param string[] $permissions
     * @param array<string, mixed>|null $metadata
     * @param string|null $description
     */
    public function __construct(
        string $name,
        array $permissions,
        ?array $metadata = null,
        ?string $description = null
    ) {
        \SumUp\Hydrator::hydrate([
            'name' => $name,
            'permissions' => $permissions,
            'metadata' => $metadata,
            'description' => $description,
        ], self::class, $this);
    }

    /**
     * Create request DTO from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        self::assertRequiredFields($data, [
            'name' => 'name',
            'permissions' => 'permissions',
        ]);

        $request = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        \SumUp\Hydrator::hydrate($data, self::class, $request);

        return $request;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $requiredFields
     */
    private static function assertRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $serializedName => $propertyName) {
            if (!array_key_exists($serializedName, $data) && !array_key_exists($propertyName, $data)) {
                throw new \InvalidArgumentException(sprintf('Missing required field "%s".', $serializedName));
            }
        }
    }

}

class RolesUpdateRequest
{
    /**
     * User-defined name of the role.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * User's permissions.
     *
     * @var string[]|null
     */
    public ?array $permissions = null;

    /**
     * User-defined description of the role.
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Create request DTO.
     *
     * @param string|null $name
     * @param string[]|null $permissions
     * @param string|null $description
     */
    public function __construct(
        ?string $name = null,
        ?array $permissions = null,
        ?string $description = null
    ) {
        \SumUp\Hydrator::hydrate([
            'name' => $name,
            'permissions' => $permissions,
            'description' => $description,
        ], self::class, $this);
    }

    /**
     * Create request DTO from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $request = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        \SumUp\Hydrator::hydrate($data, self::class, $request);

        return $request;
    }

}

class RolesListResponse
{
    /**
     *
     * @var \SumUp\Types\Role[]
     */
    public array $items;

}

/**
 * Class Roles
 *
 * Endpoints to manage custom roles. Custom roles allow you to tailor roles from individual permissions to match your needs. Once created, you can assign your custom roles to your merchant account members using the memberships.
 *
 * @package SumUp\Services
 */
class Roles implements SumUpService
{
    /**
     * The client for the http communication.
     *
     * @var HttpClientInterface
     */
    protected HttpClientInterface $client;

    /**
     * The access token needed for authentication for the services.
     *
     * @var string
     */
    protected string $accessToken;

    /**
     * Roles constructor.
     *
     * @param HttpClientInterface $client
     * @param string $accessToken
     */
    public function __construct(HttpClientInterface $client, string $accessToken)
    {
        $this->client = $client;
        $this->accessToken = $accessToken;
    }

    /**
     * Create a role
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param RolesCreateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Role
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function create(string $merchantCode, RolesCreateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Role
    {
        $path = sprintf('/v0.1/merchants/%s/roles', rawurlencode((string) $merchantCode));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = RolesCreateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '201' => ['type' => 'class', 'class' => \SumUp\Types\Role::class],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'POST', $path);
    }

    /**
     * Delete a role
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $roleId The ID of the role to retrieve.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return null
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function delete(string $merchantCode, string $roleId, ?RequestOptions $requestOptions = null): null
    {
        $path = sprintf('/v0.1/merchants/%s/roles/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $roleId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('DELETE', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '200' => ['type' => 'void'],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'DELETE', $path);
    }

    /**
     * Retrieve a role
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $roleId The ID of the role to retrieve.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Role
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $merchantCode, string $roleId, ?RequestOptions $requestOptions = null): \SumUp\Types\Role
    {
        $path = sprintf('/v0.1/merchants/%s/roles/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $roleId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Role::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * List roles
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Services\RolesListResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function list(string $merchantCode, ?RequestOptions $requestOptions = null): \SumUp\Services\RolesListResponse
    {
        $path = sprintf('/v0.1/merchants/%s/roles', rawurlencode((string) $merchantCode));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Services\RolesListResponse::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Update a role
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $roleId The ID of the role to retrieve.
     * @param RolesUpdateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Role
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function update(string $merchantCode, string $roleId, RolesUpdateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Role
    {
        $path = sprintf('/v0.1/merchants/%s/roles/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $roleId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = RolesUpdateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('PATCH', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Role::class, [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'PATCH', $path);
    }
}
