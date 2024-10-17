<?php

namespace Keycloak;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Keycloak\Exception\KeycloakCredentialsException;
use Keycloak\Exception\KeycloakException;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\ResponseInterface;

class KeycloakClient
{
    /**
     * @var GenericProvider
     */
    private $oauthProvider;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var string
     */
    private $realm;

    /**
     * KeycloakClient constructor.
     *
     * @param string      $clientId
     * @param string      $clientSecret
     * @param string      $realm
     * @param string      $url
     * @param string|null $altAuthRealm
     * @param string|null $basePath Version 17+ removed the fixed <tt>/auth</tt> base-path.
     *                              The relative base can still be set with <tt>"--http-relative-path=/..."</tt> when
     *                              Keycloak is started, so this argument should match that setting.
     *                              If no relative path is in use: <tt>"/"</tt> or <tt>""</tt>.
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $realm,
        string $url,
        ?string $altAuthRealm = null,
        ?string $basePath = null
    ) {
        $this->realm = $realm;

        $authRealm = $altAuthRealm ?: $realm;
        // Coalesce - empty string should be preserved
        $relativePath = $basePath ?? '/auth';
        $fullBaseUrl = trim(rtrim($url, '/') . '/' . ltrim($relativePath, '/'), '/');

        $this->oauthProvider = new GenericProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'urlAccessToken' => "{$fullBaseUrl}/realms/{$authRealm}/protocol/openid-connect/token",
            'urlAuthorize' => '',
            'urlResourceOwnerDetails' => '',
        ]);
        $this->guzzleClient = new GuzzleClient(['base_uri' => "{$fullBaseUrl}/admin/realms/"]);
    }

    public function sendRealmlessRequest(string $method, string $uri, $body = null, array $headers = []): ResponseInterface
    {
        try {
            $accessToken = $this->oauthProvider->getAccessToken('client_credentials');
        } catch (Exception $ex) {
            throw new KeycloakCredentialsException();
        }

        $data = ['headers' => $headers];
        if ($body !== null) {
            $data['headers']['Content-Type'] = 'application/json';
            $data['body'] = json_encode($body);
        }

        $request = $this->oauthProvider->getAuthenticatedRequest(
            $method,
            $uri,
            $accessToken,
            $data
        );

        try {
            return $this->guzzleClient->send($request);
        } catch (GuzzleException $ex) {
            throw new KeycloakException(
                $ex->getMessage(),
                $ex->getCode(),
                $ex
            );
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param mixed $body
     * @param array $headers
     * @return ResponseInterface
     * @throws KeycloakException
     */
    public function sendRequest(string $method, string $uri, $body = null, array $headers = []): ResponseInterface
    {
        return $this->sendRealmlessRequest(
            $method,
            "{$this->realm}/$uri",
            $body,
            $headers
        );
    }

    /**
     * @return GenericProvider
     */
    public function getOAuthProvider(): GenericProvider
    {
        return $this->oauthProvider;
    }
}
