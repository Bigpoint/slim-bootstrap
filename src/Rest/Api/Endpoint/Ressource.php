<?php
namespace Rest\Api\Endpoint;

use \Rest\Api\DataObject;

/**
 * This interface represents the basic structure for the ressource endpoints.
 *
 * @package Rest\Api
 */
interface Ressource
{
    /**
     * This function is called on a GET request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param string $param1 The first parameter for the endpoint
     *                       from the GET request.
     * @param string $param2 The second parameter for the endpoint
     *                       from the GET request.
     *
     * @return DataObject
     */
    public function get($param1, $param2);
}
