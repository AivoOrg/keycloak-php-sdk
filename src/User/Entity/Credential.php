<?php

namespace Keycloak\User\Entity;

use JsonSerializable;
use Keycloak\JsonDeserializable;

class Credential implements JsonSerializable, JsonDeserializable
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int|null
     */
    public $createdDate;

    /**
     * @var string
     */
    public $credentialData;

    /**
     * @param string $id
     * @param string $type
     * @param int|null $createdDate
     * @param string $credentialData
     */
    public function __construct(string $id, string $type, ?int $createdDate, string $credentialData)
    {
        $this->id = $id;
        $this->type = $type;
        $this->createdDate = $createdDate;
        $this->credentialData = $credentialData;
    }

    /**
     * @return Credential
     */
    public function jsonSerialize(): Credential
    {
        return $this;
    }

    /**
     * @param string|array $json
     * @return Credential
     */
    public static function fromJson($json): self
    {
        $arr = is_array($json) ? $json : json_decode($json, true);
        return new self(
            $arr['id'] ?? null,
            $arr['type'] ?? null,
            $arr['createdDate'] ?? null,
            $arr['credentialData'] ?? null
        );
    }
}
