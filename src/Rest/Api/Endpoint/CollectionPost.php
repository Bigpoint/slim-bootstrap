<?php
namespace Rest\Api\Endpoint;

/**
 * This interface represents the basic structure for the collection endpoints.
 *
 * @package Rest\Api\Endpoint
 */
interface CollectionPost
{
    /**
     * This function is called on a POST request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $filters array of key => value pairs to filter the result
     *
     * @return array
     */
    public function post(array $filters);
}
