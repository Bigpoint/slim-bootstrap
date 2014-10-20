<?php
namespace Rest\Api;

use Rest\Api;

/**
 * Interface Authentication
 *
 * @package Rest\Api
 */
interface Authentication
{
    /**
     * @param string $token Access token from the calling client
     *
     * @return string The clientId of the calling client.
     *
     * @throws Api\Exception When the passed access $token is invalid.
     */
    public function authenticate($token);
}
