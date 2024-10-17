<?php

declare(strict_types=1);

namespace Keycloak\Service;

use JsonException;
use Keycloak\Exception\KeycloakException;
use Psr\Http\Message\ResponseInterface;

class CreateResponseService
{
    public static function handleCreateResponse(ResponseInterface $response): string
    {
        if ($response->getStatusCode() === 201) {
            return self::extractUIDFromCreateResponse($response);
        }

        try {
            $error = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (JsonException $ex) {
            throw new KeycloakException('Unable to decode error', 0, $ex);
        }


        if (!empty($error['errorMessage'])) {
            throw new KeycloakException($error['errorMessage']);
        }
        throw new KeycloakException('Something went wrong during entity creation');
    }

    /**
     * @param ResponseInterface $res
     * @return string
     * @throws KeycloakException
     */
    public static function extractUIDFromCreateResponse(ResponseInterface $res): string
    {
        $locationHeaders = $res->getHeader('Location');
        $newUserUrl = reset($locationHeaders);
        if ($newUserUrl === false) {
            throw new KeycloakException('Entity created without "Location" header');
        }
        $urlParts = array_reverse(explode('/', $newUserUrl));
        return reset($urlParts);
    }
}
