<?php
namespace Rest\Api\Endpoint;

use \Rest\Api;

/**
 * This interface represents the basic structure for the ressource endpoints.
 *
 * @package Rest\Api\Endpoint
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
     * @return Api\DataObject
     */
    public function get(array $parameters);
}
