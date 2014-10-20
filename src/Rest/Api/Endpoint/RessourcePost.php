<?php
namespace Rest\Api\Endpoint;

use \Rest\Api;

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
     * @param array $parameters The parameters how the endpoint was called.
     * @param array $data       The parameters for the endpoint from the POST
     *                          request.
     *
     * @return Api\DataObject
     */
    public function post(array $parameters, array $data);
}
