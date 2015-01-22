<?php
namespace SlimBootstrap;

use \SlimBootstrap;

/**
 * This class is responsible for managing the access of the different clientIds
 * to the various endpoints the API provides.
 *
 * @package SlimBootstrap
 */
class Acl
{
    /**
     * Holds the ACL configuration.
     *
     * @var array
     */
    private $_config = null;

    /**
     * @param array $config The ACL configuration
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * Checks if the given $clientId is allowed to access the given
     * $endpointName.
     *
     * @param string $clientId     The clientId which wants access
     *                             to the endpoint
     * @param string $endpointName The endpoint to which the clientId wants
     *                             access
     *
     * @throws SlimBootstrap\Exception When the clientId was not found in the
     *                                 config, or has no access to the endpoint.
     */
    public function access($clientId, $endpointName)
    {
        if (false === isset($this->_config['access'][$clientId])) {
            throw new SlimBootstrap\Exception('Access denied', 403);
        }

        $role = $this->_config['access'][$clientId];

        if (false === isset($this->_config['roles'][$role][$endpointName])
            || true !== $this->_config['roles'][$role][$endpointName]
        ) {
            throw new SlimBootstrap\Exception('Access denied', 403);
        }
    }
}
