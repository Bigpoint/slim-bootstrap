<?php
namespace SlimBootstrap\Endpoint;

use \SlimBootstrap;

/**
 * This class represents the index endpoint.
 *
 * @package SlimBootstrap\Endpoint
 */
class Index
{
    /**
     * The data array that holds all endpoint names
     *
     * @var array
     */
    private $_endpoints = array();

    /**
     * @param array $endpoints
     */
    public function __construct(array $endpoints)
    {
        $this->_endpoints = $endpoints;
    }

    /**
     * This function creates a ressource that has links to all existing
     * endpoints.
     *
     * @return SlimBootstrap\DataObject
     */
    public function get()
    {
        $links = array();

        foreach ($this->_endpoints as $endpoint => $route) {
            $links[$endpoint] = $route;
        }

        return new SlimBootstrap\DataObject(
            array(),
            array(
                'welcome' => 'Welcome.',
            ),
            $links
        );
    }
}
