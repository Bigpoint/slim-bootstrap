<?php
namespace Rest\Api\Endpoint;

use \Rest\Api\DataObject;

/**
 * This interface represents the basic structure for the ressource endpoints.
 *
 * @package Rest\Api\Endpoint
 */
interface RessourcePost
{
    /**
     * This function is called on a POST request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $parameters The parameters for the endpoint from the POST
     *                          request.
     *
     * @return DataObject
     */
    public function post(array $parameters);
}
