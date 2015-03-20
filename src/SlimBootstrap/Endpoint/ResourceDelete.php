<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap;

/**
 * This interface represents the basic structure for the resource endpoints.
 *
 * @package SlimBootstrap\Endpoint
 */
interface ResourceDelete
{
    /**
     * This function is called on a DELETE request to get all data for this
     * endpoint and put them in a usable format.
     *
     * @param array $parameters The parameters how the endpoint was called.
     *
     * @return SlimBootstrap\DataObject
     */
    public function delete(array $parameters);
}
