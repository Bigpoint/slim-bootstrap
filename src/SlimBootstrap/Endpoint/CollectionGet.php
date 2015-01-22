<?php
namespace SlimBootstrap\Endpoint;

/**
 * This interface represents the basic structure for the collection endpoints.
 *
 * @package SlimBootstrap\Endpoint
 */
interface CollectionGet
{
    /**
     * This function is called on a GET request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $filters array of key => value pairs to filter the result
     *
     * @return array
     */
    public function get(array $filters);
}
