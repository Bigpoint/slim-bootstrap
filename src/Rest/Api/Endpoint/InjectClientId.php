<?php
namespace Rest\Api\Endpoint;

/**
 * Interface InjectClientId
 *
 * @package Rest\Api\Endpoint
 */
interface InjectClientId
{
    /**
     * @param string $clientId
     */
    public function setClientId($clientId);
}
