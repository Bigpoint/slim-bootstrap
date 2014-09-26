<?php
namespace Rest\Api\Endpoint;

/**
 * This interface represents the basic structure for the collection endpoints.
 *
 * @package Rest\Api\Endpoint
 */
interface CollectionPut
{
    /**
     * This function is called on a PUT request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $filters array of key => value pairs to filter the result
     * @param array $data    The parameters for the endpoint from the PUT
     *                       request.
     *
     * @return array
     */
    public function put(array $filters, array $data);
}
