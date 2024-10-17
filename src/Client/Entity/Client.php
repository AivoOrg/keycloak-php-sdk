<?php

namespace Keycloak\Client\Entity;

use Keycloak\JsonDeserializable;

class Client implements JsonDeserializable
{
    public string $id;
    public string $clientId;
    public ?string $name;
    public ?string $description;
    public ?string $protocol;
    public ?string $baseUrl;
    public bool $enabled;
    public array $attributes;
    public array $webOrigins;
    public array $redirectUris;
    /** @var array<ProtocolMapper> */
    public array $protocolMappers;
    public ?bool $publicClient;
    public ?bool $authorizationServicesEnabled;
    public ?bool $directAccessGrantsEnabled;
    public ?bool $standardFlowEnabled;
    public ?bool $implicitFlowEnabled;
    public ?bool $serviceAccountsEnabled;

    public function __construct(
        string $id,
        string $clientId,
        ?string $name,
        ?string $description,
        ?string $protocol,
        ?string $baseUrl,
        bool $enabled,
        array $attributes,
        array $webOrigins,
        array $redirectUris,
        array $protocolMappers,
        ?bool $publicClient,
        ?bool $authorizationServicesEnabled,
        ?bool $directAccessGrantsEnabled,
        ?bool $standardFlowEnabled,
        ?bool $implicitFlowEnabled,
        ?bool $serviceAccountsEnabled

    ) {
        $this->id = $id;
        $this->clientId = $clientId;
        $this->name = $name;
        $this->description = $description;
        $this->protocol = $protocol;
        $this->baseUrl = $baseUrl;
        $this->enabled = $enabled;
        $this->attributes = $attributes;
        $this->webOrigins = $webOrigins;
        $this->redirectUris = $redirectUris;
        $this->protocolMappers = $protocolMappers;
        $this->publicClient = $publicClient;
        $this->authorizationServicesEnabled = $authorizationServicesEnabled;
        $this->directAccessGrantsEnabled = $directAccessGrantsEnabled;
        $this->standardFlowEnabled = $standardFlowEnabled;
        $this->implicitFlowEnabled = $implicitFlowEnabled;
        $this->serviceAccountsEnabled = $serviceAccountsEnabled;
    }

    /**
     * @param string|array $json
     */
    public static function fromJson($json): self
    {
        $arr = is_array($json) ? $json : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['clientId'],
            $arr['name'] ?? null,
            $arr['description'] ?? null,
            $arr['protocol'] ?? null,
            $arr['baseUrl'] ?? null,
            $arr['enabled'],
            $arr['attributes'] ?? [],
            $arr['webOrigins'],
            $arr['redirectUris'],
            array_map([ProtocolMapper::class, 'fromJson'], $arr['protocolMappers'] ?? []),
            $arr['publicClient'] ?? null,
            $arr['authorizationServicesEnabled'] ?? null,
            $arr['directAccessGrantsEnabled'] ?? null,
            $arr['standardFlowEnabled'] ?? null,
            $arr['implicitFlowEnabled'] ?? null,
            $arr['serviceAccountsEnabled'] ?? null,
        );
    }
}
