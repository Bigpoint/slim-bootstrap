<?php
namespace SlimBootstrap;

use \SlimBootstrap;

/**
 * Interface Authentication
 *
 * @package SlimBootstrap
 */
interface Authentication
{
    /**
     * @param string $token Access token from the calling client
     *
     * @return string The clientId of the calling client.
     *
     * @throws SlimBootstrap\Exception When the passed access $token is invalid.
     */
    public function authenticate($token);
}
