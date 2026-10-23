<?php

declare(strict_types=1);

namespace SumUp\Members;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\RequestEncoder;
use SumUp\ResponseDecoder;

class MembersCreateRequest
{
    /**
     * True if the user is managed by the merchant. In this case, we'll created a virtual user with the provided password and nickname.
     *
     * @var bool|null
     */
    public ?bool $isManagedUser = null;

    /**
     * Email address of the member to add.
     *
     * @var string
     */
    public string $email;

    /**
     * Password of the member to add. Only used if `is_managed_user` is true. In the case of service accounts, the password is not used and can not be defined by the caller.
     *
     * @var string|null
     */
    public ?string $password = null;

    /**
     * Nickname of the member to add. Only used if `is_managed_user` is true. Used for display purposes only.
     *
     * @var string|null
     */
    public ?string $nickname = null;

    /**
     * List of roles to assign to the new member.
     *
     * @var string[]
     */
    public array $roles;

    /**
     * Set of user-defined key-value pairs attached to the object. Partial updates are not supported. When updating, always submit whole metadata. Maximum of 64 parameters are allowed in the object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * Object attributes that are modifiable only by SumUp applications.
     *
     * @var array<string, mixed>|null
     */
    public ?array $attributes = null;

    /**
     * Create request DTO.
     *
     * @param string $email
     * @param string[] $roles
     * @param bool|null $isManagedUser
     * @param string|null $password
     * @param string|null $nickname
     * @param array<string, mixed>|null $metadata
     * @param array<string, mixed>|null $attributes
     */
    public function __construct(
        string $email,
        array $roles,
        ?bool $isManagedUser = null,
        ?string $password = null,
        ?string $nickname = null,
        ?array $metadata = null,
        ?array $attributes = null
    ) {
        \SumUp\Hydrator::hydrate([
            'email' => $email,
            'roles' => $roles,
            'is_managed_user' => $isManagedUser,
            'password' => $password,
            'nickname' => $nickname,
            'metadata' => $metadata,
            'attributes' => $attributes,
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
            'email' => 'email',
            'roles' => 'roles',
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

class MembersUpdateRequest
{
    /**
     *
     * @var string[]|null
     */
    public ?array $roles = null;

    /**
     * Set of user-defined key-value pairs attached to the object. Partial updates are not supported. When updating, always submit whole metadata. Maximum of 64 parameters are allowed in the object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * Object attributes that are modifiable only by SumUp applications.
     *
     * @var array<string, mixed>|null
     */
    public ?array $attributes = null;

    /**
     * Allows you to update user data of managed users.
     *
     * @var MembersUpdateRequestUser|null
     */
    public ?MembersUpdateRequestUser $user = null;

    /**
     * Create request DTO.
     *
     * @param string[]|null $roles
     * @param array<string, mixed>|null $metadata
     * @param array<string, mixed>|null $attributes
     * @param MembersUpdateRequestUser|null $user
     */
    public function __construct(
        ?array $roles = null,
        ?array $metadata = null,
        ?array $attributes = null,
        ?MembersUpdateRequestUser $user = null
    ) {
        \SumUp\Hydrator::hydrate([
            'roles' => $roles,
            'metadata' => $metadata,
            'attributes' => $attributes,
            'user' => $user,
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

class MembersListResponse
{
    /**
     *
     * @var \SumUp\Types\Member[]
     */
    public array $items;

    /**
     *
     * @var int|null
     */
    public ?int $totalCount = null;

}

/**
 * Allows you to update user data of managed users.
 */
class MembersUpdateRequestUser
{
    /**
     * User's nickname. Used for display purposes only.
     *
     * @var string|null
     */
    public ?string $nickname = null;

    /**
     * Password of the member to add. Only used if `is_managed_user` is true.
     *
     * @var string|null
     */
    public ?string $password = null;

}

/**
 * Query parameters for MembersListParams.
 *
 * @package SumUp\Services
 */
class MembersListParams
{
    /**
     * Offset of the first member to return.
     *
     * @var int|null
     */
    public ?int $offset = null;

    /**
     * Maximum number of members to return.
     *
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * Indicates to skip count query.
     *
     * @var bool|null
     */
    public ?bool $scroll = null;

    /**
     * Filter the returned members by email address prefix.
     *
     * @var string|null
     */
    public ?string $email = null;

    /**
     * Search for a member by user id.
     *
     * @var string|null
     */
    public ?string $userId = null;

    /**
     * Filter the returned members by the membership status.
     *
     * @var string|null
     */
    public ?string $status = null;

    /**
     * Filter the returned members by role.
     *
     * @var string[]|null
     */
    public ?array $roles = null;

}

/**
 * Class Members
 *
 * Endpoints to manage account members. Members are users that have membership within merchant accounts.
 *
 * @package SumUp\Services
 */
class Members implements SumUpService
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
     * Members constructor.
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
     * Create a member
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param MembersCreateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Member
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function create(string $merchantCode, MembersCreateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Member
    {
        $path = sprintf('/v0.1/merchants/%s/members', rawurlencode((string) $merchantCode));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = MembersCreateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '201' => ['type' => 'class', 'class' => \SumUp\Types\Member::class],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '429' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'POST', $path);
    }

    /**
     * Delete a member
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $memberId The ID of the member to retrieve.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return null
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function delete(string $merchantCode, string $memberId, ?RequestOptions $requestOptions = null): null
    {
        $path = sprintf('/v0.1/merchants/%s/members/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $memberId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('DELETE', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '200' => ['type' => 'void'],
        ], [
            '403' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'DELETE', $path);
    }

    /**
     * Retrieve a member
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $memberId The ID of the member to retrieve.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Member
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $merchantCode, string $memberId, ?RequestOptions $requestOptions = null): \SumUp\Types\Member
    {
        $path = sprintf('/v0.1/merchants/%s/members/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $memberId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Member::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * List members
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param MembersListParams|null $queryParams Optional query string parameters
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Services\MembersListResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function list(string $merchantCode, ?MembersListParams $queryParams = null, ?RequestOptions $requestOptions = null): \SumUp\Services\MembersListResponse
    {
        $path = sprintf('/v0.1/merchants/%s/members', rawurlencode((string) $merchantCode));
        if ($queryParams !== null) {
            $queryParamsData = [];
            if (isset($queryParams->offset)) {
                $queryParamsData['offset'] = $queryParams->offset;
            }
            if (isset($queryParams->limit)) {
                $queryParamsData['limit'] = $queryParams->limit;
            }
            if (isset($queryParams->scroll)) {
                $queryParamsData['scroll'] = $queryParams->scroll;
            }
            if (isset($queryParams->email)) {
                $queryParamsData['email'] = $queryParams->email;
            }
            if (isset($queryParams->userId)) {
                $queryParamsData['user.id'] = $queryParams->userId;
            }
            if (isset($queryParams->status)) {
                $queryParamsData['status'] = $queryParams->status;
            }
            if (isset($queryParams->roles)) {
                $queryParamsData['roles'] = $queryParams->roles;
            }
            if (!empty($queryParamsData)) {
                $queryString = http_build_query($queryParamsData);
                if (!empty($queryString)) {
                    $path .= '?' . $queryString;
                }
            }
        }
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Services\MembersListResponse::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Update a member
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $memberId The ID of the member to retrieve.
     * @param MembersUpdateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Member
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function update(string $merchantCode, string $memberId, MembersUpdateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Member
    {
        $path = sprintf('/v0.1/merchants/%s/members/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $memberId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = MembersUpdateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('PUT', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Member::class, [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '409' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'PUT', $path);
    }
}
