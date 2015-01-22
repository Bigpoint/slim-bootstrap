<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap;

/**
 * This interface represents the basic structure for the ressource endpoints.
 *
 * @package SlimBootstrap\Endpoint
 */
interface RessourcePut
{
    /**
     * This function is called on a PUT request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $parameters The parameters how the endpoint was called.
     * @param array $data       The parameters for the endpoint from the POST
     *                          request.
     *
     * @return SlimBootstrap\DataObject
     */
    public function put(array $parameters, array $data);
}
