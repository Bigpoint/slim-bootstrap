<?php
namespace Rest\Api\Endpoint;

/**
 * Interface InjectClientId
 *
 * This interface provides a function to set the clientId into the endpoint.
 * If an endpoint implements this interface the clientId will be injected before
 * the endpoint is called.
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
