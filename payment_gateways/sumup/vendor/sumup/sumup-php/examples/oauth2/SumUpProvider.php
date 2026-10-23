<?php

/**
 * SumUp OAuth2 Provider
 *
 * A custom OAuth2 provider class specifically for SumUp that extends
 * the League OAuth2 Client with SumUp-specific defaults and endpoints.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class SumUpProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * SumUp's OAuth2 authorization endpoint
     */
    const AUTHORIZE_URL = 'https://api.sumup.com/authorize';

    /**
     * SumUp's OAuth2 token endpoint
     */
    const TOKEN_URL = 'https://api.sumup.com/token';

    /**
     * Default scopes for SumUp API
     */
    const DEFAULT_SCOPES = [
        'payments',
        'transactions.history',
        'user.profile_readonly',
        'user.app-settings'
    ];

    /**
     * @var string
     */
    protected $baseUrl = 'https://api.sumup.com';

    public function __construct(array $options = [], array $collaborators = [])
    {
        // Set default scopes if none provided
        if (!isset($options['scopes'])) {
            $options['scopes'] = self::DEFAULT_SCOPES;
        }

        parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return self::AUTHORIZE_URL;
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return self::TOKEN_URL;
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        // SumUp doesn't have a dedicated user info endpoint in the public API
        // You would typically use the merchants or memberships endpoints
        return $this->baseUrl . '/v0.1/me/memberships';
    }

    protected function getDefaultScopes(): array
    {
        return self::DEFAULT_SCOPES;
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            $message = 'OAuth2 error';
            
            if (isset($data['error'])) {
                $message = $data['error'];
                if (isset($data['error_description'])) {
                    $message .= ': ' . $data['error_description'];
                }
            }

            throw new IdentityProviderException(
                $message,
                $response->getStatusCode(),
                $response
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): SumUpResourceOwner
    {
        return new SumUpResourceOwner($response);
    }

    /**
     * Get authorization URL with PKCE enabled by default
     */
    public function getAuthorizationUrl(array $options = []): string
    {
        // Enable PKCE by default for enhanced security
        if (!isset($options['code_challenge'])) {
            $verifier = $this->getRandomPKCEVerifier();
            $challenge = $this->getPKCEChallenge($verifier);
            
            $options['code_challenge'] = $challenge;
            $options['code_challenge_method'] = 'S256';
            
            // Store verifier for later use (you should store this securely)
            $this->pkceVerifier = $verifier;
        }

        return parent::getAuthorizationUrl($options);
    }

    /**
     * Generate a random PKCE code verifier
     */
    public function getRandomPKCEVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Generate PKCE code challenge from verifier
     */
    public function getPKCEChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Get access token with PKCE verifier
     */
    public function getAccessTokenWithPKCE(string $code, string $verifier): AccessToken
    {
        return $this->getAccessToken('authorization_code', [
            'code' => $code,
            'code_verifier' => $verifier,
        ]);
    }
}

/**
 * Resource owner class for SumUp users
 */
class SumUpResourceOwner implements \League\OAuth2\Client\Provider\ResourceOwnerInterface
{
    protected $response;

    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    public function getId()
    {
        // SumUp doesn't return a user ID in the memberships endpoint
        // This would typically be available in a dedicated user info endpoint
        return $this->response['id'] ?? null;
    }

    public function toArray(): array
    {
        return $this->response;
    }

    /**
     * Get user's merchant memberships
     */
    public function getMemberships(): array
    {
        return $this->response;
    }

    /**
     * Get the default merchant code
     */
    public function getDefaultMerchantCode(): ?string
    {
        if (!empty($this->response) && is_array($this->response)) {
            $firstMembership = reset($this->response);
            return $firstMembership['merchant_code'] ?? null;
        }
        return null;
    }
}
