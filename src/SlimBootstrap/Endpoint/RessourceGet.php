<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap;

/**
 * This interface represents the basic structure for the ressource endpoints.
 *
 * @package SlimBootstrap\Endpoint
 */
interface RessourceGet
{
    /**
     * This function is called on a GET request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $parameters The parameters for the endpoint from the GET
     *                          request.
     *
     * @return SlimBootstrap\DataObject
     */
    public function get(array $parameters);
}
