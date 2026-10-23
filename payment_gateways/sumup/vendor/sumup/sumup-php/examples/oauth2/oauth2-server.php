<?php

/**
 * OAuth 2.0 Authorization Code flow with SumUp
 *
 * This example walks you through the steps necessary to implement
 * OAuth 2.0 (https://oauth.net/) in case you are building a software
 * for other people to use.
 *
 * To get started, you will need your client credentials.
 * If you don't have any yet, you can create them in the
 * [Developer Settings](https://me.sumup.com/en-us/settings/oauth2-applications).
 *
 * Your credentials need to be configured with the correct redirect URI,
 * that's the URI the user will get redirected to once they authenticate
 * and authorize your application. For development, you might want to
 * use for example `http://localhost:8080/callback`. In production, you would
 * redirect the user back to your host, e.g. `https://example.com/callback`.
 *
 * To run this example:
 * 1. Set environment variables: CLIENT_ID, CLIENT_SECRET, REDIRECT_URI
 * 2. Run: php -S localhost:8080 oauth2-server.php
 * 3. Visit: http://localhost:8080/login
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

const STATE_COOKIE_NAME = 'oauth_state';
const PKCE_COOKIE_NAME = 'oauth_pkce';

session_start();

$clientId = getenv('CLIENT_ID');
$clientSecret = getenv('CLIENT_SECRET');
$redirectUri = getenv('REDIRECT_URI') ?: 'http://localhost:8080/callback';

if (!$clientId || !$clientSecret) {
    die("Please set CLIENT_ID and CLIENT_SECRET environment variables\n");
}

// Configure the OAuth2 provider for SumUp
$provider = new GenericProvider([
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri' => $redirectUri,
    'urlAuthorize' => 'https://api.sumup.com/authorize',
    'urlAccessToken' => 'https://api.sumup.com/token',
    'urlResourceOwnerDetails' => '',
    // Scope is a mechanism in OAuth 2.0 to limit an application's access to a user's account.
    // You should always request the minimal set of scope that you need for your application to
    // work. In this example we use "payments transactions.history" scope which gives you access
    // to create payments and view transaction history.
    'scopes' => 'payments transactions.history user.profile_readonly user.app-settings',
]);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($requestUri) {
    case '/':
        echo '<h1>SumUp OAuth2 Example</h1>';
        echo '<p>This example demonstrates the OAuth2 Authorization Code flow with PKCE.</p>';
        echo '<a href="/login">Start OAuth2 Flow</a>';
        break;

    case '/login':
        handleLogin($provider);
        break;

    case '/callback':
        handleCallback($provider);
        break;

    default:
        http_response_code(404);
        echo 'Not Found';
}

function handleLogin(GenericProvider $provider): void
{
    // Generate random state for security
    $state = bin2hex(random_bytes(32));
    
    // Generate PKCE challenge and verifier
    $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

    // Store state and code verifier in session for later verification
    $_SESSION[STATE_COOKIE_NAME] = $state;
    $_SESSION[PKCE_COOKIE_NAME] = $codeVerifier;

    // Get authorization URL with state and PKCE parameters
    $authorizationUrl = $provider->getAuthorizationUrl([
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
    ]);

    // Redirect the user to the authorization URL
    header('Location: ' . $authorizationUrl);
    exit();
}

function handleCallback(GenericProvider $provider): void
{
    // Verify state parameter to prevent CSRF attacks
    $state = $_GET['state'] ?? '';
    $sessionState = $_SESSION[STATE_COOKIE_NAME] ?? '';
    
    if ($state !== $sessionState) {
        http_response_code(400);
        die('Invalid OAuth state parameter');
    }

    // Get the authorization code from the callback
    $code = $_GET['code'] ?? '';
    if (!$code) {
        http_response_code(400);
        die('Missing authorization code');
    }

    // Get the PKCE code verifier from session
    $codeVerifier = $_SESSION[PKCE_COOKIE_NAME] ?? '';
    if (!$codeVerifier) {
        http_response_code(400);
        die('Missing PKCE code verifier');
    }

    try {
        // Exchange the authorization code for an access token
        /** @var AccessToken $accessToken */
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ]);

        // Users might have access to multiple merchant accounts, the `merchant_code` parameter
        // returned in the callback is the merchant code of their default merchant account.
        // In production, you would want to let users pick which merchant they want to use
        // using the memberships API.
        $defaultMerchantCode = $_GET['merchant_code'] ?? '';

        echo '<h1>OAuth2 Success!</h1>';
        echo '<p>Successfully obtained access token.</p>';
        echo '<p><strong>Merchant Code:</strong> ' . htmlspecialchars($defaultMerchantCode) . '</p>';

        // Now use the access token with the SumUp SDK
        $sumup = new \SumUp\SumUp([
            'access_token' => $accessToken->getToken(),
        ]);

        if ($defaultMerchantCode) {
            echo '<h2>Merchant Information:</h2>';
            try {
                $merchant = $sumup->merchants()->get($defaultMerchantCode);
                echo '<pre>' . htmlspecialchars(json_encode($merchant, JSON_PRETTY_PRINT)) . '</pre>';
            } catch (Exception $e) {
                echo '<p style="color: red;">Error fetching merchant: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }

        // Display token information (in production, you would store this securely)
        echo '<h2>Access Token Information:</h2>';
        echo '<p><strong>Token:</strong> ' . htmlspecialchars(substr($accessToken->getToken(), 0, 20)) . '...</p>';
        echo '<p><strong>Expires:</strong> ' . ($accessToken->getExpires() ? date('Y-m-d H:i:s', $accessToken->getExpires()) : 'Never') . '</p>';
        if ($accessToken->getRefreshToken()) {
            echo '<p><strong>Refresh Token:</strong> Available</p>';
        }

        echo '<h2>Example: Create a Checkout</h2>';
        echo '<p>Here\'s how you would use the token to create a checkout:</p>';
        echo '<pre><code>';
        echo htmlspecialchars('<?php
$sumup = new \SumUp\SumUp([
    \'access_token\' => \'' . $accessToken->getToken() . '\',
]);

$checkout = $sumup->checkouts()->create([
    \'amount\' => 10.00,
    \'currency\' => \'EUR\',
    \'checkout_reference\' => \'my-order-123\',
    \'merchant_code\' => \'' . $defaultMerchantCode . '\',
    \'description\' => \'My product\',
]);

echo "Checkout ID: " . $checkout->id;');
        echo '</code></pre>';

        // Clean up session
        unset($_SESSION[STATE_COOKIE_NAME]);
        unset($_SESSION[PKCE_COOKIE_NAME]);

    } catch (Exception $e) {
        http_response_code(500);
        echo '<h1>OAuth2 Error</h1>';
        echo '<p style="color: red;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<a href="/login">Try again</a>';
    }
}
